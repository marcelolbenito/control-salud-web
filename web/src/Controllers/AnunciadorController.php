<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/AnunciadorRepository.php';

final class AnunciadorController
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
        $repo = new AnunciadorRepository($this->pdo, user_clinica_id($this->user));
        $estado = (string) ($_GET['estado'] ?? 'llamando');
        $modo = (string) ($_GET['modo'] ?? 'monitor');
        $isOperador = $modo === 'operador';
        $rows = $repo->listarSala($estado, 40);
        $rowsEnConsultorio = $repo->listarSala('en_consultorio', 20);
        $autoRefresh = max(2, min(30, (int) ($_GET['refresh'] ?? 5)));

        if (!$isOperador) {
            $html = $this->renderView('agenda/anunciador_monitor', [
                'rows' => $rows,
                'rowsEnConsultorio' => $rowsEnConsultorio,
                'tablaDisponible' => $repo->existeTabla(),
                'autoRefresh' => $autoRefresh,
            ]);
            echo $html;
            return;
        }

        $body = $this->renderView('agenda/anunciador', [
            'estado' => $estado,
            'modo' => $modo,
            'isOperador' => $isOperador,
            'rows' => $rows,
            'rowsEnConsultorio' => $rowsEnConsultorio,
            'tablaDisponible' => $repo->existeTabla(),
            'autoRefresh' => $autoRefresh,
        ]);
        layout_render('Anunciador (Operador)', $body, $this->user, ['skip_datatables' => true]);
    }

    public function cambiarEstadoPost(): void
    {
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $repo = new AnunciadorRepository($this->pdo, user_clinica_id($this->user));
        $ok = $repo->cambiarEstado($id, $estado, (int) ($this->user['id'] ?? 0));
        flash_set($ok ? 'Estado de llamado actualizado.' : 'No se pudo actualizar el llamado.');
        header('Location: /anunciador.php?modo=operador');
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

