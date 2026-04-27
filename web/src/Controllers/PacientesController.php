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
        $repo = new PacientesRepository($this->pdo, user_clinica_id($this->user));
        $hasExt = $repo->hasExtendedColumns();
        $hasListaCob = $repo->hasListaCoberturas();
        $f = self::collectFiltrosPacientes();
        $rows = $repo->listForIndex($hasExt, $hasListaCob, $f);

        $viewData = [
            'rows' => $rows,
            'hasExt' => $hasExt,
            'user' => $this->user,
            'f' => $f,
            'pacientesQueryString' => self::buildPacientesQueryString($f),
            'pacientesFiltrosActivos' => self::pacientesHayFiltrosActivos($f),
        ];

        $body = $this->renderView('pacientes/index', $viewData);
        layout_render('Pacientes', $body, $this->user);
    }

    public function historiaClinica(): void
    {
        $repo = new PacientesRepository($this->pdo, user_clinica_id($this->user));
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
        $hasNotasHc = $repo->historiaNotasTableExists();
        $hasAdjuntosHc = $repo->historiaAdjuntosTableExists();

        $hcBase = '';
        if ($hasHcTexto) {
            $hcBase = (string) ($p['hc_texto'] ?? '');
        } elseif ($hasHcLegacy) {
            $hcBase = (string) ($p['HC'] ?? '');
        }
        $antecedentes = $hasAnteced ? (string) ($p['antecedentes_hc'] ?? '') : '';
        $notasHc = $hasNotasHc ? $repo->listHistoriaClinicaNotas($id) : [];
        $idsNota = [];
        foreach ($notasHc as $n) {
            $nid = (int) ($n['id'] ?? 0);
            if ($nid > 0) {
                $idsNota[] = $nid;
            }
        }
        $adjuntosPorNota = ($hasNotasHc && $hasAdjuntosHc) ? $repo->listHistoriaClinicaAdjuntosPorNotas($idsNota) : [];
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $nuevaNota = trim((string) ($_POST['hc_nueva_nota'] ?? ''));
            $antecedIn = trim((string) ($_POST['antecedentes_hc'] ?? ''));
            $linkUrl = trim((string) ($_POST['hc_link_url'] ?? ''));
            $linkTitulo = trim((string) ($_POST['hc_link_titulo'] ?? ''));
            $idUsuario = isset($this->user['id']) ? (int) $this->user['id'] : null;

            if (!$hasNotasHc) {
                $error = 'Falta la tabla pacientes_hc_notas. Ejecutá sql/migration_026_hc_notas_inmutables.sql.';
            } elseif (!$hasAdjuntosHc) {
                $error = 'Falta la tabla pacientes_hc_adjuntos. Ejecutá sql/migration_027_hc_adjuntos.sql.';
            } elseif ($nuevaNota === '') {
                $error = 'Escribí una anotación para agregar a la historia clínica.';
            } elseif (!$hasHcTexto && !$hasHcLegacy) {
                $error = 'Tu tabla pacientes no tiene campo base de historia clínica (hc_texto/HC).';
            } else {
                $error = $this->validateHistoriaAdjuntosInput();
            }
            if ($error === '') {
                $idNota = $repo->addHistoriaClinicaNota($id, $nuevaNota, $idUsuario);
                if ($idNota < 1) {
                    $error = 'No se pudo registrar la anotación.';
                }
            }
            if ($error === '') {
                $error = $this->persistHistoriaAdjuntos($repo, $idNota, $idUsuario, $linkUrl, $linkTitulo);
            }
            if ($error === '') {
                // El texto histórico base se conserva; solo se permite actualizar antecedentes.
                $repo->updateHistoriaClinica($id, $hcBase, $antecedIn, $hasHcTexto, $hasAnteced);
                flash_set('Anotación agregada en la historia clínica.');
                header('Location: /historia_clinica.php?id=' . $id);
                exit;
            } else {
                $antecedentes = $antecedIn;
            }
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
            'hcBaseDisplay' => self::legacyHcToDisplayText($hcBase),
            'antecedentes' => $antecedentes,
            'hasNotasHc' => $hasNotasHc,
            'hasAdjuntosHc' => $hasAdjuntosHc,
            'notasHc' => $notasHc,
            'adjuntosPorNota' => $adjuntosPorNota,
            'error' => $error,
        ]);
        layout_render('Historia clínica', $body, $this->user);
    }

    public function form(): void
    {
        require_once dirname(__DIR__, 2) . '/includes/catalogos.php';

        $repo = new PacientesRepository($this->pdo, user_clinica_id($this->user));
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
            $row = array_merge($row, PacientesRepository::blankExtendedPatientRow());
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
                'rel' => catalogo_lista($this->pdo, 'lista_relacion_paciente'),
                'estatus' => catalogo_lista($this->pdo, 'lista_estatus_pais'),
                'sexo' => catalogo_lista($this->pdo, 'lista_sexo', 'prioridad_id'),
                'idgen' => catalogo_lista($this->pdo, 'lista_identidad_genero', 'prioridad_id'),
                'orient' => catalogo_lista($this->pdo, 'lista_orientacion_sex', 'prioridad_id'),
                'gsang' => catalogo_lista($this->pdo, 'lista_grupo_sanguineo', 'prioridad_id'),
                'fsang' => catalogo_lista($this->pdo, 'lista_factor_sanguineo', 'prioridad_id'),
            ];
        }
        $fotoDisponible = $ext && db_table_has_column($this->pdo, 'pacientes', 'ruta_foto');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
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

            if ($error === '') {
                $fotoMsg = '';
                if ($ext) {
                    $payload = $this->collectExtendedPacientePayloadFromPost();
                    $savedId = $id;
                    if ($id > 0) {
                        $repo->updatePacienteFull($id, $payload);
                    } else {
                        $savedId = $repo->insertPacienteFull($payload);
                    }
                    $fotoMsg = $this->syncPacienteFoto($repo, $savedId, $NroHC);
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
                $msg = $id > 0 ? 'Paciente actualizado.' : 'Paciente creado.';
                if ($fotoMsg !== '') {
                    $msg .= $fotoMsg;
                }
                flash_set($msg);
                header('Location: /pacientes.php');
                exit;
            }

            if ($ext) {
                $row = array_merge(['id' => $id], $this->collectExtendedPacientePayloadFromPost());
                if ($row['fecha_nacimiento'] !== null && $row['fecha_nacimiento'] !== '') {
                    $row['fecha_nacimiento'] = substr((string) $row['fecha_nacimiento'], 0, 10);
                } else {
                    $row['fecha_nacimiento'] = '';
                }
            } else {
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
            'fotoDisponible' => $fotoDisponible,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    /**
     * Sube o borra foto si existe columna `ruta_foto`. Mensaje vacío = OK; si no, texto para concatenar al flash.
     */
    private function syncPacienteFoto(PacientesRepository $repo, int $patientId, int $nroHC): string
    {
        if (!db_table_has_column($this->pdo, 'pacientes', 'ruta_foto')) {
            return '';
        }
        $row = $repo->findById($patientId);
        if (!$row) {
            return '';
        }
        $publicDir = dirname(__DIR__, 2) . '/public';
        $upDir = $publicDir . '/uploads/pacientes';

        if (!empty($_POST['borrar_foto'])) {
            self::unlinkPacienteFotoPublic($publicDir, (string) ($row['ruta_foto'] ?? ''));
            $repo->updatePacienteFull($patientId, ['ruta_foto' => null]);

            return '';
        }

        if (!isset($_FILES['foto_paciente']) || !is_array($_FILES['foto_paciente'])) {
            return '';
        }
        $fi = $_FILES['foto_paciente'];
        if (($fi['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        if (($fi['error'] ?? 0) !== UPLOAD_ERR_OK) {
            return ' No se pudo subir la foto (error de carga).';
        }
        if (($fi['size'] ?? 0) > 3_500_000) {
            return ' La imagen es demasiado grande (máx. ~3,5 MB).';
        }

        $tmp = (string) ($fi['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ' Archivo de foto inválido.';
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($map[$mime])) {
            return ' Formato no permitido (JPG, PNG o WebP).';
        }
        $ext = $map[$mime];

        if (!is_dir($upDir) && !mkdir($upDir, 0755, true) && !is_dir($upDir)) {
            return ' No se pudo crear la carpeta de fotos.';
        }

        $baseName = 'p' . $patientId . '_hc' . $nroHC;
        foreach (['jpg', 'png', 'webp'] as $e) {
            $oldF = $upDir . '/' . $baseName . '.' . $e;
            if (is_file($oldF)) {
                @unlink($oldF);
            }
        }

        $dest = $upDir . '/' . $baseName . '.' . $ext;
        if (!move_uploaded_file($tmp, $dest)) {
            return ' No se pudo guardar la imagen.';
        }

        $rel = '/uploads/pacientes/' . $baseName . '.' . $ext;
        $prev = trim((string) ($row['ruta_foto'] ?? ''));
        if ($prev !== '' && $prev !== $rel) {
            self::unlinkPacienteFotoPublic($publicDir, $prev);
        }
        $repo->updatePacienteFull($patientId, ['ruta_foto' => $rel]);

        return '';
    }

    private static function unlinkPacienteFotoPublic(string $publicDir, string $relUrl): void
    {
        $relUrl = trim($relUrl);
        if ($relUrl === '' || strpos($relUrl, '..') !== false) {
            return;
        }
        $rel = ltrim(str_replace('\\', '/', $relUrl), '/');
        $path = $publicDir . '/' . $rel;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Todos los campos persistibles del backup (migration_002 / 005) leídos del POST.
     *
     * @return array<string, mixed>
     */
    private function collectExtendedPacientePayloadFromPost(): array
    {
        $fn = trim((string) ($_POST['fecha_nacimiento'] ?? ''));
        $fecha_nacimiento = $fn === '' ? null : $fn;
        $activo = isset($_POST['activo']) ? 1 : 0;

        return [
            'NroHC' => (int) ($_POST['NroHC'] ?? 0),
            'Nombres' => trim((string) ($_POST['Nombres'] ?? '')),
            'DNI' => trim((string) ($_POST['DNI'] ?? '')),
            'convenio' => isset($_POST['convenio']) ? 1 : 0,
            'fecha_nacimiento' => $fecha_nacimiento,
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'direccion' => trim((string) ($_POST['direccion'] ?? '')),
            'activo' => $activo,
            'notas' => trim((string) ($_POST['notas'] ?? '')),
            'paciente_inactivo' => $activo ? 0 : 1,
            'fe_nac' => $fecha_nacimiento !== null ? $fecha_nacimiento . ' 00:00:00' : null,
            'numehistoria' => trim((string) ($_POST['numehistoria'] ?? '')),
            'embarazo' => isset($_POST['embarazo']) ? 1 : 0,
            'ulti_emba' => post_int_null('ulti_emba'),
            'ultima_cons' => post_datetime_local_mysql_null('ultima_cons'),
            'motivo_inactividad' => trim((string) ($_POST['motivo_inactividad'] ?? '')),
            'cobertura' => post_int_null('cobertura'),
            'id_cobertura' => post_int_null('id_cobertura'),
            'nro_os' => trim((string) ($_POST['nro_os'] ?? '')),
            'apellido' => trim((string) ($_POST['apellido'] ?? '')),
            'apellido2' => trim((string) ($_POST['apellido2'] ?? '')),
            'sexo' => post_int_null('sexo'),
            'dni_sin_uso' => trim((string) ($_POST['dni_sin_uso'] ?? '')),
            'id_tipo_doc' => post_int_null('id_tipo_doc'),
            'id_ocupacion' => post_int_null('id_ocupacion'),
            'detalle_ocupacion' => trim((string) ($_POST['detalle_ocupacion'] ?? '')),
            'tel_celular' => trim((string) ($_POST['tel_celular'] ?? '')),
            'tel_laboral' => trim((string) ($_POST['tel_laboral'] ?? '')),
            'nombre_padre' => trim((string) ($_POST['nombre_padre'] ?? '')),
            'naci_padre' => post_date_mysql_null('naci_padre'),
            'id_ocupacion_padre' => post_int_null('id_ocupacion_padre'),
            'horas_hogar_padre' => trim((string) ($_POST['horas_hogar_padre'] ?? '')),
            'nombre_madre' => trim((string) ($_POST['nombre_madre'] ?? '')),
            'naci_madre' => post_date_mysql_null('naci_madre'),
            'id_ocupacion_madre' => post_int_null('id_ocupacion_madre'),
            'horas_hogar_madre' => trim((string) ($_POST['horas_hogar_madre'] ?? '')),
            'nro_hermanos' => trim((string) ($_POST['nro_hermanos'] ?? '')),
            'edad_hermanos' => trim((string) ($_POST['edad_hermanos'] ?? '')),
            'nro_hermanas' => trim((string) ($_POST['nro_hermanas'] ?? '')),
            'edad_hermanas' => trim((string) ($_POST['edad_hermanas'] ?? '')),
            'detalles_familia' => trim((string) ($_POST['detalles_familia'] ?? '')),
            'ape1_contacto' => trim((string) ($_POST['ape1_contacto'] ?? '')),
            'ape2_contacto' => trim((string) ($_POST['ape2_contacto'] ?? '')),
            'nombre_contacto' => trim((string) ($_POST['nombre_contacto'] ?? '')),
            'id_relacion' => post_int_null('id_relacion'),
            'tel_par_contacto' => trim((string) ($_POST['tel_par_contacto'] ?? '')),
            'tel_cel_contacto' => trim((string) ($_POST['tel_cel_contacto'] ?? '')),
            'tel_lab_contacto' => trim((string) ($_POST['tel_lab_contacto'] ?? '')),
            'id_estado_civil' => post_int_null('id_estado_civil'),
            'id_etnia' => post_int_null('id_etnia'),
            'id_ciudad' => post_int_null('id_ciudad'),
            'cp' => trim((string) ($_POST['cp'] ?? '')),
            'id_provincia' => post_int_null('id_provincia'),
            'id_pais' => post_int_null('id_pais'),
            'id_estatus' => post_int_null('id_estatus'),
            'alergias' => trim((string) ($_POST['alergias'] ?? '')),
            'grupo_sanguineo' => post_int_null('grupo_sanguineo'),
            'factor_sanguineo' => post_int_null('factor_sanguineo'),
            'hc_texto' => trim((string) ($_POST['hc_texto'] ?? '')),
            'referente' => trim((string) ($_POST['referente'] ?? '')),
            'id_cobertura2' => post_int_null('id_cobertura2'),
            'nu_afiliado2' => trim((string) ($_POST['nu_afiliado2'] ?? '')),
            'antecedentes_hc' => trim((string) ($_POST['antecedentes_hc'] ?? '')),
            'id_plan' => post_int_null('id_plan'),
            'paga_iva' => isset($_POST['paga_iva']) ? 1 : 0,
            'alta_paci_web' => post_int_null('alta_paci_web'),
            'identidad_gen' => post_int_null('identidad_gen'),
            'orientacion_sex' => post_int_null('orientacion_sex'),
        ];
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pacientes.php');
            exit;
        }
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /pacientes.php');
            exit;
        }
        $repo = new PacientesRepository($this->pdo, user_clinica_id($this->user));
        $p = $repo->findById($id);
        if ($p !== null && !empty($p['ruta_foto']) && db_table_has_column($this->pdo, 'pacientes', 'ruta_foto')) {
            self::unlinkPacienteFotoPublic(dirname(__DIR__, 2) . '/public', (string) $p['ruta_foto']);
        }
        $repo->deleteById($id);
        flash_set('Paciente eliminado.');
        header('Location: /pacientes.php');
        exit;
    }

    /**
     * @return array<string, string|int>
     */
    private static function collectFiltrosPacientes(): array
    {
        $a = isset($_GET['activo']) ? trim((string) $_GET['activo']) : '1';
        if ($a !== '' && $a !== '0' && $a !== '1') {
            $a = '1';
        }

        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'nrohc' => (int) ($_GET['nrohc'] ?? 0),
            'id' => (int) ($_GET['id'] ?? 0),
            'activo' => $a,
        ];
    }

    /**
     * @param array<string, string|int> $f
     */
    private static function buildPacientesQueryString(array $f): string
    {
        $q = [];
        if (($f['q'] ?? '') !== '') {
            $q['q'] = (string) $f['q'];
        }
        if ((int) ($f['nrohc'] ?? 0) > 0) {
            $q['nrohc'] = (int) $f['nrohc'];
        }
        if ((int) ($f['id'] ?? 0) > 0) {
            $q['id'] = (int) $f['id'];
        }
        $act = (string) ($f['activo'] ?? '1');
        if ($act !== '1') {
            $q['activo'] = $act;
        }

        return http_build_query($q);
    }

    /**
     * @param array<string, string|int> $f
     */
    private static function pacientesHayFiltrosActivos(array $f): bool
    {
        if (trim((string) ($f['q'] ?? '')) !== '') {
            return true;
        }
        if ((int) ($f['nrohc'] ?? 0) > 0) {
            return true;
        }
        if ((int) ($f['id'] ?? 0) > 0) {
            return true;
        }

        return (string) ($f['activo'] ?? '1') !== '1';
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        return (string) ob_get_clean();
    }

    private function validateHistoriaAdjuntosInput(): string
    {
        $linkUrl = trim((string) ($_POST['hc_link_url'] ?? ''));
        if ($linkUrl !== '') {
            $url = $this->normalizeHistoriaLinkUrl($linkUrl);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return 'El link de estudio no tiene un formato válido.';
            }
        }
        if (!isset($_FILES['hc_adjunto_archivo']) || !is_array($_FILES['hc_adjunto_archivo'])) {
            return '';
        }
        $fi = $_FILES['hc_adjunto_archivo'];
        $err = (int) ($fi['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        if ($err !== UPLOAD_ERR_OK) {
            return 'No se pudo subir el adjunto (error de carga).';
        }
        $tmp = (string) ($fi['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return 'Archivo adjunto inválido.';
        }
        $max = 8 * 1024 * 1024;
        if ((int) ($fi['size'] ?? 0) > $max) {
            return 'El adjunto supera el tamaño máximo (8 MB).';
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        $permitidos = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $permitidos, true)) {
            return 'Formato de adjunto no permitido (PDF, JPG, PNG, WebP).';
        }

        return '';
    }

    private function persistHistoriaAdjuntos(
        PacientesRepository $repo,
        int $idNota,
        ?int $idUsuario,
        string $linkUrlRaw,
        string $linkTituloRaw
    ): string {
        if ($idNota < 1) {
            return 'No se pudo asociar adjuntos a la anotación.';
        }

        $linkUrlRaw = trim($linkUrlRaw);
        if ($linkUrlRaw !== '') {
            $url = $this->normalizeHistoriaLinkUrl($linkUrlRaw);
            $titulo = trim($linkTituloRaw) !== '' ? trim($linkTituloRaw) : $url;
            $repo->addHistoriaClinicaAdjuntoLink($idNota, $titulo, $url, $idUsuario);
        }

        if (!isset($_FILES['hc_adjunto_archivo']) || !is_array($_FILES['hc_adjunto_archivo'])) {
            return '';
        }
        $fi = $_FILES['hc_adjunto_archivo'];
        if ((int) ($fi['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        $tmp = (string) ($fi['tmp_name'] ?? '');
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        $map = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($map[$mime])) {
            return 'Formato de adjunto no permitido.';
        }
        $ext = $map[$mime];
        $publicDir = dirname(__DIR__, 2) . '/public';
        $upDir = $publicDir . '/uploads/hc';
        if (!is_dir($upDir) && !mkdir($upDir, 0755, true) && !is_dir($upDir)) {
            return 'No se pudo crear la carpeta de adjuntos.';
        }

        $baseName = 'hc_nota_' . $idNota . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $dest = $upDir . '/' . $baseName . '.' . $ext;
        if (!move_uploaded_file($tmp, $dest)) {
            return 'No se pudo guardar el archivo adjunto.';
        }
        $rel = '/uploads/hc/' . $baseName . '.' . $ext;
        $nombre = trim((string) ($fi['name'] ?? 'Adjunto'));
        if ($nombre === '') {
            $nombre = 'Adjunto';
        }
        $repo->addHistoriaClinicaAdjuntoArchivo($idNota, $nombre, $rel, $mime, (int) ($fi['size'] ?? 0), $idUsuario);

        return '';
    }

    private function normalizeHistoriaLinkUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    private static function legacyHcToDisplayText(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        // Si no parece RTF, se muestra tal cual.
        if (stripos($raw, '{\\rtf') !== 0) {
            return $raw;
        }

        $txt = str_replace(["\r\n", "\r"], "\n", $raw);
        $txt = preg_replace('/\\\\par[d]?\\b ?/', "\n", $txt) ?? $txt;
        $txt = str_replace(['\\{', '\\}'], ['{', '}'], $txt);

        // Decodifica secuencias RTF hex: \'f3, \'e1, etc. (cp1252 -> utf8).
        $txt = preg_replace_callback(
            "/\\\\'([0-9a-fA-F]{2})/",
            static function (array $m): string {
                $ch = chr((int) hexdec($m[1]));
                $u = @mb_convert_encoding($ch, 'UTF-8', 'Windows-1252');
                return is_string($u) ? $u : $ch;
            },
            $txt
        ) ?? $txt;

        // Elimina grupos/códigos RTF restantes.
        $txt = preg_replace('/\\{\\\\[^{}]*\\}/', ' ', $txt) ?? $txt;
        $txt = preg_replace('/\\\\[a-zA-Z]+-?\\d* ?/', ' ', $txt) ?? $txt;
        $txt = str_replace(['{', '}'], ' ', $txt);
        $txt = preg_replace("/\n{3,}/", "\n\n", $txt) ?? $txt;
        $txt = preg_replace('/[ \t]{2,}/', ' ', $txt) ?? $txt;

        return trim($txt);
    }
}

