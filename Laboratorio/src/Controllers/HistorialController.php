<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Helpers\Response;
use App\Services\HistorialService;

final class HistorialController
{
    public function __construct(private HistorialService $service)
    {
    }

    public function listarPorPaciente(int $pacienteId, array $query): void
    {
        if ($pacienteId <= 0) {
            Response::error('paciente_id invalido', 400);
            return;
        }

        $filtros = [
            'estado' => $query['estado'] ?? null,
            'desde'  => $query['desde']  ?? null,
            'hasta'  => $query['hasta']  ?? null,
            'limit'  => $query['limit']  ?? null,
            'offset' => $query['offset'] ?? null,
        ];

        try {
            $r = $this->service->listarPorPaciente($pacienteId, $filtros);
            Response::success($r);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        }
    }

    public function dossier(int $pedidoId): void
    {
        if ($pedidoId <= 0) {
            Response::error('pedido_id invalido', 400);
            return;
        }
        $dossier = $this->service->dossierPedido($pedidoId);
        if ($dossier === null) {
            Response::error("Pedido $pedidoId no encontrado", 404);
            return;
        }
        Response::success($dossier);
    }
}
