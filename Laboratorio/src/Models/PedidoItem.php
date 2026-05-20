<?php

declare(strict_types=1);

namespace App\Models;

/**
 * DTO de un item (determinacion solicitada) dentro de un pedido.
 * Si vino por un perfil, perfil_id queda como referencia.
 *
 * El monto del item NO se guarda. Se recalcula on-the-fly por
 * AranceladorService con la vigencia NBU del momento (ver
 * lab_nbu_valores_os.fecha_desde/fecha_hasta).
 */
final class PedidoItem
{
    public function __construct(
        public ?int $id,
        public ?int $pedidoId,
        public int $determinacionId,
        public ?int $perfilId,
        public string $estado,
        public ?float $precio,
        public ?string $observaciones,
    ) {
    }
}
