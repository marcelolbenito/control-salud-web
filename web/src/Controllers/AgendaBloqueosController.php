<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/AgendaBloqueosRepository.php';
require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';

final class AgendaBloqueosController
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
        $repo = new AgendaBloqueosRepository($this->pdo, user_clinica_id($this->user));
        if (!$repo->tableExists()) {
            $body = '<div class="container"><p class="alert alert-error">Falta la tabla <code>agenda_bloqueos</code>. Ejecutá <code>sql/migration_025_agenda_bloqueos.sql</code>.</p></div>';
            layout_render('Bloqueos de agenda', $body, $this->user);

            return;
        }

        $docRepo = new DoctoresRepository($this->pdo, user_clinica_id($this->user));
        $fdIn = trim((string) ($_GET['fd'] ?? ''));
        $fhIn = trim((string) ($_GET['fh'] ?? ''));
        $doctor = (int) ($_GET['doctor'] ?? 0);
        $fd = $fdIn !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fdIn) ? $fdIn : date('Y-m-d', strtotime('-7 days'));
        $fh = $fhIn !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fhIn) ? $fhIn : date('Y-m-d', strtotime('+60 days'));
        $rows = $repo->listForIndex($fd, $fh, $doctor);
        $doctores = $docRepo->listActivos();
        $qs = self::buildQs($fd, $fh, $doctor);

        $body = $this->renderView('agenda/bloqueos_index', [
            'rows' => $rows,
            'doctores' => $doctores,
            'fd' => $fd,
            'fh' => $fh,
            'doctor' => $doctor,
            'qs' => $qs,
        ]);
        layout_render('Bloqueos de agenda', $body, $this->user);
    }

    public function form(): void
    {
        $repo = new AgendaBloqueosRepository($this->pdo, user_clinica_id($this->user));
        if (!$repo->tableExists()) {
            flash_set('Falta la tabla agenda_bloqueos.');
            header('Location: /agenda_bloqueos.php');
            exit;
        }

        $docRepo = new DoctoresRepository($this->pdo, user_clinica_id($this->user));
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $volver = '/agenda_bloqueos.php' . self::buildQs(
            trim((string) ($_GET['fd'] ?? '')),
            trim((string) ($_GET['fh'] ?? '')),
            (int) ($_GET['doctor'] ?? 0)
        );

        $row = [
            'id' => 0,
            'doctor' => (int) ($_GET['doctor'] ?? 0),
            'fecha_desde' => isset($_GET['fd']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['fd'])
                ? (string) $_GET['fd']
                : date('Y-m-d'),
            'fecha_hasta' => isset($_GET['fh']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['fh'])
                ? (string) $_GET['fh']
                : date('Y-m-d'),
            'todo_dia' => '0',
            'hora_desde' => '',
            'hora_hasta' => '',
            'motivo' => '',
        ];

        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Bloqueo no encontrado.');
                header('Location: /agenda_bloqueos.php');
                exit;
            }
            $row['id'] = (int) $loaded['id'];
            $row['doctor'] = (int) ($loaded['doctor'] ?? 0);
            $row['fecha_desde'] = substr((string) ($loaded['fecha_desde'] ?? ''), 0, 10);
            $row['fecha_hasta'] = substr((string) ($loaded['fecha_hasta'] ?? ''), 0, 10);
            $hd = $loaded['hora_desde'] ?? null;
            $hh = $loaded['hora_hasta'] ?? null;
            $whole = ($hd === null || $hd === '') && ($hh === null || $hh === '');
            $row['todo_dia'] = $whole ? '1' : '0';
            if (!$whole) {
                $row['hora_desde'] = $hd !== null && $hd !== '' ? substr((string) $hd, 0, 5) : '';
                $row['hora_hasta'] = $hh !== null && $hh !== '' ? substr((string) $hh, 0, 5) : '';
            }
            $row['motivo'] = (string) ($loaded['motivo'] ?? '');
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $id = (int) ($_POST['id'] ?? 0);
            $doctor = (int) ($_POST['doctor'] ?? 0);
            $fd = trim((string) ($_POST['fecha_desde'] ?? ''));
            $fht = trim((string) ($_POST['fecha_hasta'] ?? ''));
            $todoDia = isset($_POST['todo_dia']);
            $hDesde = trim((string) ($_POST['hora_desde'] ?? ''));
            $hHasta = trim((string) ($_POST['hora_hasta'] ?? ''));
            $motivo = trim((string) ($_POST['motivo'] ?? ''));
            $slotsArr = self::parseBloquesSlotsJson((string) ($_POST['bloques_slots_json'] ?? ''));
            $stepMin = max(5, min(120, (int) ($_POST['bloqueo_step_min'] ?? 15)));

            if ($doctor < 1) {
                $error = 'Elegí un profesional.';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fd) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fht)) {
                $error = 'Las fechas no son válidas.';
            } elseif ($fd > $fht) {
                $error = 'La fecha desde no puede ser posterior a la fecha hasta.';
            }

            $horaDesdeSql = null;
            $horaHastaSql = null;
            if ($error === '' && !$todoDia && $slotsArr === []) {
                if (!preg_match('/^\d{2}:\d{2}$/', $hDesde) || !preg_match('/^\d{2}:\d{2}$/', $hHasta)) {
                    $error = 'Doble clic en la grilla para marcar uno o más turnos, o completá hora desde/hasta, o marcá día completo.';
                } else {
                    $horaDesdeSql = $hDesde . ':00';
                    $horaHastaSql = $hHasta . ':00';
                    $md = ((int) substr($hDesde, 0, 2)) * 60 + (int) substr($hDesde, 3, 2);
                    $mh = ((int) substr($hHasta, 0, 2)) * 60 + (int) substr($hHasta, 3, 2);
                    if ($md >= $mh) {
                        $error = 'La hora hasta debe ser mayor que la hora desde (el fin es exclusivo, como en la grilla de turnos).';
                    }
                }
            }

            if ($error === '') {
                if ($todoDia) {
                    if ($id > 0) {
                        $repo->update($id, $doctor, $fd, $fht, null, null, $motivo);
                        flash_set('Bloqueo actualizado.');
                    } else {
                        $repo->insert($doctor, $fd, $fht, null, null, $motivo);
                        flash_set('Bloqueo creado.');
                    }
                    header('Location: /agenda_bloqueos.php');
                    exit;
                }

                if ($slotsArr !== []) {
                    try {
                        $this->pdo->beginTransaction();
                        if ($id > 0) {
                            $repo->deleteById($id);
                        }
                        foreach ($slotsArr as $s) {
                            $hastaHi = self::hiMasMinutos($s, $stepMin);
                            $repo->insert($doctor, $fd, $fht, $s . ':00', $hastaHi . ':00', $motivo);
                        }
                        $this->pdo->commit();
                    } catch (Throwable $e) {
                        $this->pdo->rollBack();
                        $error = 'No se pudo guardar los bloqueos. Reintentá.';
                    }
                    if ($error === '') {
                        $n = count($slotsArr);
                        flash_set($n === 1 ? 'Bloqueo guardado.' : 'Se guardaron ' . $n . ' bloqueos (uno por horario).');
                        header('Location: /agenda_bloqueos.php');
                        exit;
                    }
                } else {
                    if ($id > 0) {
                        $repo->update($id, $doctor, $fd, $fht, $horaDesdeSql, $horaHastaSql, $motivo);
                        flash_set('Bloqueo actualizado.');
                    } else {
                        $repo->insert($doctor, $fd, $fht, $horaDesdeSql, $horaHastaSql, $motivo);
                        flash_set('Bloqueo creado.');
                    }
                    header('Location: /agenda_bloqueos.php');
                    exit;
                }
            }

            $pickedJson = trim((string) ($_POST['bloques_slots_json'] ?? ''));
            if ($pickedJson === '' || $pickedJson === '[]') {
                $pickedJson = '[]';
            }
            $row = [
                'id' => $id,
                'doctor' => $doctor,
                'fecha_desde' => $fd,
                'fecha_hasta' => $fht,
                'todo_dia' => $todoDia ? '1' : '0',
                'hora_desde' => $hDesde,
                'hora_hasta' => $hHasta,
                'motivo' => $motivo,
                'picked_slots_json' => $pickedJson,
            ];
        }

        $pickedSlotsJson = '[]';
        if (($row['picked_slots_json'] ?? '') !== '' && $row['picked_slots_json'] !== '[]') {
            $pickedSlotsJson = (string) $row['picked_slots_json'];
        } elseif ((int) ($row['id'] ?? 0) > 0 && ($row['todo_dia'] ?? '') !== '1'
            && preg_match('/^\d{2}:\d{2}$/', (string) ($row['hora_desde'] ?? ''))) {
            $pickedSlotsJson = json_encode([(string) $row['hora_desde']], JSON_UNESCAPED_UNICODE);
        }

        $titulo = $row['id'] ? 'Editar bloqueo' : 'Nuevo bloqueo';
        $body = $this->renderView('agenda/bloqueo_form', [
            'row' => $row,
            'error' => $error,
            'titulo' => $titulo,
            'volver' => $volver,
            'doctores' => $docRepo->listActivos(),
            'picked_slots_json' => $pickedSlotsJson,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    /**
     * @return list<string> HH:MM
     */
    private static function parseBloquesSlotsJson(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $d = json_decode($raw, true);
        if (!is_array($d)) {
            return [];
        }
        $out = [];
        foreach ($d as $x) {
            if (is_string($x) && preg_match('/^\d{2}:\d{2}$/', $x)) {
                $out[] = $x;
            }
        }
        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }

    private static function hiMasMinutos(string $hi, int $addM): string
    {
        if (!preg_match('/^(\d{2}):(\d{2})$/', $hi, $m)) {
            return '00:00';
        }
        $t = (int) $m[1] * 60 + (int) $m[2] + max(1, $addM);
        $t = (($t % 1440) + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
    }

    private static function buildQs(string $fd, string $fh, int $doctor): string
    {
        $q = [];
        if ($fd !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fd)) {
            $q['fd'] = $fd;
        }
        if ($fh !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fh)) {
            $q['fh'] = $fh;
        }
        if ($doctor > 0) {
            $q['doctor'] = (string) $doctor;
        }

        return $q === [] ? '' : '?' . http_build_query($q);
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
