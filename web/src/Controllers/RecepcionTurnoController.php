<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/catalogos.php';
require_once dirname(__DIR__) . '/Repositories/AgendaRepository.php';
require_once dirname(__DIR__) . '/Repositories/CajaRepository.php';
require_once dirname(__DIR__) . '/Repositories/OrdenesRepository.php';
require_once dirname(__DIR__) . '/Repositories/PagosRepository.php';

final class RecepcionTurnoController
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
        $cid = user_clinica_id($this->user);
        $agendaRepo = new AgendaRepository($this->pdo, $cid);
        $extAgenda = $agendaRepo->hasExtendedColumns();
        $idTurno = (int) ($_GET['turno'] ?? $_POST['turno'] ?? 0);
        $turno = $idTurno > 0 ? $agendaRepo->findById($idTurno, $extAgenda) : null;
        if (!$turno) {
            flash_set('Turno no encontrado para recepción.');
            header('Location: /agenda.php');
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $tipo = trim((string) ($_POST['tipo_recepcion'] ?? ''));
            $marcarLlegada = $this->debeMarcarLlegada($turno, $extAgenda);
            if ($tipo === 'particular') {
                $error = $this->recepcionarParticular($turno, $agendaRepo, $extAgenda, $marcarLlegada);
            } elseif ($tipo === 'obra_social') {
                $error = $this->recepcionarObraSocial($turno, $agendaRepo, $extAgenda, $marcarLlegada);
            } else {
                $error = 'Elegí el tipo de gestión.';
            }

            if ($error === '') {
                $fecha = (string) ($turno['Fecha'] ?? date('Y-m-d'));
                $doctor = (int) ($turno['Doctor'] ?? 0);
                flash_set($marcarLlegada ? 'Cobro / orden registrado y paciente marcado como llegó.' : 'Cobro / orden registrado.');
                header('Location: /agenda.php?fecha=' . rawurlencode($fecha) . ($doctor > 0 ? '&doctor=' . $doctor : '') . '&turno=' . (int) $turno['id']);
                exit;
            }
        }

        $ordRepo = new OrdenesRepository($this->pdo, $cid);
        $body = $this->renderView('recepcion/turno', [
            'turno' => $turno,
            'error' => $error,
            'cobOpts' => catalogo_lista($this->pdo, 'lista_coberturas', 'prioridad_id'),
            'planesOpts' => $ordRepo->listPlanesConCobertura(),
            'practicaOpts' => $ordRepo->listCatalogIfExists('lista_practicas'),
            'turnoCajaDefault' => self::turnoCajaDefault((string) ($turno['hora'] ?? '')),
            'puedeMarcarLlegada' => $this->puedeMarcarLlegada($turno, $extAgenda),
        ]);
        layout_render('Cobro / Orden del turno', $body, $this->user, ['skip_datatables' => true]);
    }

    private function recepcionarParticular(array $turno, AgendaRepository $agendaRepo, bool $extAgenda, bool $marcarLlegada): string
    {
        $cid = user_clinica_id($this->user);
        $importeTxt = trim(str_replace(',', '.', (string) ($_POST['importe'] ?? '')));
        $formaPago = trim((string) ($_POST['forma_pago'] ?? 'efectivo'));
        $turnoCaja = trim((string) ($_POST['turno_caja'] ?? self::turnoCajaDefault((string) ($turno['hora'] ?? ''))));
        if ($importeTxt === '' || !is_numeric($importeTxt) || (float) $importeTxt <= 0) {
            return 'Indicá un importe válido para cobrar al paciente particular.';
        }

        $pagosRepo = new PagosRepository($this->pdo, $cid);
        if (!$pagosRepo->tableExists()) {
            return 'Falta la tabla pacientes_pagos.';
        }
        $cajaRepo = new CajaRepository($this->pdo, $cid);
        if (!$cajaRepo->tableExists()) {
            return 'Falta la tabla caja.';
        }

        $nroHc = (int) ($turno['NroHC'] ?? 0);
        $doctor = (int) ($turno['Doctor'] ?? 0);
        $fecha = substr((string) ($turno['Fecha'] ?? date('Y-m-d')), 0, 10);
        $importe = (float) $importeTxt;
        $obs = 'Cobro particular. Turno #' . (int) $turno['id'] . '.';

        $this->pdo->beginTransaction();
        try {
            $idPago = $pagosRepo->insert([
                'quien' => 'P',
                'NroPaci' => $nroHc,
                'idorden' => null,
                'importe' => $importe,
                'fecha' => $fecha,
                'forma_pago' => $formaPago !== '' ? $formaPago : 'efectivo',
                'observaciones' => $obs,
            ]);
            $cajaRepo->insertRow([
                'doctor' => $doctor,
                'fechacaja' => $fecha,
                'importecaja' => $importe,
                'idcoberturacaja' => null,
                'modopago' => caja_modopago_from_forma_pago($formaPago),
                'turnocaja' => caja_turno_store_value($turnoCaja) ?? caja_turno_default_por_hora((string) ($turno['hora'] ?? '')),
                'observaciones' => $obs . ' Pago #' . $idPago . '. Medio: ' . $formaPago . '.',
            ]);
            if ($marcarLlegada) {
                $agendaRepo->updateQuickStatus((int) $turno['id'], 'llego', $extAgenda);
            }
            $this->pdo->commit();
        } catch (RuntimeException $e) {
            $this->pdo->rollBack();

            return $e->getMessage();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[control-salud] recepcion particular error: ' . $e->getMessage());
            return 'No se pudo registrar el cobro particular.';
        }

        return '';
    }

    private function recepcionarObraSocial(array $turno, AgendaRepository $agendaRepo, bool $extAgenda, bool $marcarLlegada): string
    {
        $cid = user_clinica_id($this->user);
        if (!db_table_exists($this->pdo, OrdenesRepository::tableSqlName())) {
            return 'Falta la tabla Pacientes Ordenes.';
        }
        $idCob = (int) ($_POST['idobrasocial'] ?? 0);
        $idPlan = (int) ($_POST['idplan'] ?? 0);
        $idPractica = (int) ($_POST['idpractica'] ?? 0);
        if ($idCob < 1) {
            return 'Elegí una obra social/cobertura.';
        }
        if ($idPractica < 1) {
            return 'Elegí una práctica.';
        }
        if (isset($turno['idorden']) && $turno['idorden'] !== null && $turno['idorden'] !== '') {
            return 'Este turno ya tiene una orden vinculada. Abrí la orden existente para editarla.';
        }

        $ordRepo = new OrdenesRepository($this->pdo, $cid);
        $fecha = substr((string) ($turno['Fecha'] ?? date('Y-m-d')), 0, 10);
        $nroAut = trim((string) ($_POST['numeautorizacion'] ?? ''));
        $costoOsTxt = trim(str_replace(',', '.', (string) ($_POST['costo_os'] ?? '')));
        $obs = trim((string) ($_POST['observaciones_os'] ?? ''));

        $this->pdo->beginTransaction();
        try {
            $idOrden = $ordRepo->insertRow([
                'NroPaci' => (int) ($turno['NroHC'] ?? 0),
                'iddoctor' => (int) ($turno['Doctor'] ?? 0),
                'fecha' => OrdenesRepository::normalizarFecha($fecha),
                'autorizada' => 1,
                'fechaautorizacion' => OrdenesRepository::normalizarFecha($fecha),
                'idobrasocial' => $idCob,
                'idplan' => $idPlan > 0 ? $idPlan : null,
                'idpractica' => $idPractica,
                'numeautorizacion' => $nroAut !== '' ? (int) $nroAut : null,
                'costo_os' => ($costoOsTxt !== '' && is_numeric($costoOsTxt)) ? (float) $costoOsTxt : null,
                'estado_os' => 'A',
                'observaciones' => $obs !== '' ? $obs : 'Orden obra social. Turno #' . (int) $turno['id'] . '.',
                'idusuariocarga' => (int) ($this->user['id'] ?? 0),
            ]);
            $agendaRepo->vincularOrden((int) $turno['id'], $idOrden);
            if ($marcarLlegada) {
                $agendaRepo->updateQuickStatus((int) $turno['id'], 'llego', $extAgenda);
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[control-salud] recepcion obra social error: ' . $e->getMessage());
            return 'No se pudo registrar la orden de obra social.';
        }

        return '';
    }

    private function debeMarcarLlegada(array $turno, bool $extAgenda): bool
    {
        return !empty($_POST['marcar_llego']) && $this->puedeMarcarLlegada($turno, $extAgenda);
    }

    private function puedeMarcarLlegada(array $turno, bool $extAgenda): bool
    {
        if (!$extAgenda) {
            return false;
        }

        $estado = (string) ($turno['estado'] ?? '');

        return empty($turno['llegado'])
            && empty($turno['atendido'])
            && empty($turno['falta_turno'])
            && $estado !== 'atendido'
            && $estado !== 'no_asistio';
    }

    private static function turnoCajaDefault(string $hora): string
    {
        return caja_turno_default_por_hora($hora);
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        return (string) ob_get_clean();
    }
}

