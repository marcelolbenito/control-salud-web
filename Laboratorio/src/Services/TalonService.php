<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Helpers\PdfRenderer;
use App\Repositories\PedidoRepository;

/**
 * Genera el TALÓN: comprobante corto que se entrega al paciente al crear
 * la orden. Incluye nº de orden, fecha y datos esenciales.
 */
final class TalonService
{
    public function __construct(
        private PedidoRepository $pedidoRepo,
        private PdfRenderer $pdfRenderer,
        private string $templatePath,
    ) {
    }

    public function generar(int $pedidoId): string
    {
        if ($pedidoId <= 0) {
            throw new ValidationException('id invalido', ['id' => 'Debe ser un entero positivo']);
        }

        $pedido = $this->pedidoRepo->findById($pedidoId);
        if ($pedido === null) {
            throw new ValidationException('Pedido no encontrado', ['id' => 'No existe el pedido o esta borrado'], 404);
        }

        $items = $this->pedidoRepo->findItemsByPedidoId($pedidoId);

        $snapshot = $pedido['snapshot_paciente'] ?? null;
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true) ?: [];
        }

        $payload = [
            'numero'           => (string) ($pedido['numero'] ?? ''),
            'fecha_solicitud'  => (string) ($pedido['fecha_solicitud'] ?? ''),
            'fecha_entrega'    => $pedido['fecha_entrega'] ?? null,
            'paciente_nombre'  => (string) ($snapshot['nombre'] ?? ''),
            'paciente_dni'     => (string) ($snapshot['dni'] ?? ''),
            'cantidad_items'   => count($items),
            'fecha_emision'    => date('Y-m-d H:i:s'),
        ];

        return $this->pdfRenderer->render($this->templatePath, $payload);
    }
}
