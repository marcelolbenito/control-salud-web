<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/TurnosRepository.php';

final class TurnosController
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
        require_once dirname(__DIR__, 2) . '/includes/catalogos.php';

        $repo = new TurnosRepository($this->pdo);
        $ext = $repo->hasExtendedAgendaColumns();
        $doctores = $repo->listDoctores();
        $primeraVezOpts = catalogo_lista($this->pdo, 'lista_primera_vez', 'prioridad_id');

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $defaultsFecha = trim((string) ($_GET['fecha'] ?? ''));
        $defaultsDoctor = (int) ($_GET['doctor'] ?? 0);

        $row = [
            'id' => 0,
            'Fecha' => $defaultsFecha !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $defaultsFecha) ? $defaultsFecha : date('Y-m-d'),
            'hora' => '',
            'NroHC' => '',
            'Doctor' => $defaultsDoctor > 0 ? $defaultsDoctor : '',
            'idorden' => '',
            'estado' => 'pendiente',
            'observaciones' => '',
        ];
        if ($ext) {
            $row = array_merge($row, [
                'paciente_nombre' => '', 'motivo' => null, 'atendido' => 0, 'pagado' => 0, 'llegado' => 0,
                'llegado_hora' => '', 'confirmado' => 0, 'falta_turno' => 0, 'reingresar' => 0, 'primera_vez' => '',
                'num_sesion' => '', 'id_sesion' => '', 'id_caja' => '', 'usuario_asignado' => '', 'fechahora_asignado' => '',
                'alta_paci_web' => '',
            ]);
        }

        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Turno no encontrado.');
                header('Location: /agenda.php');
                exit;
            }
            $row = array_merge($row, $loaded);
            $row['hora'] = $row['hora'] ? substr((string) $row['hora'], 0, 5) : '';
            $row['idorden'] = $row['idorden'] !== null ? (string) $row['idorden'] : '';
            if ($ext) {
                $row['llegado_hora'] = $row['llegado_hora'] ? (string) $row['llegado_hora'] : '';
                $row['fechahora_asignado'] = !empty($row['fechahora_asignado'])
                    ? date('Y-m-d\TH:i', strtotime((string) $row['fechahora_asignado']))
                    : '';
                $row['num_sesion'] = $row['num_sesion'] !== null ? (string) $row['num_sesion'] : '';
                $row['id_sesion'] = $row['id_sesion'] !== null ? (string) $row['id_sesion'] : '';
                $row['id_caja'] = $row['id_caja'] !== null ? (string) $row['id_caja'] : '';
                $row['primera_vez'] = $row['primera_vez'] !== null ? (string) $row['primera_vez'] : '';
                $row['alta_paci_web'] = $row['alta_paci_web'] !== null ? (string) $row['alta_paci_web'] : '';
            }
        }

        $estados = ['pendiente', 'atendido', 'cancelado', 'no_asistio'];
        $error = '';

        $formatearPacienteActual = static function (string $nombre, string $dni): string {
            $nom = trim($nombre);
            $doc = trim($dni);
            if ($nom !== '' && $doc !== '') {
                return $nom . ' - DNI ' . $doc;
            }
            return $nom;
        };

        $pacienteActual = '';
        $nroHcActual = (int) ($row['NroHC'] ?? 0);
        if ($nroHcActual > 0) {
            $paciente = $repo->pacientePorNroHC($nroHcActual);
            $nombreBase = $ext && trim((string) ($row['paciente_nombre'] ?? '')) !== ''
                ? trim((string) $row['paciente_nombre'])
                : (string) (($paciente['nombre'] ?? '') ?: $repo->pacienteNombreParaTurno($nroHcActual));
            $dniBase = (string) ($paciente['dni'] ?? '');
            $pacienteActual = $formatearPacienteActual($nombreBase, $dniBase);
            if ($ext && trim((string) ($row['paciente_nombre'] ?? '')) === '' && $nombreBase !== '') {
                $row['paciente_nombre'] = $nombreBase;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $Fecha = trim((string) ($_POST['Fecha'] ?? ''));
            $horaRaw = trim((string) ($_POST['hora'] ?? ''));
            $NroHC = (int) ($_POST['NroHC'] ?? 0);
            $Doctor = (int) ($_POST['Doctor'] ?? 0);
            $idordenRaw = trim((string) ($_POST['idorden'] ?? ''));
            $estado = trim((string) ($_POST['estado'] ?? 'pendiente'));
            $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

            $hora = $horaRaw === '' ? null : $horaRaw;
            $idorden = $idordenRaw === '' ? null : (int) $idordenRaw;

            $ex = [];
            if ($ext) {
                $ex = [
                    'paciente_nombre' => trim((string) ($_POST['paciente_nombre'] ?? '')),
                    'motivo' => post_int_null('motivo'),
                    'atendido' => isset($_POST['atendido']) ? 1 : 0,
                    'pagado' => isset($_POST['pagado']) ? 1 : 0,
                    'llegado' => isset($_POST['llegado']) ? 1 : 0,
                    'llegado_hora' => trim((string) ($_POST['llegado_hora'] ?? '')) ?: null,
                    'confirmado' => isset($_POST['confirmado']) ? 1 : 0,
                    'falta_turno' => isset($_POST['falta_turno']) ? 1 : 0,
                    'reingresar' => isset($_POST['reingresar']) ? 1 : 0,
                    'primera_vez' => post_int_null('primera_vez'),
                    'num_sesion' => post_int_null('num_sesion'),
                    'id_sesion' => post_int_null('id_sesion'),
                    'id_caja' => post_int_null('id_caja'),
                    'usuario_asignado' => trim((string) ($_POST['usuario_asignado'] ?? '')) ?: null,
                    'alta_paci_web' => post_int_null('alta_paci_web'),
                ];
                $fh = trim((string) ($_POST['fechahora_asignado'] ?? ''));
                $ex['fechahora_asignado'] = $fh === '' ? null : str_replace('T', ' ', $fh) . ':00';

                if ($ex['paciente_nombre'] === '' && $NroHC > 0) {
                    $ex['paciente_nombre'] = $repo->pacienteNombreParaTurno($NroHC);
                }
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $Fecha)) {
                $error = 'La fecha no es válida.';
            } elseif ($NroHC < 1) {
                $error = 'Indicá un número de historia clínica válido.';
            } elseif ($Doctor < 1) {
                $error = 'Elegí un profesional.';
            } elseif (!in_array($estado, $estados, true)) {
                $error = 'Estado no válido.';
            } elseif (!$repo->pacienteExistsByNroHC($NroHC)) {
                $error = 'No existe un paciente con ese Nro HC. Cargalo primero en Pacientes.';
            }

            if ($error === '') {
                if ($ext) {
                    if ($estado === 'atendido') {
                        $ex['atendido'] = 1;
                    }
                    if ($id > 0) {
                        $repo->updateExtended(
                            $id,
                            $Fecha,
                            $hora,
                            $NroHC,
                            $Doctor,
                            $idorden,
                            $estado,
                            $observaciones,
                            $ex
                        );
                    } else {
                        $repo->insertExtended(
                            $Fecha,
                            $hora,
                            $NroHC,
                            $Doctor,
                            $idorden,
                            $estado,
                            $observaciones,
                            $ex
                        );
                    }
                } else {
                    if ($id > 0) {
                        $repo->updateBase($id, $Fecha, $hora, $NroHC, $Doctor, $idorden, $estado, $observaciones);
                    } else {
                        $repo->insertBase($Fecha, $hora, $NroHC, $Doctor, $idorden, $estado, $observaciones);
                    }
                }
                flash_set($id > 0 ? 'Turno actualizado.' : 'Turno creado.');
                header('Location: /agenda.php?fecha=' . urlencode($Fecha) . ($Doctor > 0 ? '&doctor=' . $Doctor : ''));
                exit;
            }

            $row = array_merge($row, [
                'id' => $id, 'Fecha' => $Fecha, 'hora' => $horaRaw, 'NroHC' => $NroHC, 'Doctor' => $Doctor,
                'idorden' => $idordenRaw, 'estado' => $estado, 'observaciones' => $observaciones,
            ]);
            if ($ext && isset($ex)) {
                $row = array_merge($row, $ex);
            }

            $pacienteActual = '';
            $nroHcActual = (int) ($row['NroHC'] ?? 0);
            if ($nroHcActual > 0) {
                $paciente = $repo->pacientePorNroHC($nroHcActual);
                $nombreBase = $ext && trim((string) ($row['paciente_nombre'] ?? '')) !== ''
                    ? trim((string) $row['paciente_nombre'])
                    : (string) (($paciente['nombre'] ?? '') ?: $repo->pacienteNombreParaTurno($nroHcActual));
                $dniBase = (string) ($paciente['dni'] ?? '');
                $pacienteActual = $formatearPacienteActual($nombreBase, $dniBase);
                if ($ext && trim((string) ($row['paciente_nombre'] ?? '')) === '' && $nombreBase !== '') {
                    $row['paciente_nombre'] = $nombreBase;
                }
            }
        }

        $titulo = $row['id'] ? 'Editar turno' : 'Nuevo turno';
        $volver = '/agenda.php?fecha=' . urlencode((string) $row['Fecha']) . ((int) $row['Doctor'] > 0 ? '&doctor=' . (int) $row['Doctor'] : '');
        $doctorDisp = (int) ($row['Doctor'] ?? 0);
        $fechaDisp = (string) ($row['Fecha'] ?? '');
        $horaSel = trim((string) ($row['hora'] ?? ''));
        $disp = $repo->disponibilidadVisual($fechaDisp, $doctorDisp, (int) ($row['id'] ?? 0));

        $body = $this->renderView('agenda/turno_form', [
            'ext' => $ext,
            'row' => $row,
            'error' => $error,
            'pacienteActual' => $pacienteActual,
            'titulo' => $titulo,
            'volver' => $volver,
            'doctores' => $doctores,
            'primeraVezOpts' => $primeraVezOpts,
            'dispOcupadas' => $disp['occupied'],
            'dispSlots' => $disp['slots'],
            'dispSource' => $disp['source'],
            'dispSinFranjaDia' => $disp['sin_franja_dia'],
            'dispStep' => $disp['step'],
            'horaSel' => $horaSel,
            'estados' => $estados,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /agenda.php');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /agenda.php');
            exit;
        }
        $repo = new TurnosRepository($this->pdo);
        $repo->deleteById($id);
        flash_set('Turno eliminado.');
        header('Location: /agenda.php');
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
