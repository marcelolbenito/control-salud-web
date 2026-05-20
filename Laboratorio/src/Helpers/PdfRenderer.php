<?php

declare(strict_types=1);

namespace App\Helpers;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;
use Throwable;

/**
 * Wrapper genérico de Dompdf. Renderiza un template PHP con el payload
 * recibido y devuelve los bytes del PDF.
 *
 * No es final para que los tests puedan mockearlo via bypass-finals.
 */
class PdfRenderer
{
    /**
     * Pares (paper, orientation) admitidos.
     */
    private const PAPERS = ['A4', 'A5', 'A6', 'Letter'];

    public function __construct(
        private string $defaultFont = 'DejaVu Sans',
        private string $paper = 'A4',
        private string $orientation = 'portrait',
    ) {
        if (!in_array($this->paper, self::PAPERS, true)) {
            throw new RuntimeException("Paper invalido: $paper");
        }
        if (!in_array($this->orientation, ['portrait', 'landscape'], true)) {
            throw new RuntimeException("Orientation invalida: $orientation");
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return string PDF bytes.
     */
    public function render(string $templatePath, array $payload): string
    {
        if (!is_file($templatePath)) {
            throw new RuntimeException("Template no encontrado: $templatePath");
        }

        $html = $this->renderTemplate($templatePath, $payload);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', $this->defaultFont);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($this->paper, $this->orientation);
        $dompdf->render();

        $output = $dompdf->output();
        if (!is_string($output) || $output === '') {
            throw new RuntimeException('Dompdf devolvio un PDF vacio');
        }
        return $output;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function renderTemplate(string $templatePath, array $payload): string
    {
        ob_start();
        try {
            (static function (string $__path, array $__data): void {
                extract($__data, EXTR_SKIP);
                require $__path;
            })($templatePath, $payload);
            return (string) ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
