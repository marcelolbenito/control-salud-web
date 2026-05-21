<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Repositories\DeterminacionRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\PerfilRepository;
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
 *   - Generacion de numero P-YYYY-NNNNN con reseteo anual.
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

        $pacienteId = isset($input['paciente_id']) ? (int) $input['paciente_id'] : 0;
        if ($pacienteId <= 0) {
            $errors['paciente_id'] = 'paciente_id es obligatorio y debe ser > 0';
        }

        $snapshot = $input['snapshot_paciente'] ?? null;
        if (!is_array($snapshot)) {
            $errors['snapshot_paciente'] = 'snapshot_paciente es obligatorio (objeto con nombre, sexo, fecha_nac)';
            $snapshot = [];
        } else {
            foreach (['nombre', 'sexo', 'fecha_nac'] as $f) {
                if (empty($snapshot[$f])) {
                    $errors["snapshot_paciente.$f"] = "Falta $f en snapshot_paciente";
                }
            }
            if (isset($snapshot['sexo']) && !in_array($snapshot['sexo'], self::SEXOS_VALIDOS, true)) {
                $errors['snapshot_paciente.sexo'] = "sexo debe ser 'M' o 'F'";
            }
        }

        $prioridad = $input['prioridad'] ?? 'rutina';
        if (!in_array($prioridad, self::PRIORIDADES_VALIDAS, true)) {
            $errors['prioridad'] = 'prioridad invalida (rutina|urgente|guardia)';
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
        $numero = sprintf(
            'P-%04d-%05d',
            $year,
            $this->pedidoRepo->getNextNumeroForYear($year)
        );

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
            fechaExtraccion: null,
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
                $monto = $this->arancelador->calcularMontoItem($r['determinacion_id'], $obraSocialId, $hoy);

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

                if ($obraSocialId !== null) {
                    $totalSeguro += $monto->total;
                } else {
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
