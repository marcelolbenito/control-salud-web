<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InformeNoConfiguradoException;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Wrapper sobre Dompdf. Toma un payload con los datos del informe y devuelve
 * los bytes del PDF generado.
 *
 * Enriquece el payload con datos institucionales (lab_config) y el
 * firmante (responsable nominal del informe) antes de pasarlo al template.
 * Lanza InformeNoConfiguradoException si falta algun dato obligatorio.
 *
 * Estructura final inyectada al template:
 *   - numero, fecha_emision, es_parcial (del caller)
 *   - pedido, paciente, resultados_por_area (del caller)
 *   - lab (assoc-array con todas las claves de lab_config)
 *   - firmante (assoc-array con apellido/nombres/matricula/titulo/firma_path)
 *   - tecnicoPrincipal (assoc-array: ['nombre' => ..., 'titulo' => ...])
 */
class InformePdfRenderer
{
    private const CLAVES_OBLIGATORIAS = [
        'laboratorio_nombre',
        'laboratorio_subtitulo',
        'laboratorio_direccion',
        'laboratorio_telefono',
        'laboratorio_resolucion_colegio',
        'laboratorio_resolucion_vencimiento',
        'laboratorio_registro_sisa_codigo',
        'laboratorio_registro_sisa_razon_social',
        'tecnico_principal_nombre',
        'tecnico_principal_titulo',
        'firmante_apellido',
        'firmante_nombres',
        'firmante_matricula',
        'firmante_titulo',
    ];

    private string $templatePath;

    public function __construct(
        private ?LabConfigService $labConfig = null,
        ?string $templatePath = null,
    ) {
        $this->templatePath = $templatePath
            ?? dirname(__DIR__, 2) . '/public/views/informes/template.php';
    }

    /**
     * @param array<string,mixed> $payload
     * @return string Bytes binarios del PDF.
     */
    public function render(array $payload): string
    {
        if ($this->labConfig !== null) {
            $payload = $this->enriquecerConDatosInstitucionales($payload);
        }

        $html = $this->renderTemplate($payload);

        // Dompdf renderea mal caracteres UTF-8 multi-byte nativos (acentos, Ñ)
        // si vienen directos en el HTML. Convertir a entities numericas
        // (&#xxx;) garantiza que se rendereen en cualquier fuente que las
        // soporte. mb_convert_encoding con 'HTML-ENTITIES' esta deprecated en
        // PHP 8.2+; mb_encode_numericentity es la alternativa estandar.
        $convmap = [0x80, 0xFFFF, 0, 0xFFFF];
        $html = mb_encode_numericentity($html, $convmap, 'UTF-8');

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        // chroot habilita que <img src="..."> con paths absolutos resuelva
        // dentro de storage/ (logos del lab, firmas escaneadas). Sin esto
        // dompdf bloquea por seguridad y muestra el alt text en su lugar.
        $options->set('chroot', dirname(__DIR__, 2));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        if (!is_string($output) || $output === '') {
            throw new \RuntimeException('Dompdf devolvio un PDF vacio');
        }
        return $output;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function enriquecerConDatosInstitucionales(array $payload): array
    {
        $lab = $this->labConfig->getDatosLaboratorio();

        foreach (self::CLAVES_OBLIGATORIAS as $clave) {
            if (empty($lab[$clave])) {
                throw InformeNoConfiguradoException::configFaltante($clave);
            }
        }

        $payload['lab'] = $lab;
        $payload['firmante'] = $lab['firmante'];
        $payload['tecnicoPrincipal'] = [
            'nombre' => (string) $lab['tecnico_principal_nombre'],
            'titulo' => (string) $lab['tecnico_principal_titulo'],
        ];
        return $payload;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function renderTemplate(array $payload): string
    {
        if (!is_file($this->templatePath)) {
            throw new \RuntimeException("Template no encontrado: {$this->templatePath}");
        }

        ob_start();
        try {
            (static function (string $__path, array $__data): void {
                extract($__data, EXTR_SKIP);
                require $__path;
            })($this->templatePath, $payload);
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
