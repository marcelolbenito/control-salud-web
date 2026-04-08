<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/PacientesRepository.php';

final class PacientesController
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
        $repo = new PacientesRepository($this->pdo);
        $hasExt = $repo->hasExtendedColumns();
        $hasListaCob = $repo->hasListaCoberturas();
        $rows = $repo->listForIndex($hasExt, $hasListaCob);

        $viewData = [
            'rows' => $rows,
            'hasExt' => $hasExt,
            'user' => $this->user,
        ];

        $body = $this->renderView('pacientes/index', $viewData);
        layout_render('Pacientes', $body, $this->user);
    }

    public function historiaClinica(): void
    {
        $repo = new PacientesRepository($this->pdo);
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id < 1) {
            flash_set('Paciente no válido.');
            header('Location: /pacientes.php');
            exit;
        }

        $p = $repo->findById($id);
        if (!$p) {
            flash_set('Paciente no encontrado.');
            header('Location: /pacientes.php');
            exit;
        }

        $hasHcTexto = db_table_has_column($this->pdo, 'pacientes', 'hc_texto');
        $hasAnteced = db_table_has_column($this->pdo, 'pacientes', 'antecedentes_hc');
        $hasHcLegacy = db_table_has_column($this->pdo, 'pacientes', 'HC');

        $hcBase = '';
        if ($hasHcTexto) {
            $hcBase = (string) ($p['hc_texto'] ?? '');
        } elseif ($hasHcLegacy) {
            $hcBase = (string) ($p['HC'] ?? '');
        }
        $antecedentes = $hasAnteced ? (string) ($p['antecedentes_hc'] ?? '') : '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hcTextoIn = trim((string) ($_POST['hc_texto'] ?? ''));
            $antecedIn = trim((string) ($_POST['antecedentes_hc'] ?? ''));

            if (!$hasHcTexto && !$hasHcLegacy) {
                $error = 'Tu tabla pacientes no tiene campo de historia clínica (hc_texto/HC).';
            } else {
                $repo->updateHistoriaClinica($id, $hcTextoIn, $antecedIn, $hasHcTexto, $hasAnteced);
                flash_set('Historia clínica actualizada.');
                header('Location: /historia_clinica.php?id=' . $id);
                exit;
            }
            $hcBase = $hcTextoIn;
            $antecedentes = $antecedIn;
        }

        $nombre = trim((string) (($p['apellido'] ?? '') . ', ' . ($p['Nombres'] ?? '')));
        if ($nombre === ',' || $nombre === '') {
            $nombre = trim((string) ($p['Nombres'] ?? 'Sin nombre'));
        }

        $body = $this->renderView('pacientes/historia_clinica', [
            'id' => $id,
            'p' => $p,
            'nombre' => $nombre,
            'hcBase' => $hcBase,
            'antecedentes' => $antecedentes,
            'error' => $error,
        ]);
        layout_render('Historia clínica', $body, $this->user);
    }

    public function form(): void
    {
        require_once dirname(__DIR__, 2) . '/includes/catalogos.php';

        $repo = new PacientesRepository($this->pdo);
        $ext = $repo->hasExtendedColumns();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $row = [
            'id' => 0,
            'NroHC' => '',
            'Nombres' => '',
            'DNI' => '',
            'convenio' => 0,
            'fecha_nacimiento' => '',
            'telefono' => '',
            'email' => '',
            'direccion' => '',
            'activo' => 1,
            'notas' => '',
        ];

        if ($ext) {
            $row = array_merge($row, [
                'apellido' => '', 'apellido2' => '', 'numehistoria' => '',
                'id_cobertura' => null, 'id_plan' => null, 'nro_os' => '',
                'id_cobertura2' => null, 'nu_afiliado2' => '',
                'tel_celular' => '', 'tel_laboral' => '',
                'id_tipo_doc' => null, 'id_ocupacion' => null, 'detalle_ocupacion' => '',
                'sexo' => null, 'cp' => '',
                'id_pais' => null, 'id_provincia' => null, 'id_ciudad' => null,
                'id_estado_civil' => null, 'id_etnia' => null,
                'alergias' => '',
            ]);
        }

        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Paciente no encontrado.');
                header('Location: /pacientes.php');
                exit;
            }
            $row = array_merge($row, $loaded);
            $row['fecha_nacimiento'] = $row['fecha_nacimiento'] ? (string) $row['fecha_nacimiento'] : '';
        }

        $sugeridoNro = $repo->suggestedNextNroHC();
        $error = '';

        $listas = [];
        if ($ext) {
            $listas = [
                'cob' => catalogo_lista($this->pdo, 'lista_coberturas'),
                'planes' => catalogo_lista($this->pdo, 'lista_planes'),
                'pais' => catalogo_lista($this->pdo, 'lista_pais'),
                'prov' => catalogo_lista($this->pdo, 'lista_provincia'),
                'ciu' => catalogo_lista($this->pdo, 'lista_ciudad'),
                'tdoc' => catalogo_lista($this->pdo, 'lista_tipo_documento'),
                'ocup' => catalogo_lista($this->pdo, 'lista_ocupacion'),
                'eciv' => catalogo_lista($this->pdo, 'lista_estado_civil'),
                'etn' => catalogo_lista($this->pdo, 'lista_etnia'),
            ];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $NroHC = (int) ($_POST['NroHC'] ?? 0);
            $Nombres = trim((string) ($_POST['Nombres'] ?? ''));
            $DNI = trim((string) ($_POST['DNI'] ?? ''));
            $convenio = isset($_POST['convenio']) ? 1 : 0;
            $fn = trim((string) ($_POST['fecha_nacimiento'] ?? ''));
            $fecha_nacimiento = $fn === '' ? null : $fn;
            $telefono = trim((string) ($_POST['telefono'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $direccion = trim((string) ($_POST['direccion'] ?? ''));
            $activo = isset($_POST['activo']) ? 1 : 0;
            $notas = trim((string) ($_POST['notas'] ?? ''));

            if ($NroHC < 1) {
                $error = 'El número de historia clínica (Nro HC) debe ser mayor a cero.';
            } elseif ($repo->existsOtherWithNroHC($NroHC, $id)) {
                $error = 'Ya existe otro paciente con ese Nro HC.';
            }

            $ex = [];
            if ($error === '' && $ext) {
                $ex = [
                    'apellido' => trim((string) ($_POST['apellido'] ?? '')),
                    'apellido2' => trim((string) ($_POST['apellido2'] ?? '')),
                    'numehistoria' => trim((string) ($_POST['numehistoria'] ?? '')),
                    'id_cobertura' => post_int_null('id_cobertura'),
                    'id_plan' => post_int_null('id_plan'),
                    'nro_os' => trim((string) ($_POST['nro_os'] ?? '')),
                    'id_cobertura2' => post_int_null('id_cobertura2'),
                    'nu_afiliado2' => trim((string) ($_POST['nu_afiliado2'] ?? '')),
                    'tel_celular' => trim((string) ($_POST['tel_celular'] ?? '')),
                    'tel_laboral' => trim((string) ($_POST['tel_laboral'] ?? '')),
                    'id_tipo_doc' => post_int_null('id_tipo_doc'),
                    'id_ocupacion' => post_int_null('id_ocupacion'),
                    'detalle_ocupacion' => trim((string) ($_POST['detalle_ocupacion'] ?? '')),
                    'sexo' => post_int_null('sexo'),
                    'cp' => trim((string) ($_POST['cp'] ?? '')),
                    'id_pais' => post_int_null('id_pais'),
                    'id_provincia' => post_int_null('id_provincia'),
                    'id_ciudad' => post_int_null('id_ciudad'),
                    'id_estado_civil' => post_int_null('id_estado_civil'),
                    'id_etnia' => post_int_null('id_etnia'),
                    'alergias' => trim((string) ($_POST['alergias'] ?? '')),
                ];
            }

            if ($error === '') {
                if ($ext) {
                    if ($id > 0) {
                        $repo->updatePacienteExtended(
                            $id,
                            $NroHC,
                            $Nombres,
                            $DNI,
                            $convenio,
                            $fecha_nacimiento,
                            $telefono,
                            $email,
                            $direccion,
                            $activo,
                            $notas,
                            $ex
                        );
                    } else {
                        $repo->insertPacienteExtended(
                            $NroHC,
                            $Nombres,
                            $DNI,
                            $convenio,
                            $fecha_nacimiento,
                            $telefono,
                            $email,
                            $direccion,
                            $activo,
                            $notas,
                            $ex
                        );
                    }
                } else {
                    if ($id > 0) {
                        $repo->updatePacienteBase(
                            $id,
                            $NroHC,
                            $Nombres,
                            $DNI,
                            $convenio,
                            $fecha_nacimiento,
                            $telefono,
                            $email,
                            $direccion,
                            $activo,
                            $notas
                        );
                    } else {
                        $repo->insertPacienteBase(
                            $NroHC,
                            $Nombres,
                            $DNI,
                            $convenio,
                            $fecha_nacimiento,
                            $telefono,
                            $email,
                            $direccion,
                            $activo,
                            $notas
                        );
                    }
                }
                flash_set($id > 0 ? 'Paciente actualizado.' : 'Paciente creado.');
                header('Location: /pacientes.php');
                exit;
            }

            $row = array_merge($row, [
                'id' => $id,
                'NroHC' => $NroHC,
                'Nombres' => $Nombres,
                'DNI' => $DNI,
                'convenio' => $convenio,
                'fecha_nacimiento' => $fn,
                'telefono' => $telefono,
                'email' => $email,
                'direccion' => $direccion,
                'activo' => $activo,
                'notas' => $notas,
            ]);
            if ($ext && isset($ex)) {
                $row = array_merge($row, $ex);
            }
        }

        $titulo = $row['id'] ? 'Editar paciente' : 'Nuevo paciente';
        $defaultNro = $row['id'] ? (int) $row['NroHC'] : $sugeridoNro;

        $body = $this->renderView('pacientes/form', [
            'ext' => $ext,
            'row' => $row,
            'error' => $error,
            'titulo' => $titulo,
            'defaultNro' => $defaultNro,
            'sugeridoNro' => $sugeridoNro,
            'listas' => $listas,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pacientes.php');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /pacientes.php');
            exit;
        }
        $repo = new PacientesRepository($this->pdo);
        $repo->deleteById($id);
        flash_set('Paciente eliminado.');
        header('Location: /pacientes.php');
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

