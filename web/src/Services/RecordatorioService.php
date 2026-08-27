<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/RecordatorioRepository.php';
require_once dirname(__DIR__) . '/Integration/WhatsApp/WhatsAppWebProvider.php';
require_once dirname(__DIR__) . '/Integration/WhatsApp/WahaWhatsAppProvider.php';
require_once dirname(__DIR__) . '/Integration/WhatsApp/GesisWhatsAppProvider.php';

final class RecordatorioService
{
    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;
    /** @var RecordatorioRepository */
    private $repo;
    /** @var array<string, mixed> */
    private $cfg;
    /** @var WhatsAppWebProvider|null */
    private $whatsapp;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
        $this->repo = new RecordatorioRepository($pdo, $this->idClinica);
        $this->cfg = require dirname(__DIR__, 2) . '/config/config.php';
        $this->whatsapp = $this->resolveWhatsAppProvider();
    }

    private function resolveWhatsAppProvider(): ?WhatsAppWebProvider
    {
        if (recordatorio_gesis_habilitado($this->cfg, $this->pdo, $this->idClinica)) {
            return new GesisWhatsAppProvider();
        }
        if (recordatorio_whatsapp_web_habilitado($this->cfg, $this->pdo, $this->idClinica)) {
            return WahaWhatsAppProvider::fromConfig($this->cfg);
        }

        return null;
    }

    public function whatsappSessionStatus(): ?array
    {
        if ($this->whatsapp === null) {
            return null;
        }

        return $this->whatsapp->sessionStatus();
    }

    public function whatsappProvider(): ?WhatsAppWebProvider
    {
        return $this->whatsapp;
    }

    public function gesisSessionStatus(): ?array
    {
        if (!recordatorio_gesis_configurado($this->cfg)) {
            return null;
        }
        require_once dirname(__DIR__) . '/Integration/WhatsApp/GesisWhatsAppProvider.php';

        return (new GesisWhatsAppProvider())->sessionStatus();
    }

    public function gesisModoPruebaActivo(): bool
    {
        return recordatorio_gesis_habilitado($this->cfg, $this->pdo, $this->idClinica)
            && recordatorio_gesis_test_only_nro_hc($this->cfg) > 0;
    }

    /**
     * Envío de prueba directo por API (sin cola). Solo al HC de test_only_nro_hc.
     *
     * @return array{ok:bool,error:string,paciente:string,telefono:string,message_id:?string}
     */
    public function enviarPruebaGesis(): array
    {
        if (!function_exists('gesis_whatsapp_configured')) {
            require_once dirname(__DIR__, 2) . '/includes/gesis_whatsapp_helpers.php';
        }
        if (!gesis_whatsapp_configured()) {
            return ['ok' => false, 'error' => 'gesis_whatsapp no configurado.', 'paciente' => '', 'telefono' => '', 'message_id' => null];
        }
        $nroHc = recordatorio_gesis_test_only_nro_hc($this->cfg);
        if ($nroHc < 1) {
            return ['ok' => false, 'error' => 'Definí test_only_nro_hc en config.local.php.', 'paciente' => '', 'telefono' => '', 'message_id' => null];
        }
        $estado = gesis_whatsapp_estado();
        if (!gesis_whatsapp_is_connected((string) ($estado['status'] ?? ''))) {
            return ['ok' => false, 'error' => 'WhatsApp no conectado. Abrí Gesis vincular y escaneá el QR.', 'paciente' => '', 'telefono' => '', 'message_id' => null];
        }

        $st = $this->pdo->prepare('SELECT NroHC, Apellido, Nombres, telefono, tel_celular FROM pacientes WHERE NroHC = ? LIMIT 1');
        $st->execute([$nroHc]);
        $pac = $st->fetch(PDO::FETCH_ASSOC);
        if (!is_array($pac)) {
            return ['ok' => false, 'error' => "No existe paciente HC {$nroHc}.", 'paciente' => '', 'telefono' => '', 'message_id' => null];
        }

        $tel = recordatorio_normalizar_telefono(
            trim((string) ($pac['tel_celular'] ?? '')) ?: trim((string) ($pac['telefono'] ?? ''))
        );
        if ($tel === null) {
            return ['ok' => false, 'error' => "HC {$nroHc} sin teléfono válido.", 'paciente' => '', 'telefono' => '', 'message_id' => null];
        }

        $nombre = trim((string) ($pac['Apellido'] ?? '') . ', ' . (string) ($pac['Nombres'] ?? ''));
        $texto = 'Prueba Control Salud — recordatorio WhatsApp vía Gesis.'
            . "\nPaciente: {$nombre}"
            . "\nSi recibiste esto, el envío automático funciona.";

        $send = gesis_whatsapp_enviar_mensaje($tel, $texto);
        if (!$send['ok']) {
            return ['ok' => false, 'error' => $send['error'], 'paciente' => $nombre, 'telefono' => $tel, 'message_id' => null];
        }

        return [
            'ok' => true,
            'error' => '',
            'paciente' => $nombre,
            'telefono' => $tel,
            'message_id' => $send['message_id'],
        ];
    }

    public function encolarConfirmacionTurno(int $idTurno): bool
    {
        if (!$this->repo->tableExists() || !recordatorio_auto_confirmar($this->pdo, $this->idClinica)) {
            return false;
        }
        if ($this->repo->existsForTurnoTipo($idTurno, 'confirmacion')) {
            return false;
        }
        $turno = $this->repo->turnoConPaciente($idTurno);
        if (!$turno) {
            return false;
        }
        $tel = recordatorio_normalizar_telefono(
            trim((string) ($turno['tel_celular'] ?? '')) ?: trim((string) ($turno['telefono'] ?? ''))
        );
        if ($tel === null) {
            return false;
        }

        $this->repo->insert([
            'id_turno' => $idTurno,
            'id_doctor' => (int) ($turno['Doctor'] ?? 0) ?: null,
            'nro_hc' => (int) ($turno['NroHC'] ?? 0),
            'telefono_e164' => $tel,
            'tipo' => 'confirmacion',
            'proveedor' => recordatorio_config($this->pdo, $this->idClinica, 'recordatorios.modo', 'manual'),
            'template_codigo' => 'mensajerecordatorio',
            'estado' => 'pendiente',
            'programado_en' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function encolarYProcesarConfirmacion(int $idTurno): void
    {
        if (!$this->encolarConfirmacionTurno($idTurno)) {
            return;
        }
        $this->procesarCola(5);
    }

    public function encolarAnulacionTurno(int $idTurno): bool
    {
        if (!$this->repo->tableExists() || !recordatorio_habilitado($this->pdo, $this->idClinica)) {
            return false;
        }
        if (!recordatorio_aviso_anulacion($this->pdo, $this->idClinica)) {
            return false;
        }
        if ($this->repo->existsForTurnoTipo($idTurno, 'anulacion')) {
            return false;
        }
        $turno = $this->repo->turnoConPaciente($idTurno);
        if (!$turno) {
            return false;
        }
        $tel = recordatorio_normalizar_telefono(
            trim((string) ($turno['tel_celular'] ?? '')) ?: trim((string) ($turno['telefono'] ?? ''))
        );
        if ($tel === null) {
            return false;
        }

        $this->repo->insert([
            'id_turno' => $idTurno,
            'id_doctor' => (int) ($turno['Doctor'] ?? 0) ?: null,
            'nro_hc' => (int) ($turno['NroHC'] ?? 0),
            'telefono_e164' => $tel,
            'tipo' => 'anulacion',
            'proveedor' => recordatorio_config($this->pdo, $this->idClinica, 'recordatorios.modo', 'manual'),
            'template_codigo' => 'mensajeanular',
            'estado' => 'pendiente',
            'programado_en' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function encolarYProcesarAnulacion(int $idTurno): void
    {
        if (!$this->encolarAnulacionTurno($idTurno)) {
            return;
        }
        $this->procesarCola(5);
    }

    /**
     * Encola recordatorios para turnos de $fechaTurno (habitualmente mañana).
     */
    public function encolarRecordatoriosDia(string $fechaTurno): int
    {
        if (!$this->repo->tableExists() || !recordatorio_habilitado($this->pdo, $this->idClinica)) {
            return 0;
        }
        $horaCfg = recordatorio_config($this->pdo, $this->idClinica, 'recordatorios.hora_recordatorio', '18:00');
        if (!preg_match('/^\d{2}:\d{2}$/', $horaCfg)) {
            $horaCfg = '18:00';
        }
        $programado = date('Y-m-d', strtotime($fechaTurno . ' -1 day')) . ' ' . $horaCfg . ':00';
        if (strtotime($programado) > time()) {
            // Si aún no llegó la hora del día anterior, usar ahora para no perder el lote del cron tardío.
            $programado = date('Y-m-d H:i:s');
        }

        $n = 0;
        foreach ($this->repo->turnosParaRecordatorio($fechaTurno) as $turno) {
            $idTurno = (int) ($turno['id'] ?? 0);
            if ($idTurno < 1 || $this->repo->existsForTurnoTipo($idTurno, 'recordatorio')) {
                continue;
            }
            $tel = recordatorio_normalizar_telefono(
                trim((string) ($turno['tel_celular'] ?? '')) ?: trim((string) ($turno['telefono'] ?? ''))
            );
            if ($tel === null) {
                continue;
            }
            $this->repo->insert([
                'id_turno' => $idTurno,
                'id_doctor' => (int) ($turno['Doctor'] ?? 0) ?: null,
                'nro_hc' => (int) ($turno['NroHC'] ?? 0),
                'telefono_e164' => $tel,
                'tipo' => 'recordatorio',
                'proveedor' => recordatorio_config($this->pdo, $this->idClinica, 'recordatorios.modo', 'manual'),
                'template_codigo' => 'mensajerecordatorio2',
                'estado' => 'pendiente',
                'programado_en' => $programado,
            ]);
            ++$n;
        }

        return $n;
    }

    /**
     * Prepara mensajes pendientes (equivalente al loop de Recordatorios.exe).
     *
     * @return array{procesados:int,listos:int,enviados:int,errores:int,omitidos_prueba:int}
     */
    public function procesarCola(int $limit = 100): array
    {
        $stats = ['procesados' => 0, 'listos' => 0, 'enviados' => 0, 'errores' => 0, 'omitidos_prueba' => 0];
        if (!$this->repo->tableExists()) {
            return $stats;
        }
        $apiOk = false;
        if ($this->whatsapp !== null && $this->whatsapp->isConfigured()) {
            $st = $this->whatsapp->sessionStatus();
            $apiOk = !empty($st['connected']);
        }
        foreach ($this->repo->pendientesParaProcesar($limit) as $row) {
            ++$stats['procesados'];
            $id = (int) ($row['id'] ?? 0);
            try {
                $built = $this->construirMensaje($row);
                if ($built === null) {
                    $this->repo->marcarError($id, 'No se pudo armar el mensaje.');
                    ++$stats['errores'];
                    continue;
                }
                if ($this->whatsapp !== null && $apiOk) {
                    $nroHc = (int) ($row['nro_hc'] ?? 0);
                    if (recordatorio_gesis_habilitado($this->cfg, $this->pdo, $this->idClinica)
                        && !recordatorio_gesis_puede_enviar_api($this->cfg, $nroHc)) {
                        $this->repo->marcarListo($id, $built['mensaje'], $built['wa_url']);
                        ++$stats['listos'];
                        ++$stats['omitidos_prueba'];
                        continue;
                    }
                    $tel = (string) ($built['telefono'] ?? '');
                    $send = $this->whatsapp->sendText($tel, $built['mensaje']);
                    if ($send['ok']) {
                        $this->repo->marcarEnviadoApi($id, $built['mensaje'], $built['wa_url'], $send['message_id']);
                        ++$stats['enviados'];
                        continue;
                    }
                    $this->repo->marcarError($id, $send['error'] ?? 'WhatsApp API no envió el mensaje.');
                    ++$stats['errores'];
                    continue;
                }
                $this->repo->marcarListo($id, $built['mensaje'], $built['wa_url']);
                ++$stats['listos'];
            } catch (Throwable $e) {
                $this->repo->marcarError($id, $e->getMessage());
                ++$stats['errores'];
            }
        }

        return $stats;
    }

    /**
     * Ciclo completo tipo Recordatorios.exe: encolar mañana + procesar pendientes.
     *
     * @return array<string, int>
     */
    public function ejecutarCiclo(): array
    {
        $manana = date('Y-m-d', strtotime('+1 day'));

        return [
            'encolados_recordatorio' => $this->encolarRecordatoriosDia($manana),
            'procesados' => ($p = $this->procesarCola(200))['procesados'],
            'listos' => $p['listos'],
            'enviados' => $p['enviados'],
            'errores' => $p['errores'],
            'omitidos_prueba' => $p['omitidos_prueba'],
        ];
    }

    public function marcarEnviadoManual(int $id): void
    {
        $this->repo->marcarEnviado($id);
    }

    /**
     * @param array<string, mixed> $rowCola
     * @return array{mensaje:string,wa_url:string,telefono:string}|null
     */
    private function construirMensaje(array $rowCola): ?array
    {
        $idTurno = (int) ($rowCola['id_turno'] ?? 0);
        $tipo = (string) ($rowCola['tipo'] ?? 'recordatorio');
        $turno = $this->repo->turnoConPaciente($idTurno);
        if (!$turno) {
            return null;
        }
        $tel = recordatorio_normalizar_telefono((string) ($rowCola['telefono_e164'] ?? ''));
        if ($tel === null) {
            $tel = recordatorio_normalizar_telefono(
                trim((string) ($turno['tel_celular'] ?? '')) ?: trim((string) ($turno['telefono'] ?? ''))
            );
        }
        if ($tel === null) {
            return null;
        }

        $idSuc = (int) recordatorio_config($this->pdo, $this->idClinica, 'recordatorios.sucursal_plantillas', '1');
        $plantilla = $this->repo->plantillaSucursal($idSuc, $tipo);
        if ($plantilla === '') {
            if ($tipo === 'confirmacion') {
                $plantilla = 'Le confirmamos su turno para el <fecha> a las <hora> con <medico>.';
            } elseif ($tipo === 'anulacion') {
                $plantilla = 'Le informamos que su turno del <fecha> a las <hora> con <medico> en <NOMBRE_CLINICA> fue cancelado.';
            } else {
                $plantilla = 'Le recordamos su turno para el <fecha> a las <hora> con <medico>.';
            }
        }

        $vars = recordatorio_vars_desde_turno(
            $turno,
            $turno,
            trim((string) ($turno['doctor_nombre'] ?? '')) ?: ('Profesional #' . (int) ($turno['Doctor'] ?? 0)),
            $this->pdo,
            $this->idClinica
        );
        $mensaje = recordatorio_render_plantilla($plantilla, $vars);

        return [
            'mensaje' => $mensaje,
            'wa_url' => recordatorio_wa_me_url($tel, $mensaje),
            'telefono' => $tel,
        ];
    }
}
