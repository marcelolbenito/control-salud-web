<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Helpers\PdfRenderer;
use App\Integration\ControlSaludIntegration;
use App\Repositories\LoteOsRepository;
use PDO;

/**
 * Renderiza el PDF resumen de un lote (planilla de facturacion).
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

        $filas = $this->loteRepo->findDetalleParaPlanilla($loteId);
        $obraSocialNombre = $this->buscarNombreOs($lote->obraSocialId);
        $lab = $this->buscarDatosLab();

        $pedidos = [];
        foreach ($filas as $f) {
            $pid = (int) $f['pedido_id'];
            if (!isset($pedidos[$pid])) {
                $snap = $f['snapshot_paciente'] ?? null;
                if (is_string($snap)) {
                    $snap = json_decode($snap, true) ?: [];
                }
                $snap = is_array($snap) ? $snap : [];
                $nombre = trim((string) ($snap['nombre'] ?? '')) !== ''
                    ? (string) $snap['nombre']
                    : trim((string) ($f['paciente_join'] ?? ''), ', ');

                $fecha = (string) ($f['fecha_solicitud'] ?? '');
                $ts = $fecha !== '' ? strtotime($fecha) : false;

                $pedidos[$pid] = [
                    'numero'       => (string) ($f['pedido_numero'] ?? ''),
                    'fecha'        => $ts !== false ? date('d/m/Y', $ts) : substr($fecha, 0, 10),
                    'paciente'     => $nombre !== '' ? $nombre : '-',
                    'nro_afiliado' => (string) ($f['numero_afiliado'] ?? ($snap['nro_afiliado'] ?? '')),
                    'items'        => [],
                    'subtotal'     => 0.0,
                ];
            }
            $monto = (float) ($f['monto_item'] ?? 0);
            $pedidos[$pid]['items'][] = [
                'codigo'      => (string) ($f['determinacion_codigo'] ?? ''),
                'descripcion' => (string) ($f['determinacion_nombre'] ?? ''),
                'ub'          => $f['nbu_unidades'],
                'ub_os'       => $f['nbu_valor_unitario'],
                'precio'      => $monto,
            ];
            $pedidos[$pid]['subtotal'] += $monto;
        }

        $pedidos = array_values($pedidos);
        $totalGeneral = 0.0;
        foreach ($pedidos as $p) {
            $totalGeneral += $p['subtotal'];
        }

        return $this->pdfRenderer->render($this->templatePath, [
            'lab' => $lab,
            'obraSocialNombre' => $obraSocialNombre,
            'lote' => [
                'numero' => $lote->numero,
                'fecha_desde' => $lote->fechaDesde,
                'fecha_hasta' => $lote->fechaHasta,
                'estado' => $lote->estado,
            ],
            'pedidos' => $pedidos,
            'totalGeneral' => $totalGeneral,
            'fechaEmision' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<string,string>
     */
    private function buscarDatosLab(): array
    {
        $claves = [
            'laboratorio_nombre',
            'laboratorio_direccion',
            'firmante_apellido',
            'firmante_nombres',
            'firmante_matricula',
        ];
        $in = implode(',', array_fill(0, count($claves), '?'));
        $stmt = $this->db->prepare("SELECT clave, valor FROM lab_config WHERE clave IN ($in)");
        $stmt->execute($claves);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['clave']] = (string) $row['valor'];
        }
        return $out;
    }

    private function buscarNombreOs(int $obraSocialId): string
    {
        $table = ControlSaludIntegration::obraSocialTable();
        $stmt = $this->db->prepare(
            "SELECT nombre FROM {$table} WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $obraSocialId]);
        $row = $stmt->fetch();
        return $row !== false ? (string) $row['nombre'] : "OS #$obraSocialId";
    }
}
