<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/PagosRepository.php';
require_once dirname(__DIR__) . '/Repositories/CajaRepository.php';

final class PagosController
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
        $repo = new PagosRepository($this->pdo, user_clinica_id($this->user));
        if (!$repo->tableExists()) {
            $body = '<div class="container"><p class="alert alert-error">Falta la tabla <code>pacientes_pagos</code>. Importá <code>sql/schema_mysql.sql</code>.</p></div>';
            layout_render('Pagos', $body, $this->user);

            return;
        }
        $f = self::collectFiltros();
        $rows = $repo->listForIndex($f);
        $queryString = self::buildQueryString($f);
        $hayFiltros = self::hayFiltros($f);
        $totalFiltro = 0.0;
        foreach ($rows as $r) {
            $totalFiltro += (float) ($r['importe'] ?? 0);
        }

        $body = $this->renderView('pagos/index', [
            'rows' => $rows,
            'f' => $f,
            'queryString' => $queryString,
            'hayFiltros' => $hayFiltros,
            'totalFiltro' => $totalFiltro,
        ]);
        layout_render('Pagos', $body, $this->user);
    }

    public function form(): void
    {
        $repo = new PagosRepository($this->pdo, user_clinica_id($this->user));
        if (!$repo->tableExists()) {
            flash_set('Falta la tabla pacientes_pagos.');
            header('Location: /index.php');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $queryString = self::buildQueryString(self::collectFiltros());
        $volver = '/pagos.php' . ($queryString !== '' ? '?' . $queryString : '');
        $row = [
            'id' => 0,
            'quien' => 'P',
            'NroPaci' => isset($_GET['nrohc']) ? (int) $_GET['nrohc'] : '',
            'idorden' => isset($_GET['idorden']) ? (int) $_GET['idorden'] : '',
            'importe' => '',
            'fecha' => date('Y-m-d'),
            'forma_pago' => 'efectivo',
            'observaciones' => '',
        ];
        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Pago no encontrado.');
                header('Location: /pagos.php');
                exit;
            }
            $row = array_merge($row, $loaded);
            $row['fecha'] = !empty($loaded['fecha']) ? substr((string) $loaded['fecha'], 0, 10) : '';
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $id = (int) ($_POST['id'] ?? 0);
            $idPrevOrden = 0;
            $importePrev = 0.0;
            if ($id > 0) {
                $prev = $repo->findById($id);
                if ($prev) {
                    $idPrevOrden = (int) ($prev['idorden'] ?? 0);
                    $importePrev = (float) ($prev['importe'] ?? 0);
                }
            }

            $quien = trim((string) ($_POST['quien'] ?? 'P'));
            $nroHc = (int) ($_POST['NroPaci'] ?? 0);
            $idOrdenTxt = trim((string) ($_POST['idorden'] ?? ''));
            $idOrden = $idOrdenTxt === '' ? null : (int) $idOrdenTxt;
            $importeTxt = trim(str_replace(',', '.', (string) ($_POST['importe'] ?? '')));
            $fecha = trim((string) ($_POST['fecha'] ?? ''));
            $formaPago = trim((string) ($_POST['forma_pago'] ?? 'efectivo'));
            $obs = trim((string) ($_POST['observaciones'] ?? ''));

            if (!in_array($quien, ['P', 'C', 'O'], true)) {
                $error = 'Tipo de pagador inválido.';
            } elseif ($nroHc < 1 || !$repo->pacienteExists($nroHc)) {
                $error = 'Paciente inválido.';
            } elseif ($importeTxt === '' || !is_numeric($importeTxt) || (float) $importeTxt <= 0) {
                $error = 'Importe inválido.';
            } elseif ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $error = 'Fecha inválida.';
            } elseif ($idOrden !== null && $idOrden > 0) {
                $ord = $repo->findOrdenById($idOrden);
                if (!$ord) {
                    $error = 'Orden no válida.';
                } elseif ((int) ($ord['NroPaci'] ?? 0) !== $nroHc) {
                    $error = 'La orden no corresponde al paciente.';
                }
            }

            if ($error === '') {
                $vals = [
                    'quien' => $quien,
                    'NroPaci' => $nroHc,
                    'idorden' => ($idOrden !== null && $idOrden > 0) ? $idOrden : null,
                    'importe' => (float) $importeTxt,
                    'fecha' => $fecha,
                    'forma_pago' => $formaPago !== '' ? $formaPago : null,
                    'observaciones' => $obs !== '' ? $obs : null,
                ];
                if ($id > 0) {
                    $repo->update($id, $vals);
                    $msg = 'Pago actualizado.';
                } else {
                    $id = $repo->insert($vals);
                    $msg = 'Pago registrado.';
                }

                if ($idPrevOrden > 0 && $idPrevOrden !== (int) ($vals['idorden'] ?? 0)) {
                    self::sincronizarPagoOrden($repo, $idPrevOrden);
                }
                $idOrdenFinal = (int) ($vals['idorden'] ?? 0);
                if ($idOrdenFinal > 0) {
                    self::sincronizarPagoOrden($repo, $idOrdenFinal);
                }

                $deltaCaja = (float) $vals['importe'] - $importePrev;
                $fechaCaja = (string) $vals['fecha'];
                if ($idPrevOrden > 0 && $idPrevOrden !== $idOrdenFinal) {
                    self::registrarMovimientoCaja($this->pdo, $idPrevOrden, -$importePrev, $nroHc, 'Ajuste por cambio de orden en pago #' . $id, $fechaCaja);
                    self::registrarMovimientoCaja($this->pdo, $idOrdenFinal, (float) $vals['importe'], $nroHc, 'Cobro registrado (pago #' . $id . ')', $fechaCaja);
                } else {
                    self::registrarMovimientoCaja($this->pdo, $idOrdenFinal, $deltaCaja, $nroHc, 'Cobro/ajuste de pago #' . $id, $fechaCaja);
                }

                flash_set($msg);
                $retQs = trim((string) ($_POST['pagos_return_qs'] ?? $queryString));
                header('Location: /pagos.php' . ($retQs !== '' ? '?' . $retQs : ''));
                exit;
            }

            $row = [
                'id' => $id,
                'quien' => $quien,
                'NroPaci' => $nroHc > 0 ? $nroHc : '',
                'idorden' => $idOrdenTxt,
                'importe' => $importeTxt,
                'fecha' => $fecha,
                'forma_pago' => $formaPago,
                'observaciones' => $obs,
            ];
            $queryString = trim((string) ($_POST['pagos_return_qs'] ?? $queryString));
            $volver = '/pagos.php' . ($queryString !== '' ? '?' . $queryString : '');
        }

        $titulo = (int) ($row['id'] ?? 0) > 0 ? 'Editar pago' : 'Nuevo pago';
        $body = $this->renderView('pagos/form', [
            'row' => $row,
            'error' => $error,
            'titulo' => $titulo,
            'volver' => $volver,
            'queryString' => $queryString,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pagos.php');
            exit;
        }
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /pagos.php');
            exit;
        }
        $repo = new PagosRepository($this->pdo, user_clinica_id($this->user));
        $prev = $repo->findById($id);
        if (!$prev) {
            header('Location: /pagos.php');
            exit;
        }
        $repo->delete($id);
        $idOrden = (int) ($prev['idorden'] ?? 0);
        $nroHc = (int) ($prev['NroPaci'] ?? 0);
        $importe = (float) ($prev['importe'] ?? 0);
        if ($idOrden > 0) {
            self::sincronizarPagoOrden($repo, $idOrden);
            $fechaCaja = !empty($prev['fecha']) ? substr((string) $prev['fecha'], 0, 10) : date('Y-m-d');
            self::registrarMovimientoCaja($this->pdo, $idOrden, -$importe, $nroHc, 'Anulación de pago #' . $id, $fechaCaja);
        }
        flash_set('Pago eliminado.');
        header('Location: /pagos.php');
        exit;
    }

    public function recibo(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $repo = new PagosRepository($this->pdo, user_clinica_id($this->user));
        $row = $id > 0 ? $repo->findById($id) : null;
        if (!$row) {
            flash_set('Pago no encontrado para recibo.');
            header('Location: /pagos.php');
            exit;
        }
        $body = $this->renderView('pagos/recibo', ['row' => $row]);
        layout_render('Recibo de pago', $body, $this->user);
    }

    private static function sincronizarPagoOrden(PagosRepository $repo, int $idOrden): void
    {
        if ($idOrden < 1) {
            return;
        }
        $sumPago = $repo->sumPagosPacienteByOrden($idOrden);
        $repo->updatePagoOrden($idOrden, $sumPago);
    }

    private static function registrarMovimientoCaja(PDO $pdo, int $idOrden, float $delta, int $nroHc, string $motivo, string $fechaYmd): void
    {
        if ($idOrden < 1 || abs($delta) < 0.00001) {
            return;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaYmd)) {
            $fechaYmd = date('Y-m-d');
        }
        $cid = user_clinica_id(auth_user());
        $repo = new PagosRepository($pdo, $cid);
        $ord = $repo->findOrdenById($idOrden);
        if (!$ord) {
            return;
        }
        $doctor = (int) ($ord['iddoctor'] ?? 0);
        if ($doctor < 1) {
            return;
        }
        $cajaRepo = new CajaRepository($pdo, $cid);
        if (!$cajaRepo->tableExists()) {
            return;
        }
        $idCob = (int) ($ord['idobrasocial'] ?? 0);
        $obs = $motivo . '. Orden #' . $idOrden . '. HC: ' . $nroHc . '.';
        $cajaRepo->insertRow([
            'doctor' => $doctor,
            'fechacaja' => $fechaYmd,
            'importecaja' => $delta,
            'idcoberturacaja' => $idCob > 0 ? $idCob : null,
            'turnocaja' => 'Orden #' . $idOrden,
            'observaciones' => $obs,
        ]);
    }

    /**
     * @return array<string, string|int>
     */
    private static function collectFiltros(): array
    {
        return [
            'nrohc' => isset($_GET['nrohc']) ? (int) $_GET['nrohc'] : 0,
            'idorden' => isset($_GET['idorden']) ? (int) $_GET['idorden'] : 0,
            'quien' => isset($_GET['quien']) ? trim((string) $_GET['quien']) : '',
            'fecha_desde' => isset($_GET['fecha_desde']) ? trim((string) $_GET['fecha_desde']) : '',
            'fecha_hasta' => isset($_GET['fecha_hasta']) ? trim((string) $_GET['fecha_hasta']) : '',
        ];
    }

    /**
     * @param array<string, string|int> $f
     */
    private static function buildQueryString(array $f): string
    {
        $q = [];
        foreach (['nrohc', 'idorden'] as $k) {
            if (($f[$k] ?? 0) > 0) {
                $q[$k] = (int) $f[$k];
            }
        }
        foreach (['quien', 'fecha_desde', 'fecha_hasta'] as $k) {
            if (($f[$k] ?? '') !== '') {
                $q[$k] = (string) $f[$k];
            }
        }

        return http_build_query($q);
    }

    /**
     * @param array<string, string|int> $f
     */
    private static function hayFiltros(array $f): bool
    {
        foreach ($f as $v) {
            if ($v === '' || $v === 0) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}

