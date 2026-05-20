<?php

declare(strict_types=1);

namespace App\Models;

/**
 * DTO de un lote de facturacion a OS (SP8).
 *
 * Estados: 'abierto' (default), 'cobrado', 'anulado'.
 * Numero formato: L-YYYY-NNNNN.
 */
final class LoteOs
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $numero,
        public readonly int $obraSocialId,
        public readonly string $fechaDesde,
        public readonly string $fechaHasta,
        public readonly string $estado,
        public readonly float $montoTotal,
        public readonly int $cantidadPedidos,
        public readonly ?string $fechaGeneracion = null,
        public readonly ?string $fechaCobro = null,
        public readonly ?string $fechaAnulacion = null,
        public readonly ?string $motivoAnulacion = null,
        public readonly ?int $usuarioGeneracionId = null,
        public readonly ?int $usuarioCobroId = null,
        public readonly ?int $usuarioAnulacionId = null,
        public readonly ?string $observaciones = null,
    ) {
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            numero: (string) $row['numero'],
            obraSocialId: (int) $row['obra_social_id'],
            fechaDesde: (string) $row['fecha_desde'],
            fechaHasta: (string) $row['fecha_hasta'],
            estado: (string) $row['estado'],
            montoTotal: (float) ($row['monto_total'] ?? 0.0),
            cantidadPedidos: (int) ($row['cantidad_pedidos'] ?? 0),
            fechaGeneracion: $row['fecha_generacion'] ?? null,
            fechaCobro: $row['fecha_cobro'] ?? null,
            fechaAnulacion: $row['fecha_anulacion'] ?? null,
            motivoAnulacion: $row['motivo_anulacion'] ?? null,
            usuarioGeneracionId: isset($row['usuario_generacion_id']) ? (int) $row['usuario_generacion_id'] : null,
            usuarioCobroId: isset($row['usuario_cobro_id']) ? (int) $row['usuario_cobro_id'] : null,
            usuarioAnulacionId: isset($row['usuario_anulacion_id']) ? (int) $row['usuario_anulacion_id'] : null,
            observaciones: $row['observaciones'] ?? null,
        );
    }
}
