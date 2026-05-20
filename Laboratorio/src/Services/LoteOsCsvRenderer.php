<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\LoteOsRepository;

/**
 * Genera el CSV detallado del lote (1 fila por item de pedido).
 *
 * Columnas:
 *   lote_numero, pedido_numero, paciente_hc, paciente_nombre,
 *   determinacion_codigo, determinacion_nombre,
 *   nbu_unidades, valor_unitario, monto_item
 *
 * Separador: coma. Encoding: UTF-8 con BOM (para que Excel lo abra
 * correctamente con tildes y enies).
 */
final class LoteOsCsvRenderer
{
    public function __construct(private LoteOsRepository $loteRepo)
    {
    }

    /**
     * @throws ValidationException 404 si el lote no existe.
     */
    public function generar(int $loteId): string
    {
        if ($this->loteRepo->findById($loteId) === null) {
            throw new ValidationException('Lote no encontrado', ['lote_id' => 'No existe'], 404);
        }

        $items = $this->loteRepo->findItemsDelLote($loteId);

        $fh = fopen('php://temp', 'w+');
        if ($fh === false) {
            throw new \RuntimeException('No se pudo abrir buffer para CSV');
        }

        // BOM UTF-8 para Excel
        fwrite($fh, "\xEF\xBB\xBF");

        fputcsv($fh, [
            'lote_numero',
            'pedido_numero',
            'paciente_hc',
            'paciente_nombre',
            'determinacion_codigo',
            'determinacion_nombre',
            'nbu_unidades',
            'valor_unitario',
            'monto_item',
        ]);

        foreach ($items as $r) {
            fputcsv($fh, [
                (string) ($r['lote_numero'] ?? ''),
                (string) ($r['pedido_numero'] ?? ''),
                (string) ($r['paciente_nro_hc'] ?? ''),
                (string) ($r['paciente_nombre'] ?? ''),
                (string) ($r['determinacion_codigo'] ?? ''),
                (string) ($r['determinacion_nombre'] ?? ''),
                $r['nbu_unidades'] !== null ? (string) $r['nbu_unidades'] : '',
                $r['nbu_valor_unitario'] !== null ? (string) $r['nbu_valor_unitario'] : '',
                (string) ($r['monto_item'] ?? '0.00'),
            ]);
        }

        rewind($fh);
        $csv = (string) stream_get_contents($fh);
        fclose($fh);
        return $csv;
    }
}
