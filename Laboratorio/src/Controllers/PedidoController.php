<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Helpers\Response;
use App\Services\PedidoService;

final class PedidoController
{
    /**
     * TODO: reemplazar cuando exista login.
     * Por ahora todas las acciones se atribuyen al usuario id=1.
     */
    private const USUARIO_ID_HARDCODED = 1;

    public function __construct(private PedidoService $service)
    {
    }

    public function crear(): void
    {
        $input = $this->getJsonInput();

        try {
            $result = $this->service->crear($input, self::USUARIO_ID_HARDCODED);
            Response::success($result, 201);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        } catch (DomainException $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function obtener(int $id): void
    {
        if ($id <= 0) {
            Response::error('id invalido', 400);
            return;
        }

        $pedido = $this->service->obtener($id);
        if ($pedido === null) {
            Response::error("Pedido $id no encontrado", 404);
            return;
        }

        Response::success($pedido);
    }

    /**
     * Listado paginado de pedidos (sub-proyecto 2).
     *
     * @param array<string,mixed> $query
     */
    public function listar(array $query): void
    {
        try {
            $r = $this->service->buscar($query);
            Response::success($r);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        }
    }

    public function anular(int $id): void
    {
        if ($id <= 0) {
            Response::error('id invalido', 400);
            return;
        }

        $body = $this->getJsonInput();
        $motivo = isset($body['motivo']) ? (string) $body['motivo'] : '';

        try {
            $this->service->anular($id, $motivo, self::USUARIO_ID_HARDCODED);
            Response::success(['pedido_id' => $id, 'estado' => 'anulado']);
        } catch (ValidationException $e) {
            $http = $e->getCode() && $e->getCode() >= 400 ? (int) $e->getCode() : 422;
            Response::error($e->getMessage(), $http, $e->getFields(), 'VALIDATION');
        }
    }

    public function eliminar(int $id): void
    {
        if ($id <= 0) {
            Response::error('id invalido', 400);
            return;
        }

        try {
            $this->service->eliminar($id, self::USUARIO_ID_HARDCODED);
            Response::success(['pedido_id' => $id, 'eliminado' => true]);
        } catch (ValidationException $e) {
            $http = $e->getCode() && $e->getCode() >= 400 ? (int) $e->getCode() : 422;
            Response::error($e->getMessage(), $http, $e->getFields(), 'VALIDATION');
        }
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
