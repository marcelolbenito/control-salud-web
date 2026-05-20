<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\ReporteRepository;
use DateTimeImmutable;

/**
 * Reportes de recaudacion del laboratorio.
 *
 * Solo lectura. Valida formato de mes y delega al repo.
 *
 * "Particular" = pagos con quien_pago = 'paciente'.
 * "Obra social" = pagos con quien_pago = 'seguro'.
 *
 * Los pagos los carga el sistema mayor; este modulo solo consulta.
 */
final class ReporteService
{
    public function __construct(private ReporteRepository $repo)
    {
    }

    /**
     * Recaudacion del mes indicado (default: mes actual).
     *
     * @param string|null $mes Formato 'YYYY-MM'. Si null usa el mes actual.
     * @return array{mes:string, particular:float, obra_social:float, total:float, cantidad_pagos:int}
     */
    public function delMes(?string $mes = null): array
    {
        $mes = $mes ?? (new DateTimeImmutable())->format('Y-m');
        $this->validarMes($mes);

        return $this->repo->recaudacionDelMes($mes);
    }

    /**
     * Serie de los ultimos N meses (incluye el actual).
     * Default 12 meses, max 36, min 1.
     *
     * @return array<int,array{mes:string, particular:float, obra_social:float, total:float, cantidad_pagos:int}>
     */
    public function ultimosMeses(int $cantidad = 12): array
    {
        $cantidad = max(1, min(36, $cantidad));

        $hasta = new DateTimeImmutable('first day of this month');
        $desde = $hasta->modify('-' . ($cantidad - 1) . ' months');

        return $this->repo->recaudacionPorMes(
            mesDesde: $desde->format('Y-m'),
            mesHasta: $hasta->format('Y-m'),
        );
    }

    private function validarMes(string $mes): void
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            throw new ValidationException('Mes invalido', [
                'mes' => "Formato esperado YYYY-MM (recibido: $mes)",
            ]);
        }
    }
}
