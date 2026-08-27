<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/RecordatorioRepository.php';
require_once dirname(__DIR__) . '/Services/RecordatorioService.php';

final class RecordatoriosController
{
    /** @var PDO */
    private $pdo;
    /** @var array|null */
    private $user;

    public function __construct(PDO $pdo, ?array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function index(): void
    {
        $cid = user_clinica_id($this->user);
        $repo = new RecordatorioRepository($this->pdo, $cid);
        $svc = new RecordatorioService($this->pdo, $cid);
        $error = '';
        $ok = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $accion = trim((string) ($_POST['accion'] ?? ''));
            if (!$repo->tableExists()) {
                $error = 'Falta aplicar sql/migration_035_agenda_recordatorios.sql';
            } elseif ($accion === 'ciclo') {
                $r = $svc->ejecutarCiclo();
                $ok = 'Ciclo ejecutado: encolados ' . $r['encolados_recordatorio']
                    . ', enviados ' . ($r['enviados'] ?? 0)
                    . ', listos ' . $r['listos']
                    . ', omitidos prueba ' . ($r['omitidos_prueba'] ?? 0)
                    . ', errores ' . $r['errores'] . '.';
            } elseif ($accion === 'procesar') {
                $p = $svc->procesarCola(200);
                $ok = 'Cola procesada: enviados ' . $p['enviados']
                    . ', listos ' . $p['listos']
                    . ', omitidos prueba ' . $p['omitidos_prueba']
                    . ', errores ' . $p['errores'] . '.';
            } elseif ($accion === 'prueba_gesis') {
                $pr = $svc->enviarPruebaGesis();
                if ($pr['ok']) {
                    $ok = 'Prueba enviada a ' . $pr['paciente'] . ' (' . $pr['telefono'] . ')'
                        . ($pr['message_id'] !== null ? ' — id ' . $pr['message_id'] : '') . '.';
                } else {
                    $error = $pr['error'];
                }
            } elseif ($accion === 'enviado') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $svc->marcarEnviadoManual($id);
                    $ok = 'Marcado como enviado.';
                }
            }
        }

        $estado = trim((string) ($_GET['estado'] ?? ''));
        $rows = $repo->tableExists() ? $repo->listBandeja($estado) : [];
        $habilitado = recordatorio_habilitado($this->pdo, $cid);
        $autoConf = recordatorio_auto_confirmar($this->pdo, $cid);
        $modo = recordatorio_modo($this->pdo, $cid);
        $waStatus = $svc->whatsappSessionStatus();
        $waProvider = $svc->whatsappProvider();
        $cfg = require dirname(__DIR__, 2) . '/config/config.php';
        $waCfg = $cfg['whatsapp_web'] ?? [];
        $waHabilitado = recordatorio_whatsapp_web_habilitado($cfg, $this->pdo, $cid);
        $gesisHabilitado = recordatorio_gesis_habilitado($cfg, $this->pdo, $cid);
        $gesisPruebaHc = recordatorio_gesis_test_only_nro_hc($cfg);
        $gesisConfigurado = recordatorio_gesis_configurado($cfg);

        $body = $this->renderView('recordatorios/index', [
            'rows' => $rows,
            'estado' => $estado,
            'error' => $error,
            'ok' => $ok,
            'habilitado' => $habilitado,
            'autoConf' => $autoConf,
            'tablaOk' => $repo->tableExists(),
            'modo' => $modo,
            'waHabilitado' => $waHabilitado,
            'waStatus' => $waStatus,
            'waDashboard' => $waProvider && method_exists($waProvider, 'dashboardUrl')
                ? $waProvider->dashboardUrl()
                : (string) ($waCfg['base_url'] ?? ''),
            'gesisHabilitado' => $gesisHabilitado,
            'gesisConfigurado' => $gesisConfigurado,
            'gesisPruebaHc' => $gesisPruebaHc,
            'gesisStatus' => $gesisConfigurado ? $svc->gesisSessionStatus() : null,
        ]);
        layout_render('Recordatorios WhatsApp', $body, $this->user);
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
