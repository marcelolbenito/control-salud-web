<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Helpers\Response;
use App\Services\FacturacionOsService;
use App\Services\LoteOsCsvRenderer;
use App\Services\LoteOsPdfRenderer;
use Throwable;

final class FacturacionOsController
{
    public function __construct(
        private FacturacionOsService $service,
        private LoteOsPdfRenderer $pdfRenderer,
        private LoteOsCsvRenderer $csvRenderer,
    ) {
    }

    public function elegibles(): void
    {
        try {
            $resultado = $this->service->pedidosElegibles([
                'obra_social_id' => $_GET['obra_social_id'] ?? 0,
                'fecha_desde'    => $_GET['fecha_desde'] ?? '',
                'fecha_hasta'    => $_GET['fecha_hasta'] ?? '',
            ]);
            Response::success($resultado);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 elegibles] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function listar(): void
    {
        $limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 50;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $filtros = [
            'estado'         => $_GET['estado'] ?? null,
            'obra_social_id' => $_GET['obra_social_id'] ?? null,
            'fecha_desde'    => $_GET['fecha_desde'] ?? null,
            'fecha_hasta'    => $_GET['fecha_hasta'] ?? null,
            'orden'          => $_GET['orden'] ?? null,
        ];
        try {
            Response::success($this->service->listar($filtros, $limite, $offset));
        } catch (Throwable $e) {
            error_log('[SP8 listar] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function ver(int $loteId): void
    {
        try {
            Response::success($this->service->ver($loteId));
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 ver] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function generar(?int $usuarioId): void
    {
        try {
            $body = $this->getJsonInput();
            $result = $this->service->generar($body, $usuarioId);
            Response::success($result, 201);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 generar] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function quitarPedido(int $loteId, int $pedidoId, ?int $usuarioId): void
    {
        try {
            $this->service->quitarPedido($loteId, $pedidoId, $usuarioId);
            Response::success(['lote_id' => $loteId, 'pedido_id_quitado' => $pedidoId]);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 quitar-pedido] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function anular(int $loteId, ?int $usuarioId): void
    {
        try {
            $body = $this->getJsonInput();
            $motivo = (string) ($body['motivo'] ?? '');
            $this->service->anular($loteId, $motivo, $usuarioId);
            Response::success(['lote_id' => $loteId, 'estado' => 'anulado']);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 anular] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function cobrar(int $loteId, ?int $usuarioId): void
    {
        try {
            $body = $this->getJsonInput();
            $this->service->cobrar($loteId, $body, $usuarioId);
            Response::success(['lote_id' => $loteId, 'estado' => 'cobrado']);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 cobrar] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function pdf(int $loteId): void
    {
        try {
            $bytes = $this->pdfRenderer->generar($loteId);
            Response::pdf($bytes, "lote-$loteId.pdf");
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 pdf] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function itemsPedido(int $loteId, int $pedidoId): void
    {
        try {
            Response::success(['items' => $this->service->listarItemsDePedidoEnLote($loteId, $pedidoId)]);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 items-pedido] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function excluirItem(int $loteId, int $pedidoItemId, ?int $usuarioId): void
    {
        try {
            $body = $this->getJsonInput();
            $motivo = isset($body['motivo']) ? (string) $body['motivo'] : null;
            $r = $this->service->excluirItem($loteId, $pedidoItemId, $usuarioId, $motivo);
            Response::success($r);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 excluir-item] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function incluirItem(int $loteId, int $pedidoItemId, ?int $usuarioId): void
    {
        try {
            $r = $this->service->incluirItem($loteId, $pedidoItemId, $usuarioId);
            Response::success($r);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 incluir-item] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    public function csv(int $loteId): void
    {
        try {
            $csv = $this->csvRenderer->generar($loteId);
            if (!headers_sent()) {
                http_response_code(200);
                header('Content-Type: text/csv; charset=utf-8');
                header(sprintf('Content-Disposition: attachment; filename="lote-%d.csv"', $loteId));
                header('Content-Length: ' . strlen($csv));
            }
            echo $csv;
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->getCode(), $e->getFields());
        } catch (Throwable $e) {
            error_log('[SP8 csv] ' . $e->getMessage());
            Response::error('Error interno', 500);
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function getJsonInput(): array
    {
        $raw = (string) file_get_contents('php://input');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
