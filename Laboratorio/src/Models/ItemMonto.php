<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Resultado del calculo de monto para un item de pedido.
 * Devuelto por AranceladorService::calcularMontoItem.
 */
final class ItemMonto
{
    /**
     * @param 'nbu_os'|'precio_particular'|'sin_arancel' $origen
     */
    public function __construct(
        public readonly ?float $unidades,
        public readonly ?float $valorUnit,
        public readonly float $total,
        public readonly string $origen,
    ) {
    }

    public static function nbuOs(float $unidades, float $valorUnit): self
    {
        return new self(
            unidades: $unidades,
            valorUnit: $valorUnit,
            total: round($unidades * $valorUnit, 2),
            origen: 'nbu_os',
        );
    }

    public static function precioParticular(?float $precio): self
    {
        return new self(
            unidades: null,
            valorUnit: null,
            total: $precio !== null ? round($precio, 2) : 0.00,
            origen: 'precio_particular',
        );
    }

    public static function sinArancel(): self
    {
        return new self(
            unidades: null,
            valorUnit: null,
            total: 0.00,
            origen: 'sin_arancel',
        );
    }
}
