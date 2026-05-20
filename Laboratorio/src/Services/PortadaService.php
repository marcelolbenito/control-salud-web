<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Helpers\PdfRenderer;
use App\Repositories\PedidoRepository;

/**
 * Genera el PDF "Portada" de un pedido: hoja de carátula con datos del
 * paciente, médico, fecha y la lista de items solicitados.
 */
final class PortadaService
{
    public function __construct(
        private PedidoRepository $pedidoRepo,
        private PdfRenderer $pdfRenderer,
        private string $templatePath,
    ) {
    }

    /**
     * @return string PDF bytes.
     * @throws ValidationException si el pedido no existe.
     */
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

        $payload = $this->armarPayload($pedido, $items);
        return $this->pdfRenderer->render($this->templatePath, $payload);
    }

    /**
     * @param array<string,mixed> $pedido
     * @param array<int,array<string,mixed>> $items
     * @return array<string,mixed>
     */
    private function armarPayload(array $pedido, array $items): array
    {
        $snapshot = $pedido['snapshot_paciente'] ?? null;
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true) ?: [];
        }

        return [
            'numero'            => (string) ($pedido['numero'] ?? ''),
            'fecha_solicitud'   => (string) ($pedido['fecha_solicitud'] ?? ''),
            'fecha_extraccion'  => $pedido['fecha_extraccion'] ?? null,
            'fecha_entrega'     => $pedido['fecha_entrega'] ?? null,
            'estado'            => (string) ($pedido['estado'] ?? ''),
            'prioridad'         => (string) ($pedido['prioridad'] ?? 'rutina'),
            'es_critico'        => (bool) ($pedido['es_critico'] ?? false),
            'paciente'          => [
                'nombre'    => $snapshot['nombre']    ?? '',
                'dni'       => $snapshot['dni']       ?? '',
                'sexo'      => $snapshot['sexo']      ?? '',
                'fecha_nac' => $snapshot['fecha_nac'] ?? '',
            ],
            'medico'            => $pedido['medico_externo']
                ?? ($pedido['medico_id'] ? "medico_id={$pedido['medico_id']}" : ''),
            'obra_social_nombre' => $pedido['obra_social_nombre'] ?? '',
            'numero_afiliado'   => $pedido['numero_afiliado'] ?? '',
            'diagnostico'       => $pedido['diagnostico'] ?? '',
            'observaciones'     => $pedido['observaciones'] ?? '',
            'items'             => array_map(static function (array $it): array {
                return [
                    'codigo'      => (string) ($it['determinacion_codigo'] ?? ''),
                    'nombre'      => (string) ($it['determinacion_nombre'] ?? ''),
                    'unidad'      => (string) ($it['unidad'] ?? ''),
                    'perfil'      => (string) ($it['perfil_nombre'] ?? ''),
                    'estado'      => (string) ($it['estado'] ?? ''),
                ];
            }, $items),
            'fecha_emision'     => date('Y-m-d H:i:s'),
        ];
    }
}
