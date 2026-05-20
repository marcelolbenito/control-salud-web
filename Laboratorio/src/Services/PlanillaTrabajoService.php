<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Helpers\PdfRenderer;
use App\Repositories\PedidoRepository;
use App\Repositories\PerfilRepository;

/**
 * Genera la PLANILLA DE TRABAJO para el técnico:
 * filas = pedidos, columnas = determinaciones del perfil seleccionado.
 * El técnico anota los valores a mano y los carga después en el sistema.
 */
final class PlanillaTrabajoService
{
    private const MAX_PEDIDOS = 200;

    public function __construct(
        private PedidoRepository $pedidoRepo,
        private PerfilRepository $perfilRepo,
        private PdfRenderer $pdfRenderer,
        private string $templatePath,
    ) {
    }

    /**
     * @param array<int> $pedidoIds
     */
    public function porPerfil(int $perfilId, array $pedidoIds): string
    {
        $errores = [];
        if ($perfilId <= 0) {
            $errores['perfil_id'] = 'Debe ser un id valido';
        }
        $pedidoIds = array_values(array_unique(array_filter(
            array_map(static fn ($x) => (int) $x, $pedidoIds),
            static fn (int $x) => $x > 0,
        )));
        if (count($pedidoIds) === 0) {
            $errores['pedido_ids'] = 'Debe haber al menos un pedido';
        }
        if (count($pedidoIds) > self::MAX_PEDIDOS) {
            $errores['pedido_ids'] = 'Máximo ' . self::MAX_PEDIDOS . ' pedidos por planilla';
        }
        if (!empty($errores)) {
            throw new ValidationException('Filtros invalidos', $errores);
        }

        // Cargar perfil + determinaciones que lo componen.
        $detIds = $this->perfilRepo->getDeterminacionIdsByPerfilId($perfilId);
        if ($detIds === []) {
            throw new ValidationException('Perfil sin determinaciones', ['perfil_id' => 'El perfil no existe o no tiene determinaciones activas'], 404);
        }

        // Obtener nombres del perfil y de las determinaciones.
        $perfil = $this->perfilRepo->findById($perfilId);
        $determinaciones = $this->perfilRepo->findDeterminacionesByIds($detIds);

        // Cargar cada pedido con su paciente snapshot.
        $pedidos = [];
        foreach ($pedidoIds as $pid) {
            $p = $this->pedidoRepo->findById($pid);
            if ($p === null) {
                continue;
            }
            $snapshot = $p['snapshot_paciente'] ?? null;
            if (is_string($snapshot)) {
                $snapshot = json_decode($snapshot, true) ?: [];
            }
            $pedidos[] = [
                'numero'          => (string) ($p['numero'] ?? ''),
                'fecha_solicitud' => (string) ($p['fecha_solicitud'] ?? ''),
                'paciente_nombre' => (string) ($snapshot['nombre'] ?? ''),
                'paciente_dni'    => (string) ($snapshot['dni'] ?? ''),
                'paciente_sexo'   => (string) ($snapshot['sexo'] ?? ''),
                'paciente_fnac'   => (string) ($snapshot['fecha_nac'] ?? ''),
            ];
        }

        $payload = [
            'perfil'          => [
                'id'     => $perfilId,
                'codigo' => (string) ($perfil['codigo'] ?? ''),
                'nombre' => (string) ($perfil['nombre'] ?? ''),
            ],
            'determinaciones' => $determinaciones,
            'pedidos'         => $pedidos,
            'fecha_emision'   => date('Y-m-d H:i:s'),
        ];

        return $this->pdfRenderer->render($this->templatePath, $payload);
    }
}
