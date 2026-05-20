<?php

declare(strict_types=1);

namespace App\Models;

final class Pago
{
    public const QUIEN_PACIENTE = 'paciente';
    public const QUIEN_SEGURO   = 'seguro';

    public const MEDIO_EFECTIVO       = 'efectivo';
    public const MEDIO_TARJETA        = 'tarjeta';
    public const MEDIO_TRANSFERENCIA  = 'transferencia';
    public const MEDIO_CHEQUE         = 'cheque';
    public const MEDIO_OTRO           = 'otro';

    public function __construct(
        public readonly int $id,
        public readonly int $pedidoId,
        public readonly string $quienPago,        // 'paciente' | 'seguro'
        public readonly string $monto,            // como string para preservar precision
        public readonly string $fechaPago,        // YYYY-MM-DD
        public readonly string $medioPago,
        public readonly ?string $referencia,
        public readonly ?string $observaciones,
        public readonly ?int $usuarioCargaId,
    ) {
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            pedidoId: (int) $row['pedido_id'],
            quienPago: (string) $row['quien_pago'],
            monto: (string) $row['monto'],
            fechaPago: (string) $row['fecha_pago'],
            medioPago: (string) $row['medio_pago'],
            referencia: isset($row['referencia']) ? (string) $row['referencia'] : null,
            observaciones: isset($row['observaciones']) ? (string) $row['observaciones'] : null,
            usuarioCargaId: isset($row['usuario_carga_id']) ? (int) $row['usuario_carga_id'] : null,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'pedido_id' => $this->pedidoId,
            'quien_pago' => $this->quienPago,
            'monto' => $this->monto,
            'fecha_pago' => $this->fechaPago,
            'medio_pago' => $this->medioPago,
            'referencia' => $this->referencia,
            'observaciones' => $this->observaciones,
            'usuario_carga_id' => $this->usuarioCargaId,
        ];
    }
}
