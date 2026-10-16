<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Helpers\HemogramaLayout;
use App\Models\Informe;
use App\Repositories\InformeRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ResultadoRepository;
use Closure;
use PDO;
use Throwable;

/**
 * Genera, lista y entrega informes PDF.
 *
 * Reglas:
 *   - Para emitir un informe COMPLETO se requiere que todos los items del
 *     pedido esten validados (estado pedido = 'completo').
 *   - Para emitir un informe PARCIAL alcanza con que al menos un item este
 *     'validado'; el resto se omite. El pedido NO transiciona a 'entregado'.
 *   - El PDF se guarda en storage/informes/I-YYYY-NNNNN.pdf y se calcula
 *     SHA-256 para detectar manipulacion.
 *   - Numero correlativo I-YYYY-NNNNN con reseteo anual.
 *   - Auditoria + atomicidad.
 *   - Marcar entregado: setea entregado=1, fecha_entrega, usuario_entrega_id.
 *
 * Inyectable para tests:
 *   - InformePdfRenderer (mockeable via bypass-finals en su clase concreta).
 *   - storageDir: directorio donde se escriben los PDFs.
 *   - fileWriter: callable($path, $bytes): bool. Default file_put_contents.
 */
final class InformeService
{
    /** @var Closure(string,string):bool */
    private Closure $fileWriter;

    public function __construct(
        private PDO $db,
        private InformeRepository $informeRepo,
        private ResultadoRepository $resultadoRepo,
        private PedidoRepository $pedidoRepo,
        private InformePdfRenderer $renderer,
        private AuditoriaService $auditoria,
        private string $storageDir,
        ?Closure $fileWriter = null,
    ) {
        $this->fileWriter = $fileWriter ?? static function (string $path, string $bytes): bool {
            return file_put_contents($path, $bytes) !== false;
        };
    }

    /**
     * Genera un informe PDF para un pedido.
     *
     * @param array<string,mixed> $opciones  ['es_parcial' => bool, 'observaciones' => string|null, 'firma' => string|null]
     * @return array{id:int, numero:string, ruta_pdf:string, hash_pdf:string, es_parcial:bool}
     */
    public function generar(int $pedidoId, int $usuarioId, array $opciones = []): array
    {
        if ($pedidoId <= 0) {
            throw new ValidationException('Errores de validacion', [
                'pedido_id' => 'pedido_id invalido',
            ]);
        }

        $pedido = $this->pedidoRepo->findById($pedidoId);
        if ($pedido === null) {
            throw new ValidationException('Pedido no encontrado', [
                'pedido_id' => "Pedido $pedidoId no existe",
            ]);
        }

        $estadoPedido = (string) $pedido['estado'];
        if (in_array($estadoPedido, ['anulado'], true)) {
            throw new DomainException("El pedido esta '$estadoPedido', no se puede informar");
        }

        $esParcial = (bool) ($opciones['es_parcial'] ?? false);

        $resultados = $this->resultadoRepo->findByPedidoId($pedidoId);
        $resultadosValidados = array_values(array_filter(
            $resultados,
            static fn (array $r): bool => (string) $r['estado'] === 'validado',
        ));

        if ($resultadosValidados === []) {
            throw new DomainException(
                'No hay resultados validados para emitir un informe'
            );
        }

        if (!$esParcial && count($resultadosValidados) !== count($resultados)) {
            throw new DomainException(
                'Hay resultados sin validar: emita informe parcial o complete la validacion'
            );
        }

        $year = (int) date('Y');
        $numero = sprintf(
            'I-%04d-%05d',
            $year,
            $this->informeRepo->getNextNumeroForYear($year),
        );

        $ahora = date('Y-m-d H:i:s');
        $payload = $this->armarPayload($pedido, $resultadosValidados, $numero, $ahora, $esParcial, $opciones);

        $bytes = $this->renderer->render($payload);

        $rutaRelativa = $numero . '.pdf';
        $rutaAbsoluta = rtrim($this->storageDir, '/\\') . DIRECTORY_SEPARATOR . $rutaRelativa;

        if (!is_dir($this->storageDir) && !mkdir($this->storageDir, 0775, true) && !is_dir($this->storageDir)) {
            throw new \RuntimeException("No se pudo crear el directorio: {$this->storageDir}");
        }

        $ok = ($this->fileWriter)($rutaAbsoluta, $bytes);
        if (!$ok) {
            throw new \RuntimeException("No se pudo escribir el PDF en: $rutaAbsoluta");
        }

        $hash = hash('sha256', $bytes);

        $this->db->beginTransaction();
        try {
            $informe = new Informe(
                id: null,
                pedidoId: $pedidoId,
                numero: $numero,
                rutaPdf: $rutaRelativa,
                hashPdf: $hash,
                esParcial: $esParcial,
                usuarioEmisionId: $usuarioId,
                fechaEmision: $ahora,
                entregado: false,
                fechaEntrega: null,
                usuarioEntregaId: null,
                destinatario: null,
                observaciones: !empty($opciones['observaciones'])
                    ? (string) $opciones['observaciones']
                    : null,
            );
            $informeId = $this->informeRepo->insert($informe);

            if (!$esParcial && $estadoPedido === 'completo') {
                $this->pedidoRepo->updateEstado($pedidoId, 'entregado');
                $this->pedidoRepo->updateFechaEntrega($pedidoId, $ahora);
                $this->auditoria->log(
                    usuarioId: $usuarioId,
                    accion: 'cambiar_estado',
                    tablaAfectada: 'lab_pedidos',
                    registroId: $pedidoId,
                    valorAnterior: ['estado' => 'completo'],
                    valorNuevo: ['estado' => 'entregado'],
                    contexto: 'Emision de informe completo',
                );
            }

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'crear',
                tablaAfectada: 'lab_informes',
                registroId: $informeId,
                valorAnterior: null,
                valorNuevo: [
                    'numero'     => $numero,
                    'pedido_id'  => $pedidoId,
                    'es_parcial' => (int) $esParcial,
                    'hash_pdf'   => $hash,
                ],
                contexto: $esParcial ? 'Emision de informe parcial' : 'Emision de informe',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Limpieza best-effort del archivo si la transaccion fallo.
            if (is_file($rutaAbsoluta)) {
                @unlink($rutaAbsoluta);
            }
            throw $e;
        }

        return [
            'id'         => $informeId,
            'numero'     => $numero,
            'ruta_pdf'   => $rutaRelativa,
            'hash_pdf'   => $hash,
            'es_parcial' => $esParcial,
        ];
    }

    /**
     * Marca un informe como entregado.
     *
     * @return array<string,mixed>
     */
    public function marcarEntregado(int $informeId, int $usuarioId, ?string $destinatario): array
    {
        $informe = $this->informeRepo->findById($informeId);
        if ($informe === null) {
            throw new ValidationException('Informe no encontrado', [
                'id' => "Informe $informeId no existe",
            ]);
        }
        if ((int) $informe['entregado'] === 1) {
            throw new DomainException('El informe ya esta marcado como entregado');
        }

        $ahora = date('Y-m-d H:i:s');

        $this->db->beginTransaction();
        try {
            $this->informeRepo->marcarEntregado(
                id: $informeId,
                usuarioEntregaId: $usuarioId,
                destinatario: $destinatario,
                fechaEntrega: $ahora,
            );

            $this->auditoria->log(
                usuarioId: $usuarioId,
                accion: 'cambiar_estado',
                tablaAfectada: 'lab_informes',
                registroId: $informeId,
                valorAnterior: ['entregado' => 0],
                valorNuevo: [
                    'entregado'    => 1,
                    'destinatario' => $destinatario,
                    'fecha_entrega' => $ahora,
                ],
                contexto: 'Entrega de informe',
            );

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return [
            'id'           => $informeId,
            'entregado'    => true,
            'fecha_entrega' => $ahora,
            'destinatario' => $destinatario,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listarPorPedido(int $pedidoId): array
    {
        return $this->informeRepo->findByPedidoId($pedidoId);
    }

    /**
     * @param array<string,mixed> $filtros
     * @return array<int,array<string,mixed>>
     */
    public function listarRecientes(array $filtros = []): array
    {
        return $this->informeRepo->listarRecientes($filtros);
    }

    /**
     * Pedidos activos sin informe completo.
     *
     * @param array<string,mixed> $filtros
     * @return array<int,array<string,mixed>>
     */
    public function listarPendientes(array $filtros = []): array
    {
        return $this->informeRepo->findPendientesDeInforme($filtros);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function obtener(int $id): ?array
    {
        return $this->informeRepo->findById($id);
    }

    /**
     * @param array<string,mixed> $pedido
     * @param array<int,array<string,mixed>> $resultados
     * @param array<string,mixed> $opciones
     * @return array<string,mixed>
     */
    private function armarPayload(
        array $pedido,
        array $resultados,
        string $numero,
        string $fechaEmision,
        bool $esParcial,
        array $opciones,
    ): array {
        $snapshot = [];
        if (!empty($pedido['snapshot_paciente'])) {
            $sp = $pedido['snapshot_paciente'];
            $snapshot = is_string($sp) ? (json_decode($sp, true) ?: []) : (is_array($sp) ? $sp : []);
        }

        // El hemograma se renderiza con su layout propio (serie roja/blanca +
        // formula leucocitaria con absolutos calculados); el resto va por area.
        [$hemograma, $resto] = $this->separarHemograma($resultados);

        $porArea = [];
        foreach ($resto as $r) {
            $area = (string) ($r['area_nombre'] ?? 'Sin area');
            $porArea[$area][] = $r;
        }

        return [
            'numero'        => $numero,
            'fecha_emision' => $fechaEmision,
            'es_parcial'    => $esParcial,
            'pedido' => [
                'numero'          => (string) ($pedido['numero'] ?? ''),
                'fecha_solicitud' => (string) ($pedido['fecha_solicitud'] ?? ''),
                'medico'          => $pedido['medico_externo'] ?? null,
            ],
            'paciente' => [
                'nombre'    => $snapshot['nombre']    ?? '',
                'dni'       => $snapshot['dni']       ?? '',
                'sexo'      => $snapshot['sexo']      ?? '',
                'fecha_nac' => $snapshot['fecha_nac'] ?? '',
            ],
            'hemograma'           => $hemograma,
            'resultados_por_area' => $porArea,
            'firma' => $opciones['firma'] ?? null,
        ];
    }

    /**
     * Separa los resultados del hemograma (por codigo de determinacion) y los
     * estructura segun el layout impreso. Devuelve [estructura|null, resto].
     * El absoluto de la formula leucocitaria se calcula con el recuento de
     * globulos blancos del propio pedido.
     *
     * @param array<int,array<string,mixed>> $resultados
     * @return array{0: ?array<string,mixed>, 1: array<int,array<string,mixed>>}
     */
    private function separarHemograma(array $resultados): array
    {
        $porCodigo = [];
        $resto = [];
        foreach ($resultados as $r) {
            $cod = strtoupper((string) ($r['determinacion_codigo'] ?? ''));
            if ($cod !== '' && HemogramaLayout::esHemograma($cod)) {
                $porCodigo[$cod] = $r;
            } else {
                $resto[] = $r;
            }
        }

        if ($porCodigo === []) {
            return [null, $resultados];
        }

        $valorNum = static function (?array $r): ?float {
            if ($r === null) {
                return null;
            }
            $v = $r['valor_numerico'] ?? null;
            return ($v === null || $v === '') ? null : (float) $v;
        };

        $gb = $valorNum($porCodigo['GB'] ?? null);

        $fila = static function (string $cod) use ($porCodigo): ?array {
            if (!isset($porCodigo[$cod])) {
                return null;
            }
            $r = $porCodigo[$cod];
            $r['hemo_label'] = HemogramaLayout::etiqueta($cod, (string) ($r['determinacion_nombre'] ?? ''));
            return $r;
        };

        $indices = [];
        foreach (HemogramaLayout::SERIE_ROJA_INDICES as $cod) {
            $f = $fila($cod);
            if ($f !== null) {
                $indices[] = $f;
            }
        }

        $formula = [];
        foreach (HemogramaLayout::FORMULA as $cod) {
            $f = $fila($cod);
            if ($f === null) {
                continue;
            }
            $f['hemo_absoluto'] = HemogramaLayout::absoluto($gb, $valorNum($f));
            $formula[] = $f;
        }

        $hemograma = [
            'serie_roja'   => ['header' => $fila('GR'), 'indices' => $indices],
            'serie_blanca' => ['header' => $fila('GB'), 'formula' => $formula],
        ];

        return [$hemograma, $resto];
    }
}
