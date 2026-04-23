<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/catalogos.php';
require_once dirname(__DIR__) . '/Repositories/OrdenesRepository.php';
require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';
require_once dirname(__DIR__) . '/Repositories/SesionesRepository.php';

final class OrdenesController
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
        if (!db_table_exists($this->pdo, OrdenesRepository::tableSqlName())) {
            $tn = h(OrdenesRepository::tableSqlName());
            $body = '<div class="container"><p class="alert alert-error">Falta la tabla <code>' . $tn . '</code> (datos del backup). Importá el backup o el esquema en <code>sql/schema_mysql.sql</code>.</p></div>';
            layout_render('Órdenes', $body, $this->user);
            return;
        }

        $repo = new OrdenesRepository($this->pdo, user_clinica_id($this->user));
        $docRepo = new DoctoresRepository($this->pdo, user_clinica_id($this->user));

        $f = self::collectFiltrosOrdenes();
        $rows = $repo->listForIndex($f);
        $doctores = $docRepo->listAllOrdered();
        $cobOpts = catalogo_lista($this->pdo, 'lista_coberturas', 'prioridad_id');
        $ordenesQueryString = self::buildOrdenesQueryString($f);

        $body = $this->renderView('ordenes/index', [
            'rows' => $rows,
            'doctores' => $doctores,
            'cobOpts' => $cobOpts,
            'f' => $f,
            'ordenesQueryString' => $ordenesQueryString,
            'ordenesFiltrosActivos' => self::ordenesHayFiltrosActivos($f),
        ]);
        layout_render('Órdenes', $body, $this->user);
    }

    public function form(): void
    {
        if (!db_table_exists($this->pdo, OrdenesRepository::tableSqlName())) {
            flash_set('Falta la tabla ' . OrdenesRepository::tableSqlName() . ' (importar backup / esquema).');
            header('Location: /index.php');
            exit;
        }

        $repo = new OrdenesRepository($this->pdo, user_clinica_id($this->user));
        $docRepo = new DoctoresRepository($this->pdo, user_clinica_id($this->user));

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $prefillNro = isset($_GET['nropaci']) ? (int) $_GET['nropaci'] : 0;
        if ($prefillNro < 1 && $id < 1) {
            $prefillNro = isset($_GET['nrohc']) ? (int) $_GET['nrohc'] : 0;
        }

        $row = self::ordenRowDefaults($prefillNro);

        if ($id < 1) {
            $gf = trim((string) ($_GET['fecha'] ?? ''));
            if ($gf !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $gf)) {
                $row['fecha_orden'] = $gf;
            }
            $gd = (int) ($_GET['doctor'] ?? 0);
            if ($gd < 1) {
                $gd = (int) ($_GET['iddoctor'] ?? 0);
            }
            if ($gd > 0) {
                $row['iddoctor'] = $gd;
            }
        }

        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Orden no encontrada.');
                header('Location: /ordenes.php');
                exit;
            }
            $row = self::ordenRowFromDb($loaded, $row);
        }

        $doctores = $docRepo->listActivos();
        if ($doctores === []) {
            $doctores = $docRepo->listAllOrdered();
        }

        $ordenesReturnQs = self::buildOrdenesQueryString(self::collectFiltrosOrdenes());
        if ($prefillNro > 0 && !isset($_GET['nrohc'])) {
            $ordenesReturnQs = self::buildOrdenesQueryString(array_merge(
                self::collectFiltrosOrdenes(),
                ['nrohc' => $prefillNro]
            ));
        }

        $cobOpts = catalogo_lista($this->pdo, 'lista_coberturas', 'prioridad_id');
        $planesOpts = $repo->listPlanesConCobertura();
        $practicaOpts = $repo->listCatalogIfExists('lista_practicas');
        $derivacionOpts = $repo->listCatalogIfExists('lista_derivaciones');
        $sucursalOpts = $repo->listCatalogIfExists('lista_sucursales');

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $id = (int) ($_POST['id'] ?? 0);
            $parsed = self::ordenParsePost($this->pdo, $repo, $this->user);
            $error = $parsed['error'];
            if ($error === '') {
                $vals = $parsed['values'];
                if ($id > 0) {
                    unset($vals['idusuariocarga']);
                    $repo->updateRow($id, $vals);
                    flash_set('Orden actualizada.');
                } else {
                    $repo->insertRow($vals);
                    flash_set('Orden registrada.');
                }
                $retQs = trim((string) ($_POST['ordenes_return_qs'] ?? ''));
                header('Location: /ordenes.php' . ($retQs !== '' ? '?' . $retQs : ''));
                exit;
            }

            $ordenesReturnQs = trim((string) ($_POST['ordenes_return_qs'] ?? $ordenesReturnQs));
            $row = self::ordenRowFromPost($_POST, $id);
        }

        $volver = '/ordenes.php' . ($ordenesReturnQs !== '' ? '?' . $ordenesReturnQs : '');
        $titulo = $row['id'] ? 'Editar orden' : 'Nueva orden';

        $sesionesResumen = '';
        $idOrdenRow = (int) ($row['id'] ?? 0);
        if ($idOrdenRow > 0 && db_table_exists($this->pdo, SesionesRepository::tableName())) {
            $sRepo = new SesionesRepository($this->pdo, user_clinica_id($this->user));
            $nSes = $sRepo->countByOrden($idOrdenRow);
            $sumSes = $sRepo->sumCantidadByOrden($idOrdenRow);
            if ($nSes > 0 || $sumSes > 0) {
                $sesionesResumen = $nSes . ' registro(s), ' . $sumSes . ' sesión(es) contabilizadas.';
            }
        }

        $body = $this->renderView('ordenes/form', [
            'row' => $row,
            'doctores' => $doctores,
            'cobOpts' => $cobOpts,
            'planesOpts' => $planesOpts,
            'practicaOpts' => $practicaOpts,
            'derivacionOpts' => $derivacionOpts,
            'sucursalOpts' => $sucursalOpts,
            'error' => $error,
            'titulo' => $titulo,
            'volver' => $volver,
            'ordenesReturnQs' => $ordenesReturnQs,
            'sesionesResumen' => $sesionesResumen,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ordenes.php');
            exit;
        }
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /ordenes.php');
            exit;
        }
        $repo = new OrdenesRepository($this->pdo, user_clinica_id($this->user));
        if ($repo->countSesionesByOrden($id) > 0) {
            flash_set('No se puede eliminar: esta orden tiene sesiones registradas (módulo sesiones pendiente).');
            header('Location: /ordenes.php');
            exit;
        }
        $repo->deleteById($id);
        flash_set('Orden eliminada.');
        header('Location: /ordenes.php');
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private static function ordenRowDefaults(int $prefillNro): array
    {
        return [
            'id' => 0,
            'NroPaci' => $prefillNro > 0 ? $prefillNro : '',
            'iddoctor' => '',
            'fecha_orden' => '',
            'autorizada' => 0,
            'entregada' => 0,
            'liquidada' => 0,
            'observaciones' => '',
            'numero' => '',
            'sesiones' => '',
            'sesionesreali' => '',
            'costo' => '',
            'pago' => '',
            'costo_os' => '',
            'honorarioextra' => '',
            'idobrasocial' => '',
            'idplan' => '',
            'idpractica' => '',
            'idderivado' => '',
            'sucursal' => '',
            'estado' => '',
            'estado_os' => '',
            'numeautorizacion' => '',
            'fechaderivacion' => '',
            'fechaautorizacion' => '',
            'fechaentrega' => '',
            'honorariofecha' => '',
            'diente' => '',
            'cara' => '',
            'nusiniestro' => '',
            'pagaiva' => '',
            'cerrada' => '',
            'tipoasistencia' => '',
        ];
    }

    /**
     * @param array<string, mixed> $db
     * @param array<string, mixed> $base
     * @return array<string, mixed>
     */
    private static function ordenRowFromDb(array $db, array $base): array
    {
        $row = array_merge($base, $db);
        $row['fecha_orden'] = !empty($db['fecha']) ? substr((string) $db['fecha'], 0, 10) : '';
        foreach (['fechaderivacion', 'fechaautorizacion', 'fechaentrega', 'honorariofecha'] as $f) {
            if (!empty($db[$f])) {
                $row[$f] = substr((string) $db[$f], 0, 10);
            } else {
                $row[$f] = '';
            }
        }
        foreach (['pagaiva', 'cerrada'] as $f) {
            if (isset($db[$f]) && $db[$f] !== null && $db[$f] !== '') {
                $row[$f] = (string) (int) $db[$f];
            } else {
                $row[$f] = '';
            }
        }
        $row['tipoasistencia'] = isset($db['tipoasistencia']) && $db['tipoasistencia'] !== null && $db['tipoasistencia'] !== ''
            ? (string) (int) $db['tipoasistencia'] : '';
        foreach (['costo', 'pago', 'costo_os', 'honorarioextra'] as $f) {
            if (isset($db[$f]) && $db[$f] !== null && $db[$f] !== '' && is_numeric($db[$f])) {
                $v = (float) $db[$f];
                $row[$f] = $v == floor($v) ? (string) (int) $v : rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.');
            } else {
                $row[$f] = '';
            }
        }
        foreach (['numero', 'sesiones', 'sesionesreali', 'numeautorizacion', 'idobrasocial', 'idplan', 'idpractica', 'idderivado', 'sucursal'] as $f) {
            if (isset($db[$f]) && $db[$f] !== null && $db[$f] !== '') {
                $row[$f] = (string) (int) $db[$f];
            } else {
                $row[$f] = '';
            }
        }
        $row['estado'] = trim((string) ($db['estado'] ?? ''));
        $row['estado_os'] = trim((string) ($db['estado_os'] ?? ''));
        $row['diente'] = (string) ($db['diente'] ?? '');
        $row['cara'] = (string) ($db['cara'] ?? '');
        $row['nusiniestro'] = (string) ($db['nusiniestro'] ?? '');
        $row['autorizada'] = !empty($db['autorizada']) ? 1 : 0;
        $row['entregada'] = !empty($db['entregada']) ? 1 : 0;
        $row['liquidada'] = !empty($db['liquidada']) ? 1 : 0;

        return $row;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private static function ordenRowFromPost(array $post, int $id): array
    {
        $row = self::ordenRowDefaults(0);
        $row['id'] = $id;
        foreach (array_keys($row) as $k) {
            if ($k === 'id' || $k === 'autorizada' || $k === 'entregada' || $k === 'liquidada') {
                continue;
            }
            if (isset($post[$k])) {
                $v = $post[$k];
                $row[$k] = is_string($v) ? $v : (string) $v;
            }
        }
        $row['autorizada'] = isset($post['autorizada']) ? 1 : 0;
        $row['entregada'] = isset($post['entregada']) ? 1 : 0;
        $row['liquidada'] = isset($post['liquidada']) ? 1 : 0;

        return $row;
    }

    /**
     * @return array{error: string, values: array<string, mixed>}
     */
    private static function ordenParsePost(PDO $pdo, OrdenesRepository $repo, ?array $user): array
    {
        $nroPaci = (int) ($_POST['NroPaci'] ?? 0);
        $iddoctor = (int) ($_POST['iddoctor'] ?? 0);
        $fechaRaw = trim((string) ($_POST['fecha_orden'] ?? ''));
        $autorizada = isset($_POST['autorizada']) ? 1 : 0;
        $entregada = isset($_POST['entregada']) ? 1 : 0;
        $liquidada = isset($_POST['liquidada']) ? 1 : 0;
        $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

        if ($nroPaci < 1) {
            return ['error' => 'Indicá un Nro. HC (paciente) válido.', 'values' => []];
        }
        if (!$repo->nroHcExists($nroPaci)) {
            return ['error' => 'No existe un paciente con ese Nro. HC.', 'values' => []];
        }
        if ($iddoctor < 1) {
            return ['error' => 'Elegí un profesional.', 'values' => []];
        }
        if (!$repo->doctorExists($iddoctor)) {
            return ['error' => 'Profesional no válido.', 'values' => []];
        }

        $optInt = static function (string $key): ?int {
            $t = trim((string) ($_POST[$key] ?? ''));
            if ($t === '') {
                return null;
            }

            return (int) $t;
        };

        $values = [
            'NroPaci' => $nroPaci,
            'iddoctor' => $iddoctor,
            'fecha' => OrdenesRepository::normalizarFecha($fechaRaw !== '' ? $fechaRaw : null),
            'autorizada' => $autorizada,
            'entregada' => $entregada,
            'liquidada' => $liquidada,
            'observaciones' => $observaciones,
            'numero' => $optInt('numero'),
            'sesiones' => $optInt('sesiones'),
            'sesionesreali' => $optInt('sesionesreali'),
            'numeautorizacion' => $optInt('numeautorizacion'),
            'costo' => post_float_null('costo'),
            'pago' => post_float_null('pago'),
            'costo_os' => post_float_null('costo_os'),
            'honorarioextra' => post_float_null('honorarioextra'),
            'idobrasocial' => $optInt('idobrasocial'),
            'idplan' => $optInt('idplan'),
            'idpractica' => $optInt('idpractica'),
            'idderivado' => $optInt('idderivado'),
            'sucursal' => $optInt('sucursal'),
        ];

        $est = trim((string) ($_POST['estado'] ?? ''));
        $values['estado'] = $est === '' ? null : substr($est, 0, 1);
        $estOs = trim((string) ($_POST['estado_os'] ?? ''));
        $values['estado_os'] = $estOs === '' ? null : substr($estOs, 0, 1);

        $values['fechaderivacion'] = post_date_mysql_null('fechaderivacion');
        $values['fechaautorizacion'] = post_date_mysql_null('fechaautorizacion');
        $values['fechaentrega'] = post_date_mysql_null('fechaentrega');
        $values['honorariofecha'] = post_date_mysql_null('honorariofecha');

        $diente = post_string_null('diente');
        if ($diente !== null && mb_strlen($diente) > 2) {
            return ['error' => 'Campo diente: máximo 2 caracteres.', 'values' => []];
        }
        $values['diente'] = $diente;

        $cara = post_string_null('cara');
        if ($cara !== null && mb_strlen($cara) > 5) {
            return ['error' => 'Campo cara: máximo 5 caracteres.', 'values' => []];
        }
        $values['cara'] = $cara;

        $nus = post_string_null('nusiniestro');
        if ($nus !== null && mb_strlen($nus) > 30) {
            return ['error' => 'Nº siniestro: máximo 30 caracteres.', 'values' => []];
        }
        $values['nusiniestro'] = $nus;

        $values['pagaiva'] = post_smallint_tri('pagaiva');
        $values['cerrada'] = post_smallint_tri('cerrada');

        $tipoAs = trim((string) ($_POST['tipoasistencia'] ?? ''));
        if ($tipoAs !== '' && !preg_match('/^-?\d+$/', $tipoAs)) {
            return ['error' => 'Tipo asistencia debe ser un número entero o vacío.', 'values' => []];
        }
        $values['tipoasistencia'] = $tipoAs === '' ? null : (int) $tipoAs;

        $idcob = (int) ($values['idobrasocial'] ?? 0);
        $idplan = (int) ($values['idplan'] ?? 0);
        if ($idcob > 0 && db_table_exists($pdo, 'lista_coberturas') && !$repo->idExistsInTable('lista_coberturas', $idcob)) {
            return ['error' => 'La cobertura elegida no existe en el catálogo.', 'values' => []];
        }
        if ($idplan > 0 && db_table_exists($pdo, 'lista_planes') && !$repo->idExistsInTable('lista_planes', $idplan)) {
            return ['error' => 'El plan elegido no existe en el catálogo.', 'values' => []];
        }
        if ($idplan > 0 && $idcob > 0 && !$repo->planCompatibleConCobertura($idplan, $idcob)) {
            return ['error' => 'El plan no corresponde a la cobertura indicada.', 'values' => []];
        }

        $idp = (int) ($values['idpractica'] ?? 0);
        if ($idp > 0 && db_table_exists($pdo, 'lista_practicas') && !$repo->idExistsInTable('lista_practicas', $idp)) {
            return ['error' => 'La práctica elegida no existe en el catálogo.', 'values' => []];
        }

        $idd = (int) ($values['idderivado'] ?? 0);
        if ($idd > 0 && db_table_exists($pdo, 'lista_derivaciones') && !$repo->idExistsInTable('lista_derivaciones', $idd)) {
            return ['error' => 'La derivación elegida no existe en el catálogo.', 'values' => []];
        }

        $suc = (int) ($values['sucursal'] ?? 0);
        if ($suc > 0 && db_table_exists($pdo, 'lista_sucursales') && !$repo->idExistsInTable('lista_sucursales', $suc)) {
            return ['error' => 'La sucursal elegida no existe en el catálogo.', 'values' => []];
        }

        if ($user !== null && db_table_has_column($pdo, OrdenesRepository::tableSqlName(), 'idusuariocarga')) {
            $values['idusuariocarga'] = (int) $user['id'];
        }

        return ['error' => '', 'values' => $values];
    }

    /**
     * @return array<string, mixed>
     */
    private static function collectFiltrosOrdenes(): array
    {
        $g = static function (string $k, $default = ''): string {
            return isset($_GET[$k]) ? trim((string) $_GET[$k]) : (string) $default;
        };
        $gi = static function (string $k): int {
            return isset($_GET[$k]) ? (int) $_GET[$k] : 0;
        };

        return [
            'nrohc' => $gi('nrohc'),
            'doctor' => $gi('doctor'),
            'id_desde' => $gi('id_desde'),
            'id_hasta' => $gi('id_hasta'),
            'fecha_desde' => $g('fecha_desde'),
            'fecha_hasta' => $g('fecha_hasta'),
            'honorariofecha_desde' => $g('honorariofecha_desde'),
            'honorariofecha_hasta' => $g('honorariofecha_hasta'),
            'sucursal' => $gi('sucursal'),
            'idobrasocial' => $gi('idobrasocial'),
            'idpractica' => $gi('idpractica'),
            'idderivado' => $gi('idderivado'),
            'idplan' => $gi('idplan'),
            'sesion_doctor' => $gi('sesion_doctor'),
            'sesion_estado' => $g('sesion_estado'),
            'estado' => $g('estado'),
            'estado_os' => $g('estado_os'),
            'estado_multi' => self::collectEstadoMulti('estado'),
            'estado_os_multi' => self::collectEstadoMulti('estado_os'),
            'autorizada' => $g('autorizada'),
            'entregada' => $g('entregada'),
            'liquidada' => $g('liquidada'),
            'pagaiva' => $g('pagaiva'),
            'numeautorizacion' => $g('numeautorizacion'),
        ];
    }

    /**
     * @param array<string, mixed> $f
     */
    private static function buildOrdenesQueryString(array $f): string
    {
        $q = [];
        if (($f['nrohc'] ?? 0) > 0) {
            $q['nrohc'] = (int) $f['nrohc'];
        }
        if (($f['doctor'] ?? 0) > 0) {
            $q['doctor'] = (int) $f['doctor'];
        }
        if (($f['id_desde'] ?? 0) > 0) {
            $q['id_desde'] = (int) $f['id_desde'];
        }
        if (($f['id_hasta'] ?? 0) > 0) {
            $q['id_hasta'] = (int) $f['id_hasta'];
        }
        if (($f['fecha_desde'] ?? '') !== '') {
            $q['fecha_desde'] = (string) $f['fecha_desde'];
        }
        if (($f['fecha_hasta'] ?? '') !== '') {
            $q['fecha_hasta'] = (string) $f['fecha_hasta'];
        }
        if (($f['honorariofecha_desde'] ?? '') !== '') {
            $q['honorariofecha_desde'] = (string) $f['honorariofecha_desde'];
        }
        if (($f['honorariofecha_hasta'] ?? '') !== '') {
            $q['honorariofecha_hasta'] = (string) $f['honorariofecha_hasta'];
        }
        foreach (['sucursal', 'idobrasocial', 'idpractica', 'idderivado', 'idplan', 'sesion_doctor'] as $k) {
            if (($f[$k] ?? 0) > 0) {
                $q[$k] = (int) $f[$k];
            }
        }
        if (($f['sesion_estado'] ?? '') !== '') {
            $q['sesion_estado'] = (string) $f['sesion_estado'];
        }
        if (($f['estado'] ?? '') !== '') {
            $q['estado'] = (string) $f['estado'];
        }
        if (($f['estado_os'] ?? '') !== '') {
            $q['estado_os'] = (string) $f['estado_os'];
        }
        foreach (['estado_multi', 'estado_os_multi'] as $k) {
            $vals = isset($f[$k]) && is_array($f[$k]) ? $f[$k] : [];
            if ($vals !== []) {
                $q[$k] = $vals;
            }
        }
        foreach (['autorizada', 'entregada', 'liquidada', 'pagaiva', 'numeautorizacion'] as $k) {
            if (($f[$k] ?? '') !== '') {
                $q[$k] = (string) $f[$k];
            }
        }

        return http_build_query($q);
    }

    /**
     * @param array<string, mixed> $f
     */
    private static function ordenesHayFiltrosActivos(array $f): bool
    {
        foreach ($f as $v) {
            if (is_array($v)) {
                if ($v === []) {
                    continue;
                }
                return true;
            }
            if ($v === '' || $v === 0) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * @return list<string>
     */
    private static function collectEstadoMulti(string $key): array
    {
        $raw = $_GET[$key . '_multi'] ?? [];
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $v) {
            $s = strtoupper(substr(trim((string) $v), 0, 1));
            if (!in_array($s, ['A', 'F', 'P'], true)) {
                continue;
            }
            if (!in_array($s, $out, true)) {
                $out[] = $s;
            }
        }

        return $out;
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        return (string) ob_get_clean();
    }
}
