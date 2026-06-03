<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Helpers\Response;
use App\Services\InformeService;

final class InformeController
{
    private const USUARIO_ID_HARDCODED = 1;

    public function __construct(
        private InformeService $service,
        private string $storageDir,
    ) {
    }

    public function generar(): void
    {
        $input = $this->getJsonInput();

        $pedidoId = isset($input['pedido_id']) ? (int) $input['pedido_id'] : 0;
        if ($pedidoId <= 0) {
            Response::error('pedido_id es obligatorio', 422, ['pedido_id' => 'Falta'], 'VALIDATION');
            return;
        }

        $opciones = [
            'es_parcial'    => (bool) ($input['es_parcial'] ?? false),
            'observaciones' => $input['observaciones'] ?? null,
            'firma'         => $input['firma'] ?? null,
        ];

        try {
            $r = $this->service->generar($pedidoId, self::USUARIO_ID_HARDCODED, $opciones);
            Response::success($r, 201);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        } catch (DomainException $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function marcarEntregado(int $id): void
    {
        if ($id <= 0) {
            Response::error('id invalido', 400);
            return;
        }

        $input = $this->getJsonInput();
        $destinatario = isset($input['destinatario']) ? (string) $input['destinatario'] : null;

        try {
            $r = $this->service->marcarEntregado($id, self::USUARIO_ID_HARDCODED, $destinatario);
            Response::success($r);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->getFields(), 'VALIDATION');
        } catch (DomainException $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function listarPorPedido(int $pedidoId): void
    {
        if ($pedidoId <= 0) {
            Response::error('pedido_id invalido', 400);
            return;
        }
        $informes = $this->service->listarPorPedido($pedidoId);
        Response::success(['informes' => $informes]);
    }

    /**
     * @param array<string,mixed> $query
     */
    public function listarRecientes(array $query): void
    {
        $filtros = [
            'numero'    => $query['numero']    ?? null,
            'paciente'  => $query['paciente']  ?? null,
            'dni'       => $query['dni']       ?? null,
            'desde'     => $query['desde']     ?? null,
            'hasta'     => $query['hasta']     ?? null,
            'entregado' => $query['entregado'] ?? null,
            'limit'     => isset($query['limit']) ? (int) $query['limit'] : null,
        ];
        $informes = $this->service->listarRecientes($filtros);
        Response::success(['informes' => $informes]);
    }

    public function descargar(int $id): void
    {
        if ($id <= 0) {
            Response::error('id invalido', 400);
            return;
        }

        $informe = $this->service->obtener($id);
        if ($informe === null) {
            Response::error("Informe $id no encontrado", 404);
            return;
        }

        $ruta = rtrim($this->storageDir, '/\\') . DIRECTORY_SEPARATOR . (string) $informe['ruta_pdf'];
        $real = realpath($ruta);
        $base = realpath($this->storageDir);
        if ($real === false || $base === false || !str_starts_with($real, $base)) {
            Response::error('Archivo no accesible', 404);
            return;
        }
        if (!is_file($real)) {
            Response::error('Archivo no encontrado en disco', 404);
            return;
        }

        $hashActual = hash_file('sha256', $real);
        if ($hashActual !== (string) $informe['hash_pdf']) {
            Response::error('El archivo fue alterado: hash invalido', 409);
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($real) . '"');
        header('Content-Length: ' . (string) filesize($real));
        readfile($real);
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
