<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/FacturacionElectronica/FacturaElectronicaService.php';
require_once dirname(__DIR__) . '/Repositories/FacturaElectronicaRepository.php';

use ControlSalud\FacturacionElectronica\FacturaElectronicaService;
use ControlSalud\FacturacionElectronica\GesisArcaClient;

final class FacturaElectronicaController
{
    private PDO $pdo;
    /** @var array<string, mixed> */
    private array $user;
    private int $idClinica;
    private FacturaElectronicaService $svc;

    /**
     * @param array<string, mixed> $user
     */
    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->idClinica = user_clinica_id($user);
        $this->svc = new FacturaElectronicaService($pdo, $this->idClinica);
    }

    public function index(): void
    {
        require_roles(['superadmin', 'admin_clinica']);
        $repo = $this->svc->repo();
        $hasTable = $repo->tablesOk();
        $cfg = $hasTable ? $repo->gesisConfig() : [];
        $gesisOk = $hasTable && (new GesisArcaClient($cfg))->isConfigured();
        $rows = $hasTable ? $repo->listar() : [];
        $flash = flash_take();
        $error = '';
        $form = [
            'id_paciente' => (int) ($_GET['id_paciente'] ?? 0),
            'paciente_nombre' => '',
            'paciente_doc' => '',
            'concepto' => '',
            'importe' => '',
        ];

        if ($form['id_paciente'] > 0) {
            $this->rellenarPaciente($form);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['accion'] ?? '') === 'emitir') {
            csrf_verify();
            $form['id_paciente'] = (int) ($_POST['id_paciente'] ?? 0);
            $form['paciente_nombre'] = trim((string) ($_POST['paciente_nombre'] ?? ''));
            $form['paciente_doc'] = trim((string) ($_POST['paciente_doc'] ?? ''));
            $form['concepto'] = trim((string) ($_POST['concepto'] ?? ''));
            $form['importe'] = trim((string) ($_POST['importe'] ?? ''));
            $importe = (float) str_replace(',', '.', $form['importe']);
            $res = $this->svc->emitir([
                'id_paciente' => $form['id_paciente'] > 0 ? $form['id_paciente'] : null,
                'paciente_nombre' => $form['paciente_nombre'],
                'paciente_doc' => $form['paciente_doc'],
                'concepto' => $form['concepto'],
                'importe' => $importe,
                'id_usuario' => (int) ($this->user['id'] ?? 0),
            ]);
            if ($res['ok']) {
                flash_set(
                    'Factura autorizada. CAE ' . ($res['cae'] ?? '')
                    . ' · N° ' . sprintf('%05d-%08d', (int) ($res['punto_venta'] ?? 0), (int) ($res['numero'] ?? 0))
                );
                header('Location: /fe_imprimir.php?id=' . (int) ($res['id'] ?? 0));
                exit;
            }
            $error = (string) ($res['msg'] ?? 'No se pudo emitir.');
        }

        $body = $this->render('factura_electronica/index', [
            'hasTable' => $hasTable,
            'gesisOk' => $gesisOk,
            'production' => !empty($cfg['production']),
            'rows' => $rows,
            'flash' => $flash,
            'error' => $error,
            'form' => $form,
        ]);
        layout_render('Facturación electrónica', $body, $this->user, ['skip_datatables' => true]);
    }

    public function parametros(): void
    {
        require_roles(['superadmin', 'admin_clinica']);
        $repo = $this->svc->repo();
        $hasTable = $repo->tablesOk();
        $flash = flash_take();
        $error = '';
        $row = $hasTable ? $repo->getParametros() : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasTable) {
            csrf_verify();
            try {
                $repo->saveParametros($_POST);
                flash_set('Parámetros de facturación electrónica guardados.');
                header('Location: /fe_parametros.php');
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
                $row = array_merge($row, $_POST);
            }
        }

        $body = $this->render('factura_electronica/parametros', [
            'hasTable' => $hasTable,
            'row' => $row,
            'flash' => $flash,
            'error' => $error,
        ]);
        layout_render('FE — Parámetros', $body, $this->user, ['skip_datatables' => true]);
    }

    public function imprimir(): void
    {
        require_roles(['superadmin', 'admin_clinica']);
        $id = (int) ($_GET['id'] ?? 0);
        $repo = $this->svc->repo();
        $comp = $repo->findById($id);
        if ($comp === null) {
            http_response_code(404);
            echo 'Comprobante no encontrado.';
            exit;
        }
        $param = $repo->getParametros();
        echo FacturaElectronicaService::renderPrintHtml($comp, $param);
        exit;
    }

    /**
     * @param array<string, mixed> $form
     */
    private function rellenarPaciente(array &$form): void
    {
        $id = (int) $form['id_paciente'];
        if ($id < 1) {
            return;
        }
        $hasAp = db_table_has_column($this->pdo, 'pacientes', 'apellido');
        $sql = 'SELECT id, NroHC, Nombres, DNI' . ($hasAp ? ', apellido' : '') . ' FROM pacientes WHERE id = ?';
        $par = [$id];
        if (db_table_has_column($this->pdo, 'pacientes', 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql . ' LIMIT 1');
        $st->execute($par);
        $p = $st->fetch(PDO::FETCH_ASSOC);
        if (!$p) {
            return;
        }
        $nom = trim(
            ($hasAp ? trim((string) ($p['apellido'] ?? '')) . ', ' : '')
            . trim((string) ($p['Nombres'] ?? ''))
        );
        $form['paciente_nombre'] = trim($nom, ' ,');
        $form['paciente_doc'] = trim((string) ($p['DNI'] ?? ''));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
