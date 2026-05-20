<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ItemMonto;
use App\Repositories\DeterminacionRepository;
use App\Repositories\NbuDeterminacionRepository;
use App\Repositories\NbuValorOsRepository;

/**
 * Unico punto de calculo del monto de un item de pedido.
 *
 * Reglas:
 *   - Con OS: unidades(NBU) * valor_unitario(OS vigente a `fecha`). Si falta cualquiera, total = 0.
 *   - Sin OS: lab_determinaciones.precio (precio particular). Si es null, total = 0.
 *   - Siempre redondea a 2 decimales (DECIMAL(12,2) en BD).
 *
 * El parametro `fecha` permite resolver el valor NBU historico (vigencias por OS).
 * Si se omite, usa hoy.
 */
final class AranceladorService
{
    public function __construct(
        private NbuDeterminacionRepository $nbuDeterminacionRepo,
        private NbuValorOsRepository $nbuValorOsRepo,
        private DeterminacionRepository $determinacionRepo,
    ) {
    }

    public function calcularMontoItem(int $determinacionId, ?int $obraSocialId, ?string $fecha = null): ItemMonto
    {
        if ($obraSocialId === null) {
            return ItemMonto::precioParticular(
                $this->precioParticularDe($determinacionId)
            );
        }

        $unidades = $this->nbuDeterminacionRepo->findUnidades($determinacionId);
        $valor    = $this->nbuValorOsRepo->findValorAt($obraSocialId, $fecha ?? date('Y-m-d'));

        if ($unidades === null || $valor === null) {
            return ItemMonto::sinArancel();
        }

        return ItemMonto::nbuOs($unidades, $valor);
    }

    private function precioParticularDe(int $determinacionId): ?float
    {
        $rows = $this->determinacionRepo->findActivasByIds([$determinacionId]);
        if ($rows === []) {
            return null;
        }
        $precio = $rows[0]['precio'] ?? null;
        return $precio !== null ? (float) $precio : null;
    }
}
