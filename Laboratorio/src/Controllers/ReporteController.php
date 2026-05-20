<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Helpers\Response;
use App\Services\ReporteService;

final class ReporteController
{
    public function __construct(private ReporteService $service)
    {
    }

    /**
     * GET ?mes=YYYY-MM (opcional, default mes actual)
     *     ?meses=N    (opcional, default 12, serie historica)
     */
    public function index(array $query): void
    {
        try {
            $mes = isset($query['mes']) && $query['mes'] !== ''
                ? (string) $query['mes']
                : null;

            $cantidadMeses = isset($query['meses']) && $query['meses'] !== ''
                ? (int) $query['meses']
                : 12;

            Response::success([
                'mes_actual' => $this->service->delMes($mes),
                'serie'      => $this->service->ultimosMeses($cantidadMeses),
            ]);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        }
    }
}
