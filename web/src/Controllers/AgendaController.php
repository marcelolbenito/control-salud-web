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

        $body = $this->renderView('agenda/index', [
            'extAgenda' => $extAgenda,
            'fecha' => $fecha,
            'doctorFiltro' => $doctorFiltro,
            'doctores' => $doctores,
            'rows' => $rows,
        ]);
        layout_render('Agenda', $body, $this->user);
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        return (string) ob_get_clean();
    }
}

