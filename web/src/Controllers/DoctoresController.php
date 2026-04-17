<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';

final class DoctoresController
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
        $repo = new DoctoresRepository($this->pdo);
        $extDoc = $repo->hasExtendedColumns();
        $rows = $repo->listForIndex($extDoc);

        $body = $this->renderView('doctores/index', [
            'extDoc' => $extDoc,
            'rows' => $rows,
        ]);
        layout_render('Doctores', $body, $this->user);
    }

    public function form(): void
    {
        $repo = new DoctoresRepository($this->pdo);
        $ext = $repo->hasExtendedColumns();
        $legacyAgendaDisponible = $repo->hasLegacyHorarioTable();
        $especialidadesOpts = $ext ? $repo->listEspecialidadesCatalog() : [];

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $row = [
            'id' => 0,
            'nombre' => '',
            'medicoconvenio' => 0,
            'bloquearmisconsultas' => 0,
            'activo' => 1,
            'notas' => '',
        ];
        if ($ext) {
            $row = array_merge($row, [
                'especialidad' => '', 'matricula' => '', 'telefono' => '',
                'domicilio' => '', 'localidad' => '', 'consultorio' => '',
            ]);
        }

        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Profesional no encontrado.');
                header('Location: /doctores.php');
                exit;
            }
            $row = array_merge($row, $loaded);
        }

        $agendaSemana = $this->buildAgendaSemanaDefaults();
        if ($legacyAgendaDisponible && $id > 0) {
            $legacyRow = $repo->findLegacyHorarioByDoctor($id);
            if ($legacyRow) {
                $agendaSemana = $this->buildAgendaSemanaFromLegacy($legacyRow);
            }
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $id = (int) ($_POST['id'] ?? 0);
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $medicoconvenio = isset($_POST['medicoconvenio']) ? 1 : 0;
            $bloquearmisconsultas = isset($_POST['bloquearmisconsultas']) ? 1 : 0;
            $activo = isset($_POST['activo']) ? 1 : 0;
            $notas = trim((string) ($_POST['notas'] ?? ''));

            $ex = [];
            if ($ext) {
                $especialidadCatalogoId = (int) ($_POST['especialidad_catalogo_id'] ?? 0);
                $especialidadValue = trim((string) ($_POST['especialidad'] ?? ''));
                if ($especialidadCatalogoId > 0) {
                    $nombreEsp = $repo->findEspecialidadNombreById($especialidadCatalogoId);
                    if ($nombreEsp !== null) {
                        $especialidadValue = $nombreEsp;
                    }
                }
                $ex = [
                    'especialidad' => $especialidadValue,
                    'matricula' => trim((string) ($_POST['matricula'] ?? '')),
                    'telefono' => trim((string) ($_POST['telefono'] ?? '')),
                    'domicilio' => trim((string) ($_POST['domicilio'] ?? '')),
                    'localidad' => trim((string) ($_POST['localidad'] ?? '')),
                    'consultorio' => trim((string) ($_POST['consultorio'] ?? '')),
                ];
            }

            if ($nombre === '') {
                $error = 'El nombre es obligatorio.';
            } else {
                if ($ext) {
                    if ($id > 0) {
                        $repo->updateExtended(
                            $id,
                            $nombre,
                            $medicoconvenio,
                            $bloquearmisconsultas,
                            $activo,
                            $notas,
                            $ex['especialidad'],
                            $ex['matricula'],
                            $ex['telefono'],
                            $ex['domicilio'],
                            $ex['localidad'],
                            $ex['consultorio']
                        );
                    } else {
                        $repo->insertExtended(
                            $nombre,
                            $medicoconvenio,
                            $bloquearmisconsultas,
                            $activo,
                            $notas,
                            $ex['especialidad'],
                            $ex['matricula'],
                            $ex['telefono'],
                            $ex['domicilio'],
                            $ex['localidad'],
                            $ex['consultorio']
                        );
                    }
                } else {
                    if ($id > 0) {
                        $repo->updateBase($id, $nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas);
                    } else {
                        $repo->insertBase($nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas);
                    }
                }

                $doctorId = $id > 0 ? $id : (int) $this->pdo->lastInsertId();
                if ($legacyAgendaDisponible && $doctorId > 0) {
                    try {
                        $agendaPayload = $this->buildLegacyAgendaPayloadFromPost($_POST);
                        $repo->saveLegacyHorarioByDoctor($doctorId, $agendaPayload);
                    } catch (Throwable $e) {
                        $error = 'Se guardó el profesional, pero no se pudo guardar la agenda semanal.';
                    }
                }

                if ($error === '') {
                flash_set($id > 0 ? 'Profesional actualizado.' : 'Profesional creado.');
                header('Location: /doctores.php');
                exit;
                }
            }

            $row = array_merge($row, [
                'id' => $id, 'nombre' => $nombre, 'medicoconvenio' => $medicoconvenio,
                'bloquearmisconsultas' => $bloquearmisconsultas, 'activo' => $activo, 'notas' => $notas,
            ]);
            if ($ext && isset($ex)) {
                $row = array_merge($row, $ex);
            }
            if ($legacyAgendaDisponible) {
                $agendaSemana = $this->buildAgendaSemanaFromPost($_POST);
            }
        }

        $titulo = $row['id'] ? 'Editar profesional' : 'Nuevo profesional';
        $body = $this->renderView('doctores/form', [
            'ext' => $ext,
            'row' => $row,
            'agendaSemana' => $agendaSemana,
            'legacyAgendaDisponible' => $legacyAgendaDisponible,
            'especialidadesOpts' => $especialidadesOpts,
            'error' => $error,
            'titulo' => $titulo,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /doctores.php');
            exit;
        }
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /doctores.php');
            exit;
        }
        $repo = new DoctoresRepository($this->pdo);
        $uso = $repo->linkedUsageCounts($id);
        if ($uso['total'] > 0) {
            $repo->deactivateById($id);
            $det = $uso['detalle'];
            $msg = 'El profesional tiene datos vinculados y no se eliminó físicamente. Se desactivó.';
            $resumen = [];
            if (($det['agenda_turnos'] ?? 0) > 0) {
                $resumen[] = 'turnos: ' . (int) $det['agenda_turnos'];
            }
            if (($det['ordenes'] ?? 0) > 0) {
                $resumen[] = 'órdenes: ' . (int) $det['ordenes'];
            }
            if (($det['sesiones'] ?? 0) > 0) {
                $resumen[] = 'sesiones: ' . (int) $det['sesiones'];
            }
            if (($det['consultas'] ?? 0) > 0) {
                $resumen[] = 'consultas: ' . (int) $det['consultas'];
            }
            if (($det['caja'] ?? 0) > 0) {
                $resumen[] = 'caja: ' . (int) $det['caja'];
            }
            if ($resumen !== []) {
                $msg .= ' (' . implode(', ', $resumen) . ').';
            }
            flash_set($msg);
            header('Location: /doctores.php');
            exit;
        }

        $repo->deleteById($id);
        flash_set('Profesional eliminado.');
        header('Location: /doctores.php');
        exit;
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        return (string) ob_get_clean();
    }

    /**
     * @return array<string,array<string,string>>
     */
    private function buildAgendaSemanaDefaults(): array
    {
        $dias = ['do', 'lu', 'ma', 'mi', 'ju', 'vi', 'sa'];
        $out = [];
        foreach ($dias as $d) {
            $out[$d] = [
                'duracion' => '15',
                'manana_desde' => '',
                'manana_hasta' => '',
                'tarde_desde' => '',
                'tarde_hasta' => '',
            ];
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $legacy
     * @return array<string,array<string,string>>
     */
    private function buildAgendaSemanaFromLegacy(array $legacy): array
    {
        $map = [
            'do' => ['pref' => 'Do', 'dur' => 'durtur1'],
            'lu' => ['pref' => 'Lu', 'dur' => 'durtur2'],
            'ma' => ['pref' => 'Ma', 'dur' => 'durtur3'],
            'mi' => ['pref' => 'Mi', 'dur' => 'durtur4'],
            'ju' => ['pref' => 'Ju', 'dur' => 'durtur5'],
            'vi' => ['pref' => 'Vi', 'dur' => 'durtur6'],
            'sa' => ['pref' => 'Sa', 'dur' => 'durtur7'],
        ];
        $out = $this->buildAgendaSemanaDefaults();
        foreach ($map as $k => $cfg) {
            $p = $cfg['pref'];
            $durCol = $cfg['dur'];
            $out[$k] = [
                'duracion' => (string) ((int) ($legacy[$durCol] ?? 15) ?: 15),
                'manana_desde' => $this->toHi($legacy[$p . 'MaDesde'] ?? null),
                'manana_hasta' => $this->toHi($legacy[$p . 'MaHasta'] ?? null),
                'tarde_desde' => $this->toHi($legacy[$p . 'TaDesde'] ?? null),
                'tarde_hasta' => $this->toHi($legacy[$p . 'TaHasta'] ?? null),
            ];
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $post
     * @return array<string,array<string,string>>
     */
    private function buildAgendaSemanaFromPost(array $post): array
    {
        $out = $this->buildAgendaSemanaDefaults();
        foreach ($out as $dia => $_) {
            $out[$dia]['duracion'] = (string) max(5, (int) (($post['agenda_duracion'][$dia] ?? 15)));
            $out[$dia]['manana_desde'] = trim((string) ($post['agenda_manana_desde'][$dia] ?? ''));
            $out[$dia]['manana_hasta'] = trim((string) ($post['agenda_manana_hasta'][$dia] ?? ''));
            $out[$dia]['tarde_desde'] = trim((string) ($post['agenda_tarde_desde'][$dia] ?? ''));
            $out[$dia]['tarde_hasta'] = trim((string) ($post['agenda_tarde_hasta'][$dia] ?? ''));
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $post
     * @return array<string,mixed>
     */
    private function buildLegacyAgendaPayloadFromPost(array $post): array
    {
        $sem = $this->buildAgendaSemanaFromPost($post);
        $baseDate = '1899-12-30';
        $payload = [
            'fechadesde' => date('Y-m-d'),
            'fechahasta' => '2099-12-31',
        ];
        $map = [
            'do' => ['pref' => 'Do', 'dur' => 'durtur1'],
            'lu' => ['pref' => 'Lu', 'dur' => 'durtur2'],
            'ma' => ['pref' => 'Ma', 'dur' => 'durtur3'],
            'mi' => ['pref' => 'Mi', 'dur' => 'durtur4'],
            'ju' => ['pref' => 'Ju', 'dur' => 'durtur5'],
            'vi' => ['pref' => 'Vi', 'dur' => 'durtur6'],
            'sa' => ['pref' => 'Sa', 'dur' => 'durtur7'],
        ];
        foreach ($map as $k => $cfg) {
            $p = $cfg['pref'];
            $payload[$cfg['dur']] = max(5, (int) ($sem[$k]['duracion'] ?? 15));
            $payload[$p . 'MaDesde'] = $this->toLegacyDateTime($baseDate, $sem[$k]['manana_desde'] ?? '');
            $payload[$p . 'MaHasta'] = $this->toLegacyDateTime($baseDate, $sem[$k]['manana_hasta'] ?? '');
            $payload[$p . 'TaDesde'] = $this->toLegacyDateTime($baseDate, $sem[$k]['tarde_desde'] ?? '');
            $payload[$p . 'TaHasta'] = $this->toLegacyDateTime($baseDate, $sem[$k]['tarde_hasta'] ?? '');
        }

        return $payload;
    }

    /**
     * @param mixed $value
     */
    private function toHi($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $ts = strtotime((string) $value);
        if ($ts === false) {
            return '';
        }
        return date('H:i', $ts);
    }

    private function toLegacyDateTime(string $baseDate, string $hi): ?string
    {
        $time = trim($hi);
        if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
            return null;
        }
        return $baseDate . ' ' . $time . ':00';
    }
}

