<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\LoteOs;
use App\Repositories\LoteOsRepository;
use App\Repositories\PagoRepository;
use App\Repositories\PedidoRepository;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * Orquesta la facturacion por OS (SP8).
 *
 * Operaciones:
 *   - generar(): crea lote en estado 'abierto' con N pedidos elegibles.
 *   - quitarPedido(): saca un pedido de un lote abierto.
 *   - anular(): marca lote abierto como anulado.
 *   - cobrar(): genera N pagos + marca pedidos estado_seguro='P' (transaccional).
 *   - listar/ver: queries de lectura.
 */
final class FacturacionOsService
{
    private const MEDIOS_PAGO_VALIDOS = ['efectivo', 'tarjeta', 'transferencia', 'cheque', 'otro'];

    public function __construct(
        private PDO $db,
        private LoteOsRepository $loteRepo,
        private PedidoRepository $pedidoRepo,
        private PagoRepository $pagoRepo,
        private AuditoriaService $auditoria,
    ) {
    }

    /**
     * Genera un lote en estado 'abierto'.
     *
     * @param array{obra_social_id:int|string, fecha_desde:string, fecha_hasta:string,
     *              pedido_ids:array<int,int|string>, observaciones?:?string} $input
     * @return array{id:int, numero:string, monto_total:float, cantidad_pedidos:int}
     * @throws ValidationException 422 (input invalido) | 409 (regla de negocio)
     */
    public function generar(array $input, ?int $usuarioId): array
    {
        $errors = [];
        $obraSocialId = isset($input['obra_social_id']) ? (int) $input['obra_social_id'] : 0;
        if ($obraSocialId <= 0) {
            $errors['obra_social_id'] = 'Debe ser > 0';
        }

        $fechaDesde = (string) ($input['fecha_desde'] ?? '');
        $fechaHasta = (string) ($input['fecha_hasta'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $errors['fecha_desde'] = 'Formato YYYY-MM-DD requerido';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $errors['fecha_hasta'] = 'Formato YYYY-MM-DD requerido';
        }
        if ($errors === [] && $fechaDesde > $fechaHasta) {
            $errors['fecha_hasta'] = 'fecha_hasta debe ser >= fecha_desde';
        }

        $pedidoIds = array_values(array_unique(array_map('intval', (array) ($input['pedido_ids'] ?? []))));
        $pedidoIds = array_values(array_filter($pedidoIds, static fn(int $i): bool => $i > 0));
        if ($pedidoIds === []) {
            $errors['pedido_ids'] = 'Debe contener al menos un pedido';
        }

        if ($errors !== []) {
            throw new ValidationException('Errores de validacion', $errors, 422);
        }

        $observaciones = isset($input['observaciones']) ? (string) $input['observaciones'] : null;

        // Re-validacion 409: doble-loteo
        $yaEnOtroLote = $this->loteRepo->findPedidosYaEnLote($pedidoIds);
        if ($yaEnOtroLote !== []) {
            throw new ValidationException(
                'Pedidos ya incluidos en otro lote: ' . implode(', ', $yaEnOtroLote),
                ['pedido_ids' => 'Algun pedido ya esta en otro lote abierto/cobrado'],
                409
            );
        }

        // Fecha del lote: se usa para resolver la vigencia NBU activa.
        // Default: hoy. El monto_seguro se recalcula con la tarifa vigente a ese dia.
        $fechaLote = date('Y-m-d');

        // Re-validacion 409: OS distinta y elegibilidad
        $snapshots = []; // [pedido_id => monto_seguro recalculado]
        foreach ($pedidoIds as $pid) {
            $row = $this->pedidoRepo->findById($pid);
            if ($row === null) {
                throw new ValidationException("Pedido $pid no existe", ['pedido_ids' => "Pedido $pid no existe"], 409);
            }
            if ((int) $row['obra_social_id'] !== $obraSocialId) {
                throw new ValidationException(
                    "Pedido $pid no pertenece a la OS seleccionada",
                    ['pedido_ids' => "Pedido $pid tiene otra OS"],
                    409
                );
            }
            if (!in_array($row['estado_seguro'], ['A', 'F'], true)) {
                throw new ValidationException(
                    "Pedido $pid ya no es elegible (estado_seguro = {$row['estado_seguro']})",
                    ['pedido_ids' => "Pedido $pid ya no es elegible"],
                    409
                );
            }
            $monto = $this->pedidoRepo->recalcularMontoSeguroAlVuelo($pid, $fechaLote);
            if ($monto <= 0) {
                throw new ValidationException(
                    "Pedido $pid tiene monto_seguro = 0 con la vigencia NBU del $fechaLote",
                    ['pedido_ids' => "Pedido $pid sin monto"],
                    409
                );
            }
            $snapshots[$pid] = $monto;
        }

        $year = (int) (new DateTimeImmutable($fechaDesde))->format('Y');
        $next = $this->loteRepo->getNextNumeroForYear($year);
        $numero = sprintf('L-%04d-%05d', $year, $next);

        $this->db->beginTransaction();
        try {
            $loteId = $this->loteRepo->insert(
                new LoteOs(
                    id: null,
                    numero: $numero,
                    obraSocialId: $obraSocialId,
                    fechaDesde: $fechaDesde,
                    fechaHasta: $fechaHasta,
                    estado: 'abierto',
                    montoTotal: array_sum($snapshots),
                    cantidadPedidos: count($snapshots),
                    observaciones: $observaciones,
                ),
                $usuarioId
            );
            foreach ($snapshots as $pid => $monto) {
                $this->loteRepo->insertPivot($loteId, $pid, $monto);
            }
            $this->loteRepo->recalcularTotales($loteId);

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'crear',
                tablaAfectada: 'lab_lotes_os',
                registroId: $loteId,
                valorNuevo: [
                    'numero' => $numero,
                    'obra_social_id' => $obraSocialId,
                    'cantidad_pedidos' => count($snapshots),
                    'monto_total' => array_sum($snapshots),
                ],
                contexto: 'generar_lote',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'id' => $loteId,
            'numero' => $numero,
            'monto_total' => array_sum($snapshots),
            'cantidad_pedidos' => count($snapshots),
        ];
    }

    /**
     * Marca un item del pedido como excluido del lote (no cubre OS). El monto del
     * item se descuenta del monto_seguro del pedido y se suma a monto_paciente.
     *
     * @throws ValidationException 404 si lote/item no existe, 409 si lote no abierto.
     */
    public function excluirItem(int $loteId, int $pedidoItemId, ?int $usuarioId, ?string $motivo = null): array
    {
        $lote = $this->validarLoteAbierto($loteId);

        $pedidoId = $this->loteRepo->findPedidoIdDeItemEnLote($loteId, $pedidoItemId);
        if ($pedidoId === null) {
            throw new ValidationException(
                'Item no pertenece al lote',
                ['pedido_item_id' => 'No esta en este lote'],
                404
            );
        }

        $fechaLote = $lote->fechaCobro ?? date('Y-m-d');

        $this->db->beginTransaction();
        try {
            $this->loteRepo->excluirItem($loteId, $pedidoItemId, $usuarioId, $motivo);
            $totales = $this->pedidoRepo->recalcularPedidoConExclusiones($pedidoId, $loteId, $fechaLote);
            $this->loteRepo->updatePivotMonto($loteId, $pedidoId, $totales['monto_seguro']);
            $this->loteRepo->recalcularTotales($loteId);

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'actualizar',
                tablaAfectada: 'lab_lote_pedido_item_excluido',
                registroId: $pedidoItemId,
                valorNuevo: [
                    'lote_id' => $loteId,
                    'pedido_item_id' => $pedidoItemId,
                    'motivo' => $motivo,
                ],
                contexto: 'excluir_item_lote',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'pedido_id' => $pedidoId,
            'monto_seguro' => $totales['monto_seguro'],
            'monto_paciente' => $totales['monto_paciente'],
        ];
    }

    /**
     * Vuelve a incluir un item antes excluido. Recalcula montos del pedido y del lote.
     *
     * @throws ValidationException 404 si item no esta excluido, 409 si lote no abierto.
     */
    public function incluirItem(int $loteId, int $pedidoItemId, ?int $usuarioId): array
    {
        $lote = $this->validarLoteAbierto($loteId);

        $pedidoId = $this->loteRepo->findPedidoIdDeItemEnLote($loteId, $pedidoItemId);
        if ($pedidoId === null) {
            throw new ValidationException(
                'Item no pertenece al lote',
                ['pedido_item_id' => 'No esta en este lote'],
                404
            );
        }

        $fechaLote = $lote->fechaCobro ?? date('Y-m-d');

        $this->db->beginTransaction();
        try {
            $this->loteRepo->incluirItem($loteId, $pedidoItemId);
            $totales = $this->pedidoRepo->recalcularPedidoConExclusiones($pedidoId, $loteId, $fechaLote);
            $this->loteRepo->updatePivotMonto($loteId, $pedidoId, $totales['monto_seguro']);
            $this->loteRepo->recalcularTotales($loteId);

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'actualizar',
                tablaAfectada: 'lab_lote_pedido_item_excluido',
                registroId: $pedidoItemId,
                valorNuevo: ['lote_id' => $loteId, 'pedido_item_id' => $pedidoItemId, 'incluido' => true],
                contexto: 'incluir_item_lote',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'pedido_id' => $pedidoId,
            'monto_seguro' => $totales['monto_seguro'],
            'monto_paciente' => $totales['monto_paciente'],
        ];
    }

    /**
     * Lista los items de un pedido en el contexto de un lote (con flag excluido y monto).
     *
     * @return array<int,array<string,mixed>>
     */
    public function listarItemsDePedidoEnLote(int $loteId, int $pedidoId): array
    {
        $lote = $this->loteRepo->findById($loteId);
        if ($lote === null) {
            throw new ValidationException('Lote no encontrado', ['lote_id' => 'No existe'], 404);
        }
        $fecha = $lote->fechaCobro ?? date('Y-m-d');
        return $this->pedidoRepo->findItemsConExclusionDeLote($pedidoId, $loteId, $fecha);
    }

    private function validarLoteAbierto(int $loteId): \App\Models\LoteOs
    {
        $lote = $this->loteRepo->findById($loteId);
        if ($lote === null) {
            throw new ValidationException('Lote no encontrado', ['lote_id' => 'No existe'], 404);
        }
        if ($lote->estado !== 'abierto') {
            throw new ValidationException(
                "Solo se pueden modificar lotes abiertos (estado actual: {$lote->estado})",
                ['lote_id' => 'Lote no abierto'],
                409
            );
        }
        return $lote;
    }

    /**
     * Quita un pedido de un lote abierto. Recalcula totales.
     *
     * @throws ValidationException 404 si pedido no esta en el lote, 409 si lote no abierto.
     */
    public function quitarPedido(int $loteId, int $pedidoId, ?int $usuarioId): void
    {
        $lote = $this->loteRepo->findById($loteId);
        if ($lote === null) {
            throw new ValidationException('Lote no encontrado', ['lote_id' => 'No existe'], 404);
        }
        if ($lote->estado !== 'abierto') {
            throw new ValidationException(
                "Solo se pueden modificar lotes abiertos (estado actual: {$lote->estado})",
                ['lote_id' => 'Lote no abierto'],
                409
            );
        }

        $this->db->beginTransaction();
        try {
            $deleted = $this->loteRepo->deletePivot($loteId, $pedidoId);
            if (!$deleted) {
                $this->db->rollBack();
                throw new ValidationException(
                    "Pedido $pedidoId no esta en el lote",
                    ['pedido_id' => 'No esta en el lote'],
                    404
                );
            }
            $this->loteRepo->recalcularTotales($loteId);

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'actualizar',
                tablaAfectada: 'lab_lotes_os',
                registroId: $loteId,
                valorNuevo: ['pedido_id_quitado' => $pedidoId],
                contexto: 'quitar_pedido_lote',
            );

            $this->db->commit();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @throws ValidationException 409 si no esta abierto, 422 si motivo vacio.
     */
    public function anular(int $loteId, string $motivo, ?int $usuarioId): void
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new ValidationException(
                'Motivo es obligatorio',
                ['motivo' => 'No puede ser vacio'],
                422
            );
        }
        if (mb_strlen($motivo) > 500) {
            throw new ValidationException(
                'Motivo demasiado largo',
                ['motivo' => 'Maximo 500 caracteres'],
                422
            );
        }

        $lote = $this->loteRepo->findById($loteId);
        if ($lote === null) {
            throw new ValidationException('Lote no encontrado', ['lote_id' => 'No existe'], 404);
        }
        if ($lote->estado !== 'abierto') {
            throw new ValidationException(
                "Solo se pueden anular lotes abiertos (estado actual: {$lote->estado})",
                ['lote_id' => 'Lote no abierto'],
                409
            );
        }

        $this->loteRepo->marcarAnulado($loteId, $motivo, $usuarioId);

        $this->auditoria->log(
            usuarioId: $usuarioId,
            accion: 'anular',
            tablaAfectada: 'lab_lotes_os',
            registroId: $loteId,
            valorNuevo: ['motivo' => $motivo],
            contexto: 'anular_lote',
        );
    }

    /**
     * Cobra el lote: genera N pagos en lab_pagos con lote_id compartido y
     * marca cada pedido del lote estado_seguro='P'. Atomico (transaccion).
     *
     * @param array{fecha_pago:string, medio_pago:string, referencia?:?string,
     *              observaciones?:?string} $input
     * @throws ValidationException
     */
    public function cobrar(int $loteId, array $input, ?int $usuarioId): void
    {
        $errors = [];

        $fechaPago = (string) ($input['fecha_pago'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPago)) {
            $errors['fecha_pago'] = 'Formato YYYY-MM-DD requerido';
        } elseif ($fechaPago > date('Y-m-d')) {
            $errors['fecha_pago'] = 'No puede ser fecha futura';
        }

        $medioPago = (string) ($input['medio_pago'] ?? '');
        if (!in_array($medioPago, self::MEDIOS_PAGO_VALIDOS, true)) {
            $errors['medio_pago'] = 'Debe ser uno de: ' . implode(', ', self::MEDIOS_PAGO_VALIDOS);
        }

        $referencia = isset($input['referencia']) ? (string) $input['referencia'] : null;
        if ($referencia !== null && mb_strlen($referencia) > 100) {
            $errors['referencia'] = 'Maximo 100 caracteres';
        }

        $observaciones = isset($input['observaciones']) ? (string) $input['observaciones'] : null;

        if ($errors !== []) {
            throw new ValidationException('Errores de validacion', $errors, 422);
        }

        $lote = $this->loteRepo->findById($loteId);
        if ($lote === null) {
            throw new ValidationException('Lote no encontrado', ['lote_id' => 'No existe'], 404);
        }
        if ($lote->estado !== 'abierto') {
            throw new ValidationException(
                "El lote ya fue {$lote->estado}",
                ['lote_id' => 'Lote no abierto'],
                409
            );
        }

        $pedidos = $this->loteRepo->findPedidosDelLote($loteId);
        if ($pedidos === []) {
            throw new ValidationException(
                'El lote no tiene pedidos',
                ['lote_id' => 'Lote vacio'],
                409
            );
        }

        $this->db->beginTransaction();
        try {
            foreach ($pedidos as $p) {
                $pedidoId = (int) $p['pedido_id'];
                $monto = (float) $p['monto_seguro_snapshot'];

                $this->pagoRepo->crear([
                    'pedido_id' => $pedidoId,
                    'lote_id' => $loteId,
                    'quien_pago' => 'seguro',
                    'monto' => $monto,
                    'fecha_pago' => $fechaPago,
                    'medio_pago' => $medioPago,
                    'referencia' => $referencia,
                    'observaciones' => $observaciones,
                    'usuario_carga_id' => $usuarioId,
                ]);
                $this->pedidoRepo->cambiarEstadoFacturacion($pedidoId, 'seguro', 'P');
            }

            $this->loteRepo->marcarCobrado($loteId, $usuarioId);

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'actualizar',
                tablaAfectada: 'lab_lotes_os',
                registroId: $loteId,
                valorNuevo: [
                    'cantidad_pagos' => count($pedidos),
                    'monto_total' => $lote->montoTotal,
                    'fecha_pago' => $fechaPago,
                    'medio_pago' => $medioPago,
                ],
                contexto: 'cobrar_lote',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Lista pedidos elegibles para entrar a un lote nuevo.
     *
     * @param array{obra_social_id:int|string, fecha_desde:string, fecha_hasta:string} $input
     * @return array{pedidos:array<int,array<string,mixed>>, total:int, monto_total:float}
     */
    public function pedidosElegibles(array $input): array
    {
        $errors = [];
        $obraSocialId = isset($input['obra_social_id']) ? (int) $input['obra_social_id'] : 0;
        if ($obraSocialId <= 0) {
            $errors['obra_social_id'] = 'Debe ser > 0';
        }
        $fechaDesde = (string) ($input['fecha_desde'] ?? '');
        $fechaHasta = (string) ($input['fecha_hasta'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $errors['fecha_desde'] = 'Formato YYYY-MM-DD requerido';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $errors['fecha_hasta'] = 'Formato YYYY-MM-DD requerido';
        }
        if ($errors !== []) {
            throw new ValidationException('Errores de validacion', $errors, 422);
        }

        $pedidos = $this->loteRepo->findPedidosElegibles($obraSocialId, $fechaDesde, $fechaHasta);
        $total = count($pedidos);
        $montoTotal = 0.0;
        foreach ($pedidos as $p) {
            $montoTotal += (float) $p['monto_seguro'];
        }
        return [
            'pedidos' => $pedidos,
            'total' => $total,
            'monto_total' => round($montoTotal, 2),
        ];
    }

    /**
     * @param array<string,mixed> $filtros
     * @return array{lotes:array<int,array<string,mixed>>, total:int}
     */
    public function listar(array $filtros, int $limite = 50, int $offset = 0): array
    {
        $limite = max(1, min(200, $limite));
        $offset = max(0, $offset);
        return $this->loteRepo->buscar($filtros, $limite, $offset);
    }

    /**
     * Detalle del lote: cabecera + pedidos.
     *
     * @return array{lote:array<string,mixed>, pedidos:array<int,array<string,mixed>>}
     * @throws ValidationException 404
     */
    public function ver(int $loteId): array
    {
        $lote = $this->loteRepo->findById($loteId);
        if ($lote === null) {
            throw new ValidationException('Lote no encontrado', ['lote_id' => 'No existe'], 404);
        }
        $pedidos = $this->loteRepo->findPedidosDelLote($loteId);
        return [
            'lote' => [
                'id' => $lote->id,
                'numero' => $lote->numero,
                'obra_social_id' => $lote->obraSocialId,
                'fecha_desde' => $lote->fechaDesde,
                'fecha_hasta' => $lote->fechaHasta,
                'estado' => $lote->estado,
                'monto_total' => $lote->montoTotal,
                'cantidad_pedidos' => $lote->cantidadPedidos,
                'fecha_generacion' => $lote->fechaGeneracion,
                'fecha_cobro' => $lote->fechaCobro,
                'fecha_anulacion' => $lote->fechaAnulacion,
                'motivo_anulacion' => $lote->motivoAnulacion,
                'observaciones' => $lote->observaciones,
            ],
            'pedidos' => $pedidos,
        ];
    }
}
