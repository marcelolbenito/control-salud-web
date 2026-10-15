<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/TurnosRepository.php';

final class AgendaWebController
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        $idClinica = $this->clinicaDesdeRequest();
        $repo = new TurnosRepository($this->pdo, $idClinica);
        $error = '';
        $paciente = $this->pacienteSesion($idClinica);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $accion = trim((string) ($_POST['accion'] ?? ''));
            if ($accion === 'login') {
                $doc = trim((string) ($_POST['documento'] ?? ''));
                $paciente = $repo->pacientePorDniExacto($doc);
                if (!$paciente) {
                    $error = 'No encontramos un paciente activo con ese número de documento.';
                } else {
                    $_SESSION['agenda_web'] = [
                        'id_clinica' => $idClinica,
                        'paciente' => $paciente,
                    ];
                    header('Location: /agenda_web.php?clinica=' . $idClinica);
                    exit;
                }
            } elseif ($accion === 'logout') {
                unset($_SESSION['agenda_web']);
                header('Location: /agenda_web.php?clinica=' . $idClinica);
                exit;
            } elseif ($accion === 'reservar') {
                $paciente = $this->pacienteSesion($idClinica);
                if (!$paciente) {
                    $error = 'Ingresá con tu documento antes de reservar.';
                } else {
                    $error = $this->reservar($repo, $paciente);
                    if ($error === '') {
                        flash_set('Turno confirmado. Ya quedó registrado en la agenda del profesional.');
                        header('Location: /agenda_web.php?clinica=' . $idClinica);
                        exit;
                    }
                }
            }
        }

        $doctor = (int) ($_GET['doctor'] ?? 0);
        $doctores = $repo->listDoctores();
        $diasLibres = [];
        if ($paciente && $doctor > 0) {
            $diasLibres = $repo->diasConHorariosLibres($doctor, date('Y-m-d'));
        }
        $turnosPaciente = $paciente ? $repo->turnosFuturosPaciente((int) $paciente['nrohc']) : [];

        $body = $this->renderView('agenda_web/index', [
            'idClinica' => $idClinica,
            'paciente' => $paciente,
            'error' => $error,
            'doctor' => $doctor,
            'doctores' => $doctores,
            'diasLibres' => $diasLibres,
            'turnosPaciente' => $turnosPaciente,
        ]);
        layout_render('Agenda web', $body, null, [
            'skip_datatables' => true,
            'body_class' => 'public-page',
            'id_clinica' => $idClinica,
        ]);
    }

    private function reservar(TurnosRepository $repo, array $paciente): string
    {
        $slot = trim((string) ($_POST['turno_slot'] ?? ''));
        if (preg_match('/^(\d{4}-\d{2}-\d{2})\|(\d{2}:\d{2})$/', $slot, $m)) {
            $fecha = $m[1];
            $hora = $m[2];
        } else {
            $fecha = trim((string) ($_POST['fecha'] ?? ''));
            $hora = trim((string) ($_POST['hora'] ?? ''));
        }
        $doctor = (int) ($_POST['doctor'] ?? 0);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha < date('Y-m-d')) {
            return 'Elegí una fecha válida, desde hoy en adelante.';
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return 'Elegí un horario disponible.';
        }
        if (!$repo->doctorDisponible($doctor)) {
            return 'El profesional seleccionado no está disponible.';
        }
        $libre = false;
        foreach ($repo->diasConHorariosLibres($doctor, $fecha, 1, 1) as $dia) {
            if (($dia['fecha'] ?? '') === $fecha && in_array($hora, $dia['horas'] ?? [], true)) {
                $libre = true;
                break;
            }
        }
        if (!$libre) {
            return 'Ese horario ya no está disponible. Elegí otra opción.';
        }

        $nroHC = (int) ($paciente['nrohc'] ?? 0);
        if ($nroHC < 1) {
            return 'No se pudo identificar la historia clínica del paciente.';
        }
        $nombre = trim((string) ($paciente['nombre'] ?? ''));
        $obs = 'Turno confirmado desde Agenda Web.';
        if ($repo->hasExtendedAgendaColumns()) {
            $idTurno = $repo->insertExtended($fecha, $hora, $nroHC, $doctor, null, 'pendiente', $obs, [
                'paciente_nombre' => $nombre,
                'motivo' => null,
                'atendido' => 0,
                'pagado' => 0,
                'llegado' => 0,
                'llegado_hora' => null,
                'confirmado' => 1,
                'falta_turno' => 0,
                'reingresar' => 0,
                'primera_vez' => 0,
                'num_sesion' => null,
                'id_sesion' => null,
                'id_caja' => null,
                'usuario_asignado' => null,
                'fechahora_asignado' => date('Y-m-d H:i:s'),
                'alta_paci_web' => 1,
            ]);
        } else {
            $idTurno = $repo->insertBase($fecha, $hora, $nroHC, $doctor, null, 'pendiente', $obs);
        }
        if ($idTurno > 0) {
            require_once dirname(__DIR__) . '/Services/RecordatorioService.php';
            (new RecordatorioService($this->pdo, $this->clinicaDesdeRequest()))->encolarYProcesarConfirmacion($idTurno);
        }

        return '';
    }

    private function clinicaDesdeRequest(): int
    {
        $raw = (int) ($_GET['clinica'] ?? $_POST['clinica'] ?? ($_SESSION['agenda_web']['id_clinica'] ?? 1));
        $id = max(1, $raw);
        if (isset($_GET['clinica'])) {
            $prev = (int) ($_SESSION['agenda_web']['id_clinica'] ?? 0);
            if ($prev > 0 && $prev !== $id) {
                unset($_SESSION['agenda_web']);
            }
        }

        return $id;
    }

    /**
     * @return array{nrohc:int,dni:string,nombre:string}|null
     */
    private function pacienteSesion(int $idClinica): ?array
    {
        $aw = $_SESSION['agenda_web'] ?? null;
        if (!is_array($aw) || (int) ($aw['id_clinica'] ?? 0) !== $idClinica || !is_array($aw['paciente'] ?? null)) {
            return null;
        }

        return $aw['paciente'];
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
