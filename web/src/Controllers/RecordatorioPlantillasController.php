<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/RecordatorioRepository.php';

final class RecordatorioPlantillasController
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

    public function form(): void
    {
        $this->requireAdmin();
        $cid = user_clinica_id($this->user);
        $repo = new RecordatorioRepository($this->pdo, $cid);
        $error = '';
        $ok = '';

        $sucursales = $repo->listSucursalesPlantillas();
        if ($sucursales === []) {
            $error = 'No existe la tabla Sucursales o no hay sucursales cargadas.';
        }

        $idSuc = (int) ($_GET['sucursal'] ?? $_POST['id_sucursal'] ?? 0);
        if ($idSuc < 1) {
            $idSuc = (int) recordatorio_config($this->pdo, $cid, 'recordatorios.sucursal_plantillas', '1');
        }
        if ($idSuc < 1 && $sucursales !== []) {
            $idSuc = (int) $sucursales[0]['id'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $idSuc = (int) ($_POST['id_sucursal'] ?? 0);
            $confirmacion = (string) ($_POST['mensajerecordatorio'] ?? '');
            $recordatorio = (string) ($_POST['mensajerecordatorio2'] ?? '');
            $anulacion = (string) ($_POST['mensajeanular'] ?? '');
            $usarEnRecordatorios = isset($_POST['usar_en_recordatorios']);
            $nombreClinica = trim((string) ($_POST['nombre_clinica'] ?? ''));
            $direccionClinica = trim((string) ($_POST['direccion_clinica'] ?? ''));
            $avisoAnulacion = isset($_POST['aviso_anulacion']) ? '1' : '0';

            if ($idSuc < 1) {
                $error = 'Elegí una sucursal.';
            } elseif (!$repo->guardarPlantillasSucursal($idSuc, $confirmacion, $recordatorio, $anulacion)) {
                $error = 'No se pudieron guardar las plantillas.';
            } else {
                if ($usarEnRecordatorios) {
                    recordatorio_set_config($this->pdo, $cid, 'recordatorios.sucursal_plantillas', (string) $idSuc);
                }
                if ($nombreClinica !== '') {
                    recordatorio_set_config($this->pdo, $cid, 'recordatorios.nombre_clinica', $nombreClinica);
                }
                if ($direccionClinica !== '') {
                    recordatorio_set_config($this->pdo, $cid, 'recordatorios.direccion_clinica', $direccionClinica);
                }
                recordatorio_set_config($this->pdo, $cid, 'recordatorios.aviso_anulacion', $avisoAnulacion);
                $ok = 'Plantillas guardadas.';
            }
        }

        $plantillas = $idSuc > 0 ? $repo->plantillasSucursal($idSuc) : null;
        if ($plantillas === null && $error === '' && $sucursales !== []) {
            $error = 'Sucursal no encontrada.';
        }

        $sucursalActiva = (int) recordatorio_config($this->pdo, $cid, 'recordatorios.sucursal_plantillas', '1');

        $body = $this->renderView('recordatorios/plantillas', [
            'error' => $error,
            'ok' => $ok,
            'sucursales' => $sucursales,
            'idSuc' => $idSuc,
            'sucursalActiva' => $sucursalActiva,
            'nombreClinica' => recordatorio_nombre_clinica($this->pdo, $cid),
            'direccionClinica' => recordatorio_direccion_clinica($this->pdo, $cid),
            'avisoAnulacion' => recordatorio_aviso_anulacion($this->pdo, $cid),
            'plantillas' => $plantillas ?? [
                'nombre' => '',
                'mensajerecordatorio' => '',
                'mensajerecordatorio2' => '',
                'mensajeanular' => '',
            ],
        ]);
        layout_render('Plantillas WhatsApp', $body, $this->user, ['skip_datatables' => true]);
    }

    private function requireAdmin(): void
    {
        if (!in_array(auth_user_role($this->user), ['superadmin', 'admin_clinica'], true)) {
            http_response_code(403);
            exit('Solo administradores pueden editar plantillas.');
        }
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
