<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Models\Resultado;
use App\Repositories\PedidoItemRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ResultadoRepository;
use App\Repositories\ValorReferenciaRepository;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * Logica de carga de resultados.
 *
 * SP9: se elimino el paso de validacion. Un resultado se carga una vez y
 * queda firme. Es editable libremente; cada edicion queda registrada en
 * lab_auditoria con before/after JSON (regla 6).
 *
 * Reglas que aplica:
 *   - Marcado de es_anormal contra rango por sexo+edad y de es_critico contra
 *     valor_critico_min/max de la determinacion.
 *   - El rango aplicado se snapshotea en el resultado para que cambios
 *     posteriores en lab_valores_referencia no alteren historicos.
 *   - Si algun resultado del pedido es critico, pedido.es_critico = 1.
 *   - Cuando todos los items de un pedido estan cargados, pedido.estado='completo'.
 *   - Auditoria de toda accion.
 *   - Atomicidad transaccional.
 */
final class ResultadoService
{
    public function __construct(
        private PDO $db,
        private ResultadoRepository $resultadoRepo,
        private PedidoItemRepository $itemRepo,
        private PedidoRepository $pedidoRepo,
        private ValorReferenciaRepository $rangoRepo,
        private AuditoriaService $auditoria,
    ) {
    }

    /**
     * Carga (o re-carga) un resultado para un item. La re-escritura es libre:
     * cada edicion sobrescribe el valor anterior y queda en lab_auditoria.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function cargar(array $input, int $usuarioId): array
    {
        $errors = [];

        $pedidoItemId = isset($input['pedido_item_id']) ? (int) $input['pedido_item_id'] : 0;
        if ($pedidoItemId <= 0) {
            $errors['pedido_item_id'] = 'pedido_item_id es obligatorio y debe ser > 0';
        }

        $valorNumerico = $this->normalizarValorNumerico($input['valor_numerico'] ?? null, $errors);
        $valorTexto    = isset($input['valor_texto']) && $input['valor_texto'] !== ''
            ? (string) $input['valor_texto']
            : null;

        if ($valorNumerico === null && $valorTexto === null) {
            $errors['valor'] = 'Debe enviar valor_numerico o valor_texto';
        }

        if ($errors !== []) {
            throw new ValidationException('Errores de validacion', $errors);
        }

        $itemCtx = $this->itemRepo->findByIdConContexto($pedidoItemId);
        if ($itemCtx === null) {
            throw new ValidationException('Item no encontrado', [
                'pedido_item_id' => "Item $pedidoItemId no existe",
            ]);
        }

        $estadoPedido = (string) $itemCtx['pedido_estado'];
        if (in_array($estadoPedido, ['anulado', 'entregado'], true)) {
            throw new DomainException(
                "El pedido esta '$estadoPedido', no admite carga de resultados"
            );
        }

        $estadoItem = (string) $itemCtx['item_estado'];
        if ($estadoItem === 'anulado') {
            throw new DomainException("El item esta '$estadoItem', no admite carga");
        }

        $existente = $this->resultadoRepo->findByPedidoItemId($pedidoItemId);

        $tipoResultado = (string) $itemCtx['tipo_resultado'];
        if ($tipoResultado === 'numerico' && $valorNumerico === null) {
            throw new ValidationException('Errores de validacion', [
                'valor_numerico' => 'Esta determinacion requiere valor numerico',
            ]);
        }

        $rango = null;
        $sexo = '';
        $edadDias = 0;
        if ($tipoResultado === 'numerico' && $valorNumerico !== null) {
            $snapshot = is_array($itemCtx['snapshot_paciente']) ? $itemCtx['snapshot_paciente'] : [];
            $sexo = (string) ($snapshot['sexo'] ?? '');
            $fechaNac = (string) ($snapshot['fecha_nac'] ?? '');
            $edadDias = $this->calcularEdadDias($fechaNac, (string) ($itemCtx['fecha_extraccion'] ?? ''));

            if ($sexo !== '' && $fechaNac !== '') {
                $rango = $this->rangoRepo->findRangoAplicable(
                    determinacionId: (int) $itemCtx['determinacion_id'],
                    sexo: $sexo,
                    edadDias: $edadDias,
                );
            }
        }

        $esAnormal = $this->calcularAnormal($valorNumerico, $rango);
        $esCritico = $this->calcularCritico(
            $valorNumerico,
            $itemCtx['valor_critico_min'] ?? null,
            $itemCtx['valor_critico_max'] ?? null,
        );

        $ahora = date('Y-m-d H:i:s');

        $this->db->beginTransaction();
        try {
            if ($existente === null) {
                $resultado = new Resultado(
                    id: null,
                    pedidoItemId: $pedidoItemId,
                    valorNumerico: $valorNumerico,
                    valorTexto: $valorTexto,
                    unidad: (string) $itemCtx['determinacion_unidad'],
                    esAnormal: $esAnormal,
                    esCritico: $esCritico,
                    estado: 'cargado',
                    valorReferenciaMin: $rango !== null && isset($rango['valor_min'])
                        ? (float) $rango['valor_min']
                        : null,
                    valorReferenciaMax: $rango !== null && isset($rango['valor_max'])
                        ? (float) $rango['valor_max']
                        : null,
                    textoReferencia: $rango['texto_referencia'] ?? null,
                    observaciones: !empty($input['observaciones'])
                        ? (string) $input['observaciones']
                        : null,
                    usuarioCargaId: $usuarioId,
                    fechaCarga: $ahora,
                );
                $resultadoId = $this->resultadoRepo->insert($resultado);
                $accionAuditoria = 'crear';
                $valorAnteriorAud = null;
            } else {
                // Re-escritura libre: sobrescribimos sin restriccion.
                $resultado = new Resultado(
                    id: (int) $existente['id'],
                    pedidoItemId: $pedidoItemId,
                    valorNumerico: $valorNumerico,
                    valorTexto: $valorTexto,
                    unidad: (string) $itemCtx['determinacion_unidad'],
                    esAnormal: $esAnormal,
                    esCritico: $esCritico,
                    estado: 'cargado',
                    valorReferenciaMin: $rango !== null && isset($rango['valor_min'])
                        ? (float) $rango['valor_min']
                        : null,
                    valorReferenciaMax: $rango !== null && isset($rango['valor_max'])
                        ? (float) $rango['valor_max']
                        : null,
                    textoReferencia: $rango['texto_referencia'] ?? null,
                    observaciones: !empty($input['observaciones'])
                        ? (string) $input['observaciones']
                        : ($existente['observaciones'] ?? null),
                    usuarioCargaId: $usuarioId,
                    fechaCarga: $ahora,
                );
                $this->resultadoRepo->update($resultado);
                $resultadoId = (int) $existente['id'];
                $accionAuditoria = 'actualizar';
                $valorAnteriorAud = [
                    'valor_numerico' => $existente['valor_numerico'],
                    'valor_texto'    => $existente['valor_texto'],
                    'es_anormal'     => (int) $existente['es_anormal'],
                    'es_critico'     => (int) $existente['es_critico'],
                ];
            }

            if ($estadoItem !== 'cargado') {
                $this->itemRepo->updateEstado($pedidoItemId, 'cargado');
            }

            if ($esCritico) {
                $this->pedidoRepo->updateEsCritico((int) $itemCtx['pedido_id'], true);
            }

            // Si todos los items del pedido quedan en 'cargado', transicionar
            // el pedido a 'completo'. SP9: antes esto ocurria al validar.
            $pedidoId = (int) $itemCtx['pedido_id'];
            $estados = $this->itemRepo->listarEstadosByPedidoId($pedidoId);
            $todosCargados = $estados !== [] && array_unique($estados) === ['cargado'];
            $pedidoCompletado = false;
            if ($todosCargados && $estadoPedido !== 'completo') {
                $this->pedidoRepo->updateEstado($pedidoId, 'completo');
                $this->auditoria->log(
                    usuarioId: $usuarioId,
                    accion: 'cambiar_estado',
                    tablaAfectada: 'lab_pedidos',
                    registroId: $pedidoId,
                    valorAnterior: ['estado' => $estadoPedido],
                    valorNuevo: ['estado' => 'completo'],
                    contexto: 'Todos los items cargados',
                );
                $pedidoCompletado = true;
            }

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: $accionAuditoria,
                tablaAfectada: 'lab_resultados',
                registroId: $resultadoId,
                valorAnterior: $valorAnteriorAud,
                valorNuevo: [
                    'pedido_item_id' => $pedidoItemId,
                    'valor_numerico' => $valorNumerico,
                    'valor_texto'    => $valorTexto,
                    'es_anormal'     => (int) $esAnormal,
                    'es_critico'     => (int) $esCritico,
                ],
                contexto: $accionAuditoria === 'crear' ? 'Carga de resultado' : 'Re-carga (edicion libre) de resultado',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return [
            'id'                => $resultadoId,
            'pedido_item_id'    => $pedidoItemId,
            'estado'            => 'cargado',
            'es_anormal'        => $esAnormal,
            'es_critico'        => $esCritico,
            'pedido_completado' => $pedidoCompletado,
        ];
    }

    /**
     * Quita el resultado de un item mal cargado.
     *
     * @return array<string,mixed>
     */
    public function quitar(int $pedidoItemId, int $usuarioId): array
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
        if (in_array($estadoPedido, ['anulado', 'entregado'], true)) {
            throw new DomainException(
                "El pedido esta '$estadoPedido', no se puede quitar el resultado"
            );
        }

        $existente = $this->resultadoRepo->findByPedidoItemId($pedidoItemId);
        if ($existente === null) {
            throw new ValidationException('No hay resultado para quitar', [
                'pedido_item_id' => 'Este item no tiene un resultado cargado',
            ]);
        }

        $pedidoId = (int) $itemCtx['pedido_id'];

        $this->db->beginTransaction();
        try {
            $this->resultadoRepo->deleteByPedidoItemId($pedidoItemId);
            $this->itemRepo->updateEstado($pedidoItemId, 'pendiente');

            $estados = $this->itemRepo->listarEstadosByPedidoId($pedidoId);
            $hayCargados = in_array('cargado', $estados, true);
            $nuevoEstado = $hayCargados ? 'parcial' : 'pendiente';
            if ($nuevoEstado !== $estadoPedido) {
                $this->pedidoRepo->updateEstado($pedidoId, $nuevoEstado);
            }

            $restantes = $this->resultadoRepo->findByPedidoId($pedidoId);
            $hayCritico = false;
            foreach ($restantes as $r) {
                if ((int) ($r['es_critico'] ?? 0) === 1) {
                    $hayCritico = true;
                    break;
                }
            }
            $this->pedidoRepo->updateEsCritico($pedidoId, $hayCritico);

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'eliminar',
                tablaAfectada: 'lab_resultados',
                registroId: (int) $existente['id'],
                valorAnterior: [
                    'pedido_item_id' => $pedidoItemId,
                    'valor_numerico' => $existente['valor_numerico'],
                    'valor_texto'    => $existente['valor_texto'],
                    'es_anormal'     => (int) $existente['es_anormal'],
                    'es_critico'     => (int) $existente['es_critico'],
                ],
                valorNuevo: null,
                contexto: 'Quitar resultado mal cargado',
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
            'estado_pedido'  => $nuevoEstado,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listarPorPedido(int $pedidoId): array
    {
        return $this->resultadoRepo->findByPedidoId($pedidoId);
    }

    /**
     * @param array<string,string> $errors
     */
    private function normalizarValorNumerico(mixed $raw, array &$errors): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (!is_numeric($raw)) {
            $errors['valor_numerico'] = 'valor_numerico debe ser numerico';
            return null;
        }
        return (float) $raw;
    }

    private function calcularAnormal(?float $valor, ?array $rango): bool
    {
        if ($valor === null || $rango === null) {
            return false;
        }
        $min = $rango['valor_min'] ?? null;
        $max = $rango['valor_max'] ?? null;
        if ($min !== null && $valor < (float) $min) {
            return true;
        }
        if ($max !== null && $valor > (float) $max) {
            return true;
        }
        return false;
    }

    private function calcularCritico(?float $valor, mixed $criticoMin, mixed $criticoMax): bool
    {
        if ($valor === null) {
            return false;
        }
        if ($criticoMin !== null && $valor < (float) $criticoMin) {
            return true;
        }
        if ($criticoMax !== null && $valor > (float) $criticoMax) {
            return true;
        }
        return false;
    }

    /**
     * Edad en dias entre fecha_nac y fecha_extraccion (o hoy si extraccion null).
     * Devuelve 0 si las fechas no parsean.
     */
    private function calcularEdadDias(string $fechaNac, string $fechaExtraccion): int
    {
        if ($fechaNac === '') {
            return 0;
        }
        try {
            $nac = new DateTimeImmutable($fechaNac);
            $ref = $fechaExtraccion !== ''
                ? new DateTimeImmutable($fechaExtraccion)
                : new DateTimeImmutable();
        } catch (\Throwable) {
            return 0;
        }
        $diff = $nac->diff($ref);
        return $diff->invert === 1 ? 0 : (int) $diff->days;
    }
}
