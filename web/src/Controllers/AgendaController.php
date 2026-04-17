<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/AgendaRepository.php';
require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';

final class AgendaController
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
        $fecha = trim((string) ($_GET['fecha'] ?? ''));
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }
        $doctorFiltro = (int) ($_GET['doctor'] ?? 0);

        $agendaRepo = new AgendaRepository($this->pdo);
        $docRepo = new DoctoresRepository($this->pdo);
        $extAgenda = $agendaRepo->hasExtendedColumns();
        $doctores = $docRepo->listActivos();
        $rows = $agendaRepo->listByFechaYDoctor($fecha, $doctorFiltro, $extAgenda);
        $resumen = $agendaRepo->resumenDia($fecha, $doctorFiltro, $extAgenda);
        $turnoSelId = (int) ($_GET['turno'] ?? 0);
        $turnoSel = $turnoSelId > 0 ? $agendaRepo->findById($turnoSelId, $extAgenda) : null;

        $fechaPrev = date('Y-m-d', strtotime($fecha . ' -1 day'));
        $fechaNext = date('Y-m-d', strtotime($fecha . ' +1 day'));

        $body = $this->renderView('agenda/index', [
            'extAgenda' => $extAgenda,
            'fecha' => $fecha,
            'fechaPrev' => $fechaPrev,
            'fechaNext' => $fechaNext,
            'doctorFiltro' => $doctorFiltro,
            'doctores' => $doctores,
            'rows' => $rows,
            'resumen' => $resumen,
            'turnoSel' => $turnoSel,
        ]);
        layout_render('Agenda', $body, $this->user);
    }

    public function quickStatusPost(): void
    {
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        $accion = trim((string) ($_POST['accion'] ?? ''));
        $fecha = trim((string) ($_POST['fecha'] ?? ''));
        $doctor = (int) ($_POST['doctor'] ?? 0);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }
        if ($id < 1) {
            header('Location: /agenda.php?fecha=' . rawurlencode($fecha) . ($doctor > 0 ? '&doctor=' . $doctor : ''));
            exit;
        }

        $repo = new AgendaRepository($this->pdo);
        $ok = $repo->updateQuickStatus($id, $accion, $repo->hasExtendedColumns());
        if ($ok) {
            flash_set('Estado actualizado.');
        } else {
            flash_set('No se pudo actualizar estado (requiere columnas extendidas de agenda).');
        }
        header('Location: /agenda.php?fecha=' . rawurlencode($fecha) . ($doctor > 0 ? '&doctor=' . $doctor : '') . '&turno=' . $id);
        exit;
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        return (string) ob_get_clean();
    }
}

