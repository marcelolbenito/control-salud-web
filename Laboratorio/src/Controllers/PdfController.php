<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Helpers\Response;
use App\Services\PlanillaTrabajoService;
use App\Services\PortadaService;
use App\Services\TalonService;
use Throwable;

final class PdfController
{
    public function __construct(
        private PortadaService $portada,
        private TalonService $talon,
        private PlanillaTrabajoService $planilla,
    ) {
    }

    public function portada(int $pedidoId): void
    {
        $this->emitirPdf(
            fn () => $this->portada->generar($pedidoId),
            "portada-{$pedidoId}.pdf"
        );
    }

    public function talon(int $pedidoId): void
    {
        $this->emitirPdf(
            fn () => $this->talon->generar($pedidoId),
            "talon-{$pedidoId}.pdf"
        );
    }

    /**
     * @param array<string,mixed> $body
     */
    public function planillaPorPerfil(array $body): void
    {
        $perfilId  = isset($body['perfil_id']) ? (int) $body['perfil_id'] : 0;
        $pedidoIds = isset($body['pedido_ids']) && is_array($body['pedido_ids']) ? $body['pedido_ids'] : [];

        $this->emitirPdf(
            fn () => $this->planilla->porPerfil($perfilId, $pedidoIds),
            "planilla-perfil-{$perfilId}.pdf"
        );
    }

    /**
     * Ejecuta el callback que devuelve bytes PDF y los envia.
     * Maneja ValidationException -> JSON 422/404, errores genericos -> 500.
     */
    private function emitirPdf(callable $generar, string $filename): void
    {
        try {
            $bytes = $generar();
            Response::pdf($bytes, $filename, 'inline');
        } catch (ValidationException $e) {
            $http = $e->getCode() && $e->getCode() >= 400 ? (int) $e->getCode() : 422;
            Response::error($e->getMessage(), $http, $e->getFields(), 'VALIDATION');
        } catch (Throwable) {
            Response::error('No se pudo generar el PDF', 500, [], 'INTERNAL_ERROR');
        }
    }
}
