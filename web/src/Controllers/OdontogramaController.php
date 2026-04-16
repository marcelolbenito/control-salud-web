<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/OdontogramaRepository.php';
require_once dirname(__DIR__) . '/Repositories/PacientesRepository.php';
require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';
require_once dirname(__DIR__) . '/Repositories/OrdenesRepository.php';

final class OdontogramaController
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
        $repo = new OdontogramaRepository($this->pdo);
        if (!$repo->tablaExiste()) {
            $body = '<div class="container"><p class="alert alert-error">Falta la tabla <code>pacientes_odontograma</code>. Ejecutá en MySQL <code>sql/migration_014_odontograma.sql</code>.</p>'
                . '<p class="muted"><a href="/pacientes.php">← Volver a Pacientes</a></p></div>';
            layout_render('Odontograma', $body, $this->user);

            return;
        }

        $pacRepo = new PacientesRepository($this->pdo);
        $idPac = (int) ($_GET['id'] ?? 0);
        $nroHcGet = (int) ($_GET['nrohc'] ?? 0);

        $p = null;
        if ($idPac > 0) {
            $p = $pacRepo->findById($idPac);
        } elseif ($nroHcGet > 0) {
            $p = $pacRepo->findByNroHC($nroHcGet);
            if ($p) {
                $idPac = (int) $p['id'];
            }
        }

        if (!$p) {
            flash_set('Indicá un paciente válido (desde Pacientes o Historia clínica).');
            header('Location: /pacientes.php');
            exit;
        }

        $nroHC = (int) ($p['NroHC'] ?? 0);
        if ($nroHC < 1) {
            flash_set('El paciente no tiene Nro. HC válido.');
            header('Location: /pacientes.php');
            exit;
        }

        $nombre = $this->pacienteNombre($p);
        $docRepo = new DoctoresRepository($this->pdo);
        $doctores = $docRepo->listActivos();
        if ($doctores === []) {
            $doctores = $docRepo->listAllOrdered();
        }

        $ordRepo = new OrdenesRepository($this->pdo);
        $ordenesMini = $ordRepo->listMiniPorNroPaci($nroHC, 40);
        $odontogramaExt = $repo->tieneExtensionV2();
        $mapaSuperficiesActivo = $repo->tablaSuperficiesExiste();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['_action'] ?? '') === 'anular') {
            if (!$odontogramaExt) {
                flash_set('Ejecutá sql/migration_015_odontograma_orden_anulacion.sql para habilitar anulaciones con motivo.');
                header('Location: /odontograma.php?id=' . $idPac);
                exit;
            }
            $rid = (int) ($_POST['registro_id'] ?? 0);
            $mot = trim((string) ($_POST['anular_motivo'] ?? ''));
            $uid = (int) ($this->user['id'] ?? 0);
            if ($rid < 1 || $mot === '') {
                flash_set('Indicá el registro y el motivo de anulación.');
            } elseif ($uid < 1) {
                flash_set('Sesión no válida.');
            } else {
                $row = $repo->findById($rid);
                if (!$row || (int) ($row['NroHC'] ?? 0) !== $nroHC) {
                    flash_set('Registro no válido para este paciente.');
                } elseif ($repo->anular($rid, $nroHC, $mot, $uid)) {
                    flash_set('Registro anulado (permanece visible en historial).');
                } else {
                    flash_set('No se pudo anular (ya estaba anulado o no existe).');
                }
            }
            header('Location: /odontograma.php?id=' . $idPac);
            exit;
        }

        $codigos = $repo->listCodigos();
        $registros = $repo->listByNroHC($nroHC);
        $piezasOpts = self::piezasFdiParaSelect();
        $error = '';
        /** @var array<string, mixed> $formOld */
        $formOld = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pieza = (int) ($_POST['pieza_fdi'] ?? 0);
            $caras = $_POST['cara'] ?? [];
            if (!is_array($caras)) {
                $caras = [];
            }
            $caraStr = self::normalizarCaras($caras);
            $idCodigo = isset($_POST['id_codigo']) && trim((string) $_POST['id_codigo']) !== ''
                ? (int) $_POST['id_codigo'] : null;
            $notas = trim((string) ($_POST['notas'] ?? ''));
            $iddoctor = (int) ($_POST['iddoctor'] ?? 0);
            $iddoctor = $iddoctor > 0 ? $iddoctor : null;

            $idOrden = null;
            if ($odontogramaExt) {
                $ido = (int) ($_POST['id_orden'] ?? 0);
                if ($ido > 0) {
                    if (!$ordRepo->ordenPerteneceAPaciente($ido, $nroHC)) {
                        $error = 'La orden elegida no corresponde a este paciente.';
                    } else {
                        $idOrden = $ido;
                    }
                }
            }

            if ($error === '' && !OdontogramaRepository::piezaFdiValida($pieza)) {
                $error = 'Elegí una pieza dental válida (notación FDI).';
            }
            if ($error === '' && $idCodigo !== null && $idCodigo > 0 && !$this->codigoExiste($idCodigo)) {
                $error = 'El código clínico elegido no es válido.';
            }
            if ($error === '' && $iddoctor !== null && !$this->doctorExiste($iddoctor)) {
                $error = 'Profesional no válido.';
            }
            if ($error === '') {
                $uid = $this->user !== null ? (int) ($this->user['id'] ?? 0) : null;
                $uid = $uid > 0 ? $uid : null;
                $repo->insert($nroHC, $pieza, $caraStr !== '' ? $caraStr : null, $idCodigo, $notas, $iddoctor, $uid, $idOrden);
                flash_set('Registro de odontograma guardado.');
                header('Location: /odontograma.php?id=' . $idPac);
                exit;
            }
            $formOld = $_POST;
        }

        $filasSuperficiesMapa = $mapaSuperficiesActivo ? $repo->listSuperficiesParaMapa($nroHC) : [];

        $body = $this->renderView('odontograma/index', [
            'p' => $p,
            'idPac' => $idPac,
            'nroHC' => $nroHC,
            'nombre' => $nombre,
            'registros' => $registros,
            'codigos' => $codigos,
            'doctores' => $doctores,
            'piezasOpts' => $piezasOpts,
            'error' => $error,
            'formOld' => $formOld,
            'ordenesMini' => $ordenesMini,
            'odontogramaExt' => $odontogramaExt,
            'mapaSuperficiesActivo' => $mapaSuperficiesActivo,
            'filasSuperficiesMapa' => $filasSuperficiesMapa,
        ]);
        layout_render('Odontograma', $body, $this->user, ['skip_datatables' => true]);
    }

    /**
     * Vista para imprimir o guardar PDF (desde el navegador: Imprimir → Guardar como PDF).
     */
    public function imprimir(): void
    {
        $repo = new OdontogramaRepository($this->pdo);
        if (!$repo->tablaExiste()) {
            $body = '<div class="container"><p class="alert alert-error">Falta la tabla <code>pacientes_odontograma</code>.</p>'
                . '<p class="muted"><a href="/pacientes.php">← Pacientes</a></p></div>';
            layout_render('Odontograma', $body, $this->user);

            return;
        }

        $pacRepo = new PacientesRepository($this->pdo);
        $idPac = (int) ($_GET['id'] ?? 0);
        $nroHcGet = (int) ($_GET['nrohc'] ?? 0);

        $p = null;
        if ($idPac > 0) {
            $p = $pacRepo->findById($idPac);
        } elseif ($nroHcGet > 0) {
            $p = $pacRepo->findByNroHC($nroHcGet);
            if ($p) {
                $idPac = (int) $p['id'];
            }
        }

        if (!$p) {
            flash_set('Indicá un paciente válido.');
            header('Location: /pacientes.php');
            exit;
        }

        $nroHC = (int) ($p['NroHC'] ?? 0);
        if ($nroHC < 1) {
            flash_set('El paciente no tiene Nro. HC válido.');
            header('Location: /pacientes.php');
            exit;
        }

        $nombre = $this->pacienteNombre($p);
        $dni = trim((string) ($p['DNI'] ?? ''));
        $codigos = $repo->listCodigos();
        $registros = $repo->listByNroHC($nroHC);
        $odontogramaExt = $repo->tieneExtensionV2();
        $mapaSuperficiesActivo = $repo->tablaSuperficiesExiste();
        $filasSuperficiesMapa = $mapaSuperficiesActivo ? $repo->listSuperficiesParaMapa($nroHC) : [];
        $imprimidoEn = date('Y-m-d H:i:s');
        $impPor = trim((string) (($this->user['nombre'] ?? '') !== '' ? $this->user['nombre'] : ($this->user['usuario'] ?? '')));

        $body = $this->renderView('odontograma/print', [
            'p' => $p,
            'idPac' => $idPac,
            'nroHC' => $nroHC,
            'nombre' => $nombre,
            'dni' => $dni,
            'registros' => $registros,
            'codigos' => $codigos,
            'odontogramaExt' => $odontogramaExt,
            'mapaSuperficiesActivo' => $mapaSuperficiesActivo,
            'filasSuperficiesMapa' => $filasSuperficiesMapa,
            'imprimidoEn' => $imprimidoEn,
            'impPor' => $impPor,
        ]);

        layout_render('Odontograma — Impresión', $body, $this->user, [
            'skip_datatables' => true,
            'body_class' => 'odontograma-print-body',
            'extra_head_html' => '<link rel="stylesheet" href="/css/odontograma-print.css">',
        ]);
    }

    /**
     * @param array<string, mixed> $p
     */
    private function pacienteNombre(array $p): string
    {
        $a = trim((string) ($p['apellido'] ?? ''));
        $n = trim((string) ($p['Nombres'] ?? ''));
        if ($a !== '' && $n !== '') {
            return $a . ', ' . $n;
        }
        if ($a !== '') {
            return $a;
        }

        return $n;
    }

    private function codigoExiste(int $id): bool
    {
        if (!db_table_exists($this->pdo, 'lista_odontograma_codigos')) {
            return false;
        }
        $st = $this->pdo->prepare('SELECT id FROM lista_odontograma_codigos WHERE id = ? LIMIT 1');
        $st->execute([$id]);

        return (bool) $st->fetch();
    }

    private function doctorExiste(int $id): bool
    {
        $st = $this->pdo->prepare('SELECT id FROM lista_doctores WHERE id = ? LIMIT 1');
        $st->execute([$id]);

        return (bool) $st->fetch();
    }

    /**
     * @param list<mixed> $caras
     */
    private static function normalizarCaras(array $caras): string
    {
        $orden = ['M', 'O', 'D', 'V', 'L', 'I'];
        $ok = [];
        foreach ($caras as $c) {
            $c = strtoupper(trim((string) $c));
            if ($c !== '' && in_array($c, $orden, true) && !in_array($c, $ok, true)) {
                $ok[] = $c;
            }
        }
        usort($ok, static function (string $a, string $b) use ($orden): int {
            return array_search($a, $orden, true) <=> array_search($b, $orden, true);
        });

        return implode(',', $ok);
    }

    /**
     * @return list<array{label: string, piezas: list<int>}>
     */
    private static function piezasFdiParaSelect(): array
    {
        $dec = static function (int $a, int $b): array {
            $r = [];
            for ($i = $a; $i >= $b; $i--) {
                $r[] = $i;
            }

            return $r;
        };
        $asc = static function (int $a, int $b): array {
            $r = [];
            for ($i = $a; $i <= $b; $i++) {
                $r[] = $i;
            }

            return $r;
        };

        return [
            ['label' => 'Permanentes — superior derecha (18→11)', 'piezas' => $dec(18, 11)],
            ['label' => 'Permanentes — superior izquierda (21→28)', 'piezas' => $asc(21, 28)],
            ['label' => 'Permanentes — inferior izquierda (38→31)', 'piezas' => $dec(38, 31)],
            ['label' => 'Permanentes — inferior derecha (41→48)', 'piezas' => $asc(41, 48)],
            ['label' => 'Temporales — superior derecha (55→51)', 'piezas' => $dec(55, 51)],
            ['label' => 'Temporales — superior izquierda (61→65)', 'piezas' => $asc(61, 65)],
            ['label' => 'Temporales — inferior izquierda (75→71)', 'piezas' => $dec(75, 71)],
            ['label' => 'Temporales — inferior derecha (81→85)', 'piezas' => $asc(81, 85)],
        ];
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
