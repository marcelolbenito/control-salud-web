<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Helpers\PdfRenderer;
use App\Repositories\LoteOsRepository;
use PDO;

/**
 * Renderiza el PDF resumen de un lote (1 fila por pedido).
 */
final class LoteOsPdfRenderer
{
    public function __construct(
        private LoteOsRepository $loteRepo,
        private PdfRenderer $pdfRenderer,
        private PDO $db,
        private string $templatePath,
    ) {
    }

    /**
     * @return string PDF bytes.
     * @throws ValidationException 404 si lote no existe.
     */
    public function generar(int $loteId): string
    {
        $lote = $this->loteRepo->findById($loteId);
        if ($lote === null) {
            throw new ValidationException('Lote no encontrado', ['lote_id' => 'No existe'], 404);
        }

        $pedidos = $this->loteRepo->findPedidosDelLote($loteId);
        $obraSocialNombre = $this->buscarNombreOs($lote->obraSocialId);

        return $this->pdfRenderer->render($this->templatePath, [
            'lote' => [
                'numero' => $lote->numero,
                'fecha_desde' => $lote->fechaDesde,
                'fecha_hasta' => $lote->fechaHasta,
                'estado' => $lote->estado,
                'monto_total' => $lote->montoTotal,
                'cantidad_pedidos' => $lote->cantidadPedidos,
            ],
            'pedidos' => $pedidos,
            'obraSocialNombre' => $obraSocialNombre,
            'fechaEmision' => date('Y-m-d H:i:s'),
        ]);
    }

    private function buscarNombreOs(int $obraSocialId): string
    {
        $stmt = $this->db->prepare(
            'SELECT nombre FROM obras_sociales WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $obraSocialId]);
        $row = $stmt->fetch();
        return $row !== false ? (string) $row['nombre'] : "OS #$obraSocialId";
    }
}
