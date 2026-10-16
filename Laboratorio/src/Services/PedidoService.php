<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Repositories\DeterminacionRepository;
use App\Repositories\PedidoItemRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\ResultadoRepository;
use App\Services\AranceladorService;
use PDO;
use Throwable;

/**
 * Logica de negocio del pedido medico.
 *
 * Reglas que aplica:
 *   - Validacion de input (paciente, items, prioridad).
 *   - Expansion de perfiles a determinaciones.
 *   - Deduplicacion de determinaciones (un pedido no repite el mismo analisis).
 *   - Generacion de numero correlativo simple por año (1, 2, 3, ...) con reseteo anual.
 *   - Snapshot inmutable de datos del paciente.
 *   - Atomicidad: pedido + items + auditoria en una sola transaccion.
 */
final class PedidoService
{
    private const PRIORIDADES_VALIDAS = ['rutina', 'urgente', 'guardia'];
    // 'X' coincide con el ENUM de la tabla pacientes del sistema mayor.
    private const SEXOS_VALIDOS       = ['M', 'F', 'X'];

    /** Estados validos del pedido (matchea ENUM en lab_pedidos). */
    private const ESTADOS_VALIDOS = [
        'pendiente', 'en_proceso', 'parcial', 'completo', 'entregado', 'anulado',
    ];

    private const ORDENES_VALIDOS = [
        'fecha_solicitud_desc', 'fecha_solicitud_asc',
        'fecha_entrega_desc',   'fecha_entrega_asc',
        'numero_desc',          'numero_asc',
        'estado_asc',           'estado_desc',
        'prioridad_asc',        'prioridad_desc',
    ];

    private const ORDEN_DEFAULT = 'fecha_solicitud_desc';
    private const POR_PAGINA    = 50;

    public function __construct(
        private PDO $db,
        private PedidoRepository $pedidoRepo,
        private DeterminacionRepository $determinacionRepo,
        private PerfilRepository $perfilRepo,
        private AuditoriaService $auditoria,
        private AranceladorService $arancelador,
        private PedidoItemRepository $itemRepo,
        private ResultadoRepository $resultadoRepo,
    ) {
    }

    /**
     * Da de alta un pedido. Devuelve { id, numero, items_count }.
     *
     * @param array<string,mixed> $input
     * @return array{id:int, numero:string, items_count:int}
     */
    public function crear(array $input, int $usuarioId): array
    {
        $errors = [];

        // paciente_id es opcional: en Control Salud el paciente puede venir del
        // contexto. La fuente de verdad por pedido es snapshot_paciente.
        $pacienteId = !empty($input['paciente_id']) ? (int) $input['paciente_id'] : null;

        $snapshot = $input['snapshot_paciente'] ?? null;
        if (!is_array($snapshot)) {
            $errors['snapshot_paciente'] = 'snapshot_paciente es obligatorio (objeto con nombre y sexo; fecha_nac opcional)';
            $snapshot = [];
        } else {
            foreach (['nombre', 'sexo'] as $f) {
                if (empty($snapshot[$f])) {
                    $errors["snapshot_paciente.$f"] = "Falta $f en snapshot_paciente";
                }
            }
            if (!isset($snapshot['fecha_nac']) || $snapshot['fecha_nac'] === '') {
                $snapshot['fecha_nac'] = null;
            }
            if (isset($snapshot['sexo']) && !in_array($snapshot['sexo'], self::SEXOS_VALIDOS, true)) {
                $errors['snapshot_paciente.sexo'] = "sexo debe ser 'M' o 'F'";
            }
        }

        $prioridad = $input['prioridad'] ?? 'rutina';
        if (!in_array($prioridad, self::PRIORIDADES_VALIDAS, true)) {
            $errors['prioridad'] = 'prioridad invalida (rutina|urgente|guardia)';
        }

        $fechaExtraccion = null;
        $fechaRealizadoRaw = trim((string) ($input['fecha_extraccion'] ?? ''));
        if ($fechaRealizadoRaw !== '') {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaRealizadoRaw);
            if ($dt === false || $dt->format('Y-m-d') !== $fechaRealizadoRaw) {
                $errors['fecha_extraccion'] = 'Formato esperado YYYY-MM-DD';
            } elseif ($fechaRealizadoRaw > date('Y-m-d')) {
                $errors['fecha_extraccion'] = 'La fecha de realizado no puede ser futura';
            } else {
                $fechaExtraccion = $fechaRealizadoRaw . ' 00:00:00';
            }
        }

        $items = $input['items'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            $errors['items'] = 'Debe enviar al menos un item';
        }

        if ($errors !== []) {
            throw new ValidationException('Errores de validacion', $errors);
        }

        $itemsResueltos = $this->resolverItems($items, $errors);

        if ($errors !== []) {
            throw new ValidationException('Errores de validacion en items', $errors);
        }

        // El acto bioquimico (determinacion solo_facturacion) se agrega a todo pedido.
        $idsPresentes = array_column($itemsResueltos, 'determinacion_id');
        foreach ($this->determinacionRepo->findSoloFacturacionIds() as $actoId) {
            if (!in_array($actoId, $idsPresentes, true)) {
                $itemsResueltos[] = ['determinacion_id' => $actoId, 'perfil_id' => null];
            }
        }

        $determinacionIds = array_unique(array_column($itemsResueltos, 'determinacion_id'));
        $determinacionesValidas = $this->determinacionRepo->findActivasByIds($determinacionIds);
        $idsValidos = array_map('intval', array_column($determinacionesValidas, 'id'));
        $ausentes = array_diff($determinacionIds, $idsValidos);
        if ($ausentes !== []) {
            throw new ValidationException('Determinaciones invalidas o inactivas', [
                'items' => 'IDs no validos: ' . implode(',', $ausentes),
            ]);
        }

        $precios = [];
        foreach ($determinacionesValidas as $d) {
            $precios[(int) $d['id']] = $d['precio'] !== null ? (float) $d['precio'] : null;
        }

        $year = (int) date('Y');
        $numero = (string) $this->pedidoRepo->getNextNumeroForYear($year);

        $pedido = new Pedido(
            id: null,
            numero: $numero,
            pacienteId: $pacienteId,
            medicoId: !empty($input['medico_id']) ? (int) $input['medico_id'] : null,
            medicoExterno: !empty($input['medico_externo']) ? (string) $input['medico_externo'] : null,
            obraSocialId: !empty($input['obra_social_id']) ? (int) $input['obra_social_id'] : null,
            numeroAfiliado: !empty($input['numero_afiliado']) ? (string) $input['numero_afiliado'] : null,
            diagnostico: !empty($input['diagnostico']) ? (string) $input['diagnostico'] : null,
            prioridad: $prioridad,
            estado: 'pendiente',
            esCritico: false,
            fechaSolicitud: date('Y-m-d H:i:s'),
            fechaExtraccion: $fechaExtraccion,
            fechaEntrega: null,
            usuarioRecepcionId: $usuarioId,
            usuarioAnulacionId: null,
            motivoAnulacion: null,
            observaciones: !empty($input['observaciones']) ? (string) $input['observaciones'] : null,
            snapshotPaciente: $snapshot,
        );

        $this->db->beginTransaction();
        try {
            $pedidoId = $this->pedidoRepo->insert($pedido);

            $obraSocialId = !empty($input['obra_social_id']) ? (int) $input['obra_social_id'] : null;

            $totalSeguro = 0.0;
            $totalPaciente = 0.0;
            $itemsCreados = 0;

            $hoy = date('Y-m-d');
            foreach ($itemsResueltos as $r) {
                $item = new PedidoItem(
                    id: null,
                    pedidoId: $pedidoId,
                    determinacionId: $r['determinacion_id'],
                    perfilId: $r['perfil_id'],
                    estado: 'pendiente',
                    precio: $precios[$r['determinacion_id']] ?? null,
                    observaciones: null,
                );
                $this->pedidoRepo->insertItem($pedidoId, $item);
                $itemsCreados++;
            }

            if ($obraSocialId !== null) {
                $totalSeguro = $this->pedidoRepo->calcularMontoSeguroUnits($pedidoId);
            } else {
                foreach ($itemsResueltos as $r) {
                    $monto = $this->arancelador->calcularMontoItem($r['determinacion_id'], null, $hoy);
                    $totalPaciente += $monto->total;
                }
            }

            $this->pedidoRepo->updateMontos($pedidoId, $totalSeguro, $totalPaciente);

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'crear',
                tablaAfectada: 'lab_pedidos',
                registroId: $pedidoId,
                valorAnterior: null,
                valorNuevo: [
                    'numero' => $numero,
                    'items' => $itemsCreados,
                    'monto_seguro' => $totalSeguro,
                    'monto_paciente' => $totalPaciente,
                ],
                contexto: 'Alta de pedido medico',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return [
            'id'          => $pedidoId,
            'numero'      => $numero,
            'items_count' => $itemsCreados,
        ];
    }

    /**
     * Trae un pedido completo con sus items.
     *
     * @return array<string,mixed>|null
     */
    public function obtener(int $id): ?array
    {
        $pedido = $this->pedidoRepo->findById($id);
        if ($pedido === null) {
            return null;
        }
        $pedido['items'] = $this->pedidoRepo->findItemsByPedidoId($id);
        return $pedido;
    }

    /**
     * Agrega determinaciones/perfiles a un pedido ya creado. Expande perfiles,
     * deduplica contra los items ya presentes (un pedido no repite analisis),
     * recalcula montos y estado. No toca pedidos entregados ni anulados.
     *
     * @param array<string,mixed> $input  { items: [{determinacion_id}|{perfil_id}] }
     * @return array{pedido_id:int, agregados:int, items_count:int}
     */
    public function agregarItems(int $pedidoId, array $input, int $usuarioId): array
    {
        $pedido = $this->pedidoRepo->findById($pedidoId);
        if ($pedido === null) {
            throw new ValidationException('Pedido no encontrado', ['id' => 'No existe el pedido o esta borrado'], 404);
        }
        $estado = (string) $pedido['estado'];
        if (in_array($estado, ['entregado', 'anulado'], true)) {
            throw new DomainException("El pedido esta '$estado', no admite cambios de items");
        }

        $items = $input['items'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            throw new ValidationException('Errores de validacion', ['items' => 'Debe enviar al menos un item']);
        }

        $errors = [];
        $itemsResueltos = $this->resolverItems($items, $errors);
        if ($errors !== []) {
            throw new ValidationException('Errores de validacion en items', $errors);
        }

        $yaPresentes = array_fill_keys($this->itemRepo->listarDeterminacionIdsByPedidoId($pedidoId), true);
        $nuevos = array_values(array_filter(
            $itemsResueltos,
            static fn ($r) => !isset($yaPresentes[$r['determinacion_id']])
        ));

        if ($nuevos === []) {
            return [
                'pedido_id'   => $pedidoId,
                'agregados'   => 0,
                'items_count' => count($this->pedidoRepo->findItemsByPedidoId($pedidoId)),
            ];
        }

        $detIds = array_unique(array_column($nuevos, 'determinacion_id'));
        $validas = $this->determinacionRepo->findActivasByIds($detIds);
        $idsValidos = array_map('intval', array_column($validas, 'id'));
        $ausentes = array_diff($detIds, $idsValidos);
        if ($ausentes !== []) {
            throw new ValidationException('Determinaciones invalidas o inactivas', [
                'items' => 'IDs no validos: ' . implode(',', $ausentes),
            ]);
        }
        $precios = [];
        foreach ($validas as $d) {
            $precios[(int) $d['id']] = $d['precio'] !== null ? (float) $d['precio'] : null;
        }

        $this->db->beginTransaction();
        try {
            $agregados = 0;
            foreach ($nuevos as $r) {
                $item = new PedidoItem(
                    id: null,
                    pedidoId: $pedidoId,
                    determinacionId: $r['determinacion_id'],
                    perfilId: $r['perfil_id'],
                    estado: 'pendiente',
                    precio: $precios[$r['determinacion_id']] ?? null,
                    observaciones: null,
                );
                $this->pedidoRepo->insertItem($pedidoId, $item);
                $agregados++;
            }

            $this->recalcularMontos($pedidoId);
            $this->recalcularEstadoPedido($pedidoId, $estado);

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'actualizar',
                tablaAfectada: 'lab_pedidos',
                registroId: $pedidoId,
                valorAnterior: null,
                valorNuevo: ['determinaciones' => array_values($detIds), 'agregados' => $agregados],
                contexto: 'Agregar items a pedido existente',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return [
            'pedido_id'   => $pedidoId,
            'agregados'   => $agregados,
            'items_count' => count($this->pedidoRepo->findItemsByPedidoId($pedidoId)),
        ];
    }

    /**
     * Saca (soft-delete) un item de un pedido. Si el item tenia un resultado
     * cargado, lo borra (queda en auditoria). Recalcula montos, estado y el
     * flag es_critico del pedido. No deja al pedido sin analisis clinicos.
     *
     * @return array{pedido_item_id:int, pedido_id:int, items_count:int}
     */
    public function quitarItem(int $pedidoItemId, int $usuarioId): array
    {
        if ($pedidoItemId <= 0) {
            throw new ValidationException('Errores de validacion', [
                'pedido_item_id' => 'pedido_item_id es obligatorio y debe ser > 0',
            ]);
        }

        $itemCtx = $this->itemRepo->findByIdConContexto($pedidoItemId);
        if ($itemCtx === null) {
            throw new ValidationException('Item no encontrado', [
                'pedido_item_id' => "Item $pedidoItemId no existe",
            ]);
        }

        $estadoPedido = (string) $itemCtx['pedido_estado'];
        if (in_array($estadoPedido, ['entregado', 'anulado'], true)) {
            throw new DomainException("El pedido esta '$estadoPedido', no admite cambios de items");
        }

        $pedidoId = (int) $itemCtx['pedido_id'];

        if (count($this->pedidoRepo->findItemsByPedidoId($pedidoId)) <= 1) {
            throw new DomainException('No se puede quitar el ultimo analisis del pedido. Anula el pedido en su lugar.');
        }

        $this->db->beginTransaction();
        try {
            $resultado = $this->resultadoRepo->findByPedidoItemId($pedidoItemId);
            if ($resultado !== null) {
                $this->resultadoRepo->deleteByPedidoItemId($pedidoItemId);
                $this->auditoria->log(
                    usuarioId: $usuarioId,
                    accion: 'eliminar',
                    tablaAfectada: 'lab_resultados',
                    registroId: (int) $resultado['id'],
                    valorAnterior: [
                        'pedido_item_id' => $pedidoItemId,
                        'valor_numerico' => $resultado['valor_numerico'],
                        'valor_texto'    => $resultado['valor_texto'],
                        'es_anormal'     => (int) $resultado['es_anormal'],
                        'es_critico'     => (int) $resultado['es_critico'],
                    ],
                    valorNuevo: null,
                    contexto: 'Resultado borrado al quitar el analisis del pedido',
                );
            }

            $this->itemRepo->softDelete($pedidoItemId);

            $this->recalcularMontos($pedidoId);
            $this->recalcularEstadoPedido($pedidoId, $estadoPedido);

            $hayCritico = false;
            foreach ($this->resultadoRepo->findByPedidoId($pedidoId) as $r) {
                if ((int) ($r['es_critico'] ?? 0) === 1) {
                    $hayCritico = true;
                    break;
                }
            }
            $this->pedidoRepo->updateEsCritico($pedidoId, $hayCritico);

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'eliminar',
                tablaAfectada: 'lab_pedido_items',
                registroId: $pedidoItemId,
                valorAnterior: [
                    'determinacion_id'     => $itemCtx['determinacion_id'] ?? null,
                    'determinacion_nombre' => $itemCtx['determinacion_nombre'] ?? null,
                ],
                valorNuevo: null,
                contexto: 'Quitar analisis de pedido existente',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return [
            'pedido_item_id' => $pedidoItemId,
            'pedido_id'      => $pedidoId,
            'items_count'    => count($this->pedidoRepo->findItemsByPedidoId($pedidoId)),
        ];
    }

    /**
     * Recalcula y persiste monto_seguro / monto_paciente del pedido segun su
     * cobertura. Con obra social = unidades NBU; particular = suma de precios.
     */
    private function recalcularMontos(int $pedidoId): void
    {
        $pedido = $this->pedidoRepo->findById($pedidoId);
        if ($pedido === null) {
            return;
        }
        $obraSocialId = !empty($pedido['obra_social_id']) ? (int) $pedido['obra_social_id'] : null;

        if ($obraSocialId !== null) {
            $totalSeguro = $this->pedidoRepo->calcularMontoSeguroUnits($pedidoId);
            $this->pedidoRepo->updateMontos($pedidoId, $totalSeguro, 0.0);
            return;
        }

        $hoy = date('Y-m-d');
        $totalPaciente = 0.0;
        foreach ($this->itemRepo->listarDeterminacionIdsByPedidoId($pedidoId) as $detId) {
            $totalPaciente += $this->arancelador->calcularMontoItem($detId, null, $hoy)->total;
        }
        $this->pedidoRepo->updateMontos($pedidoId, 0.0, $totalPaciente);
    }

    /**
     * Reconcilia el estado del pedido a partir del estado de sus items:
     * todos cargados => completo; alguno cargado => parcial; ninguno => pendiente.
     */
    private function recalcularEstadoPedido(int $pedidoId, string $estadoActual): void
    {
        if (in_array($estadoActual, ['entregado', 'anulado'], true)) {
            return;
        }
        $estados = $this->itemRepo->listarEstadosByPedidoId($pedidoId);
        $todosCargados = $estados !== [] && array_unique($estados) === ['cargado'];
        $hayCargados = in_array('cargado', $estados, true);
        $nuevo = $todosCargados ? 'completo' : ($hayCargados ? 'parcial' : 'pendiente');
        if ($nuevo !== $estadoActual) {
            $this->pedidoRepo->updateEstado($pedidoId, $nuevo);
        }
    }

    /**
     * Convierte items de input (que pueden ser determinacion_id directa
     * o perfil_id que se expande) en un set deduplicado de pares
     * { determinacion_id, perfil_id }.
     *
     * @param array<int,mixed> $items
     * @param array<string,string> $errors  (out)
     * @return array<int,array{determinacion_id:int, perfil_id:int|null}>
     */
    private function resolverItems(array $items, array &$errors): array
    {
        $resueltos = [];
        $vistos = [];

        foreach ($items as $idx => $item) {
            if (!is_array($item)) {
                $errors["items[$idx]"] = 'Item invalido (debe ser un objeto)';
                continue;
            }

            $detId    = !empty($item['determinacion_id']) ? (int) $item['determinacion_id'] : null;
            $perfilId = !empty($item['perfil_id'])        ? (int) $item['perfil_id']        : null;

            if ($detId === null && $perfilId === null) {
                $errors["items[$idx]"] = 'Falta determinacion_id o perfil_id';
                continue;
            }
            if ($detId !== null && $perfilId !== null) {
                $errors["items[$idx]"] = 'Use determinacion_id o perfil_id, no ambos';
                continue;
            }

            if ($detId !== null) {
                if (isset($vistos[$detId])) {
                    continue;
                }
                $vistos[$detId] = true;
                $resueltos[] = ['determinacion_id' => $detId, 'perfil_id' => null];
                continue;
            }

            $detIds = $this->perfilRepo->getDeterminacionIdsByPerfilId($perfilId);
            if ($detIds === []) {
                $errors["items[$idx]"] = "Perfil $perfilId no existe o no tiene determinaciones activas";
                continue;
            }
            foreach ($detIds as $did) {
                if (isset($vistos[$did])) {
                    continue;
                }
                $vistos[$did] = true;
                $resueltos[] = ['determinacion_id' => $did, 'perfil_id' => $perfilId];
            }
        }

        return $resueltos;
    }

    // =================================================================
    // Sub-proyecto 2: listado, anulacion y eliminacion de pedidos
    // =================================================================

    /**
     * Busqueda paginada de pedidos.
     *
     * @param array<string,mixed> $filtrosCrudos
     * @return array{
     *     pedidos: array<int,array<string,mixed>>,
     *     total: int,
     *     page: int,
     *     por_pagina: int,
     *     total_paginas: int
     * }
     */
    public function buscar(array $filtrosCrudos): array
    {
        $filtros = $this->validarFiltrosBuscar($filtrosCrudos);
        $page    = $this->clampPage($filtrosCrudos['page'] ?? 1);
        $offset  = ($page - 1) * self::POR_PAGINA;

        $r = $this->pedidoRepo->buscar($filtros, self::POR_PAGINA, $offset);

        $totalPaginas = (int) max(1, (int) ceil($r['total'] / self::POR_PAGINA));

        return [
            'pedidos'       => $r['pedidos'],
            'total'         => $r['total'],
            'page'          => $page,
            'por_pagina'    => self::POR_PAGINA,
            'total_paginas' => $totalPaginas,
        ];
    }

    /**
     * Anula un pedido (cambia estado a 'anulado'). Audita.
     *
     * @throws ValidationException si motivo invalido o estado no permite anular.
     */
    public function anular(int $id, string $motivo, ?int $usuarioId): void
    {
        $errores = [];
        if ($id <= 0) {
            $errores['id'] = 'Debe ser un entero positivo';
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            $errores['motivo'] = 'El motivo de anulacion es obligatorio';
        }
        if (!empty($errores)) {
            throw new ValidationException('Datos invalidos', $errores);
        }

        $actual = $this->pedidoRepo->findById($id);
        if ($actual === null) {
            throw new ValidationException('Pedido no encontrado', ['id' => 'No existe el pedido o esta borrado'], 404);
        }
        if (in_array($actual['estado'], ['entregado', 'anulado'], true)) {
            throw new ValidationException(
                'Estado actual no permite anular',
                ['estado' => "El pedido esta '{$actual['estado']}'"],
                409
            );
        }

        $ok = $this->pedidoRepo->anular($id, $motivo, $usuarioId);
        if (!$ok) {
            throw new ValidationException('No se pudo anular el pedido', ['id' => 'La operacion no aplico cambios'], 409);
        }

        $this->auditoria->log(
            usuarioId: $usuarioId,
            accion: 'anular',
            tablaAfectada: 'lab_pedidos',
            registroId: $id,
            valorAnterior: ['estado' => $actual['estado']],
            valorNuevo: ['estado' => 'anulado', 'motivo' => $motivo],
        );
    }

    /**
     * Soft-delete del pedido. Audita.
     *
     * @throws ValidationException
     */
    public function eliminar(int $id, ?int $usuarioId): void
    {
        if ($id <= 0) {
            throw new ValidationException('Datos invalidos', ['id' => 'Debe ser un entero positivo']);
        }

        $actual = $this->pedidoRepo->findById($id);
        if ($actual === null) {
            throw new ValidationException('Pedido no encontrado', ['id' => 'No existe el pedido o ya esta borrado'], 404);
        }

        $ok = $this->pedidoRepo->softDelete($id);
        if (!$ok) {
            throw new ValidationException('No se pudo eliminar', ['id' => 'La operacion no aplico cambios'], 409);
        }

        $this->auditoria->log(
            usuarioId: $usuarioId,
            accion: 'eliminar',
            tablaAfectada: 'lab_pedidos',
            registroId: $id,
            valorAnterior: $actual,
        );
    }

    /**
     * @param array<string,mixed> $crudos
     * @return array<string,mixed>
     */
    private function validarFiltrosBuscar(array $crudos): array
    {
        $errores = [];
        $out = [];

        // Strings que se trimean.
        foreach (['numero', 'medico', 'q'] as $k) {
            if (isset($crudos[$k]) && trim((string) $crudos[$k]) !== '') {
                $out[$k] = trim((string) $crudos[$k]);
            }
        }

        // estado
        if (isset($crudos['estado']) && $crudos['estado'] !== '') {
            if (!in_array($crudos['estado'], self::ESTADOS_VALIDOS, true)) {
                $errores['estado'] = 'Estado invalido';
            } else {
                $out['estado'] = $crudos['estado'];
            }
        }

        // prioridad
        if (isset($crudos['prioridad']) && $crudos['prioridad'] !== '') {
            if (!in_array($crudos['prioridad'], self::PRIORIDADES_VALIDAS, true)) {
                $errores['prioridad'] = 'Prioridad invalida';
            } else {
                $out['prioridad'] = $crudos['prioridad'];
            }
        }

        // ints positivos
        foreach (['paciente_id', 'obra_social_id', 'medico_id'] as $k) {
            if (isset($crudos[$k]) && $crudos[$k] !== '') {
                if (!is_numeric($crudos[$k]) || (int) $crudos[$k] <= 0) {
                    $errores[$k] = 'Debe ser un id valido';
                } else {
                    $out[$k] = (int) $crudos[$k];
                }
            }
        }

        // Fechas YYYY-MM-DD
        foreach (['fecha_solicitud_desde', 'fecha_solicitud_hasta', 'fecha_entrega_desde', 'fecha_entrega_hasta'] as $k) {
            if (isset($crudos[$k]) && trim((string) $crudos[$k]) !== '') {
                $f = trim((string) $crudos[$k]);
                $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $f);
                if ($dt === false || $dt->format('Y-m-d') !== $f) {
                    $errores[$k] = 'Formato esperado YYYY-MM-DD';
                } else {
                    $out[$k] = $f;
                }
            }
        }

        // Flags booleanos.
        if (!empty($crudos['solo_criticos'])) {
            $out['solo_criticos'] = true;
        }
        if (!empty($crudos['incluir_anulados'])) {
            $out['incluir_anulados'] = true;
        }

        // Orden whitelist.
        $orden = isset($crudos['orden']) ? (string) $crudos['orden'] : '';
        $out['orden'] = in_array($orden, self::ORDENES_VALIDOS, true)
            ? $orden
            : self::ORDEN_DEFAULT;

        if (!empty($errores)) {
            throw new ValidationException('Filtros invalidos', $errores);
        }

        return $out;
    }

    private function clampPage(mixed $crudo): int
    {
        if ($crudo === null || $crudo === '') {
            return 1;
        }
        $n = is_numeric($crudo) ? (int) $crudo : 1;
        return max(1, $n);
    }

}
