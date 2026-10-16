<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Layout de presentacion del Hemograma, igual al informe impreso modelo:
 *
 *   HEMOGRAMA  (metodo del equipo)
 *     Globulos rojos recuento ........ (serie roja, header)
 *       Hematocrito / Hemoglobina / VCM / HCM / CHCM / RDW   (indices, indentados)
 *     Globulos blancos recuento ...... (serie blanca, header)
 *     FORMULA LEUCOCITARIA   Relativa | Absoluta
 *       Neutrofilos / Eosinofilos / Basofilos / Linfocitos / Monocitos
 *         (el absoluto se CALCULA = globulos blancos x relativo / 100)
 *
 * Se identifica cada renglon por el codigo de la determinacion (GR, HTO, ...).
 * Las etiquetas de presentacion pueden diferir del nombre del nomenclador.
 *
 * Nota: este layout esta replicado en el front (items-panel.js, const
 * HEMOGRAMA). Si se cambia aca, actualizar tambien alla.
 */
final class HemogramaLayout
{
    public const PERFIL_CODIGO = 'HMG';

    public const SERIE_ROJA_HEADER  = 'GR';
    public const SERIE_ROJA_INDICES = ['HTO', 'HB', 'VCM', 'HCM', 'CHCM', 'RDW'];
    public const SERIE_BLANCA_HEADER = 'GB';
    public const FORMULA            = ['NEU', 'EOS', 'BAS', 'LIN', 'MON'];

    /** codigo => etiqueta de presentacion (como el informe impreso modelo). */
    public const LABELS = [
        'GR'   => 'Globulos rojos recuento',
        'HTO'  => 'Hematocrito',
        'HB'   => 'Hemoglobina dosaje',
        'VCM'  => 'Volumen Corpuscular Medio',
        'HCM'  => 'Hemoglobina Corpuscular Media',
        'CHCM' => 'Conc.Hemoglobina Corpuscular Media',
        'RDW'  => 'Rdw (Amplitud de distribucion)',
        'GB'   => 'Globulos blancos recuento',
        'NEU'  => 'Neutrofilos Segmentados',
        'EOS'  => 'Eosinofilos',
        'BAS'  => 'Basofilos',
        'LIN'  => 'Linfocitos',
        'MON'  => 'Monocitos',
    ];

    /**
     * Todos los codigos del hemograma, en orden de presentacion.
     *
     * @return string[]
     */
    public static function codigos(): array
    {
        return array_merge(
            [self::SERIE_ROJA_HEADER],
            self::SERIE_ROJA_INDICES,
            [self::SERIE_BLANCA_HEADER],
            self::FORMULA,
        );
    }

    public static function esHemograma(string $codigo): bool
    {
        return in_array(strtoupper($codigo), self::codigos(), true);
    }

    public static function etiqueta(string $codigo, string $fallback = ''): string
    {
        return self::LABELS[strtoupper($codigo)] ?? $fallback;
    }

    /**
     * Absoluto de la formula leucocitaria = globulos blancos x relativo / 100.
     * Devuelve null si falta el recuento de blancos o el relativo.
     */
    public static function absoluto(?float $globulosBlancos, ?float $relativoPorcentaje): ?float
    {
        if ($globulosBlancos === null || $relativoPorcentaje === null) {
            return null;
        }
        return round($globulosBlancos * $relativoPorcentaje / 100);
    }
}
