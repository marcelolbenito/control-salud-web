<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Helpers\Response;
use App\Services\ResultadoService;

final class ResultadoController
{
    /**
     * TODO: reemplazar cuando exista login.
     */
    private const USUARIO_ID_HARDCODED = 1;

    public function __construct(private ResultadoService $service)
    {
    }

    public function cargar(): void
    {
        $input = $this->getJsonInput();

        try {
            $result = $this->service->cargar($input, self::USUARIO_ID_HARDCODED);
            Response::success($result, 201);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        } catch (DomainException $e) {
            $status = $e->getCode() === 403 ? 403 : 409;
            Response::error($e->getMessage(), $status);
        }
    }

    public function listarPorPedido(int $pedidoId): void
    {
        if ($pedidoId <= 0) {
            Response::error('pedido_id invalido', 400);
            return;
        }

        $resultados = $this->service->listarPorPedido($pedidoId);
        Response::success(['resultados' => $resultados]);
    }

    /**
     * @return array<string,mixed>
     */
    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }
        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Response::error('Body invalido (se esperaba JSON): ' . $e->getMessage(), 400);
            exit;
        }
        if (!is_array($data)) {
            Response::error('Body invalido (se esperaba un objeto JSON)', 400);
            exit;
        }
        return $data;
    }
}
