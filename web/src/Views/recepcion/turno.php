<?php

declare(strict_types=1);

/** @var array<string,mixed> $turno */
/** @var string $error */
/** @var list<array{id:int|string,nombre:?string}> $cobOpts */
/** @var list<array<string,mixed>> $planesOpts */
/** @var list<array{id:int|string,nombre:?string}> $practicaOpts */
/** @var string $turnoCajaDefault */
/** @var bool $puedeMarcarLlegada */

$fecha = substr((string) ($turno['Fecha'] ?? date('Y-m-d')), 0, 10);
$hora = $turno['hora'] ? substr((string) $turno['hora'], 0, 5) : '—';
$doctor = (int) ($turno['Doctor'] ?? 0);
$idOrdenVinculada = isset($turno['idorden']) && $turno['idorden'] !== null && $turno['idorden'] !== '' ? (int) $turno['idorden'] : 0;
$volverAgenda = '/agenda.php?fecha=' . rawurlencode($fecha) . ($doctor > 0 ? '&doctor=' . $doctor : '') . '&turno=' . (int) $turno['id'];
$ordenTurnoUrl = '/orden_form.php?nrohc=' . (int) ($turno['NroHC'] ?? 0)
    . '&fecha=' . rawurlencode($fecha)
    . ($doctor > 0 ? '&doctor=' . $doctor : '')
    . '&turno=' . (int) $turno['id'];
$ordenVinculadaUrl = '/orden_form.php?id=' . $idOrdenVinculada . '&turno=' . (int) $turno['id'];
?>
<div class="container container-wide recepcion-page">
    <div class="page-head">
        <div>
            <h1>Cobro / Orden del turno</h1>
            <p class="muted">Registrá el cobro o la orden. El estado <strong>Llegó</strong> se maneja aparte desde Agenda.</p>
        </div>
        <p class="muted"><a href="<?= h($volverAgenda) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Agenda</a></p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <section class="card-like recepcion-turno-resumen">
        <h2>Turno seleccionado</h2>
        <div class="recepcion-resumen-grid">
            <p><span>Paciente</span><strong><?= h((string) ($turno['paciente_nombre'] ?? '—')) ?></strong></p>
            <p><span>HC</span><strong><?= (int) ($turno['NroHC'] ?? 0) ?></strong></p>
            <p><span>Fecha/hora</span><strong><?= h($fecha) ?> <?= h($hora) ?></strong></p>
            <p><span>Profesional</span><strong><?= h((string) ($turno['doctor_nombre'] ?? '—')) ?></strong></p>
            <p>
                <span>Orden</span>
                <strong>
                    <?php if ($idOrdenVinculada > 0): ?>
                        <a href="<?= h($ordenVinculadaUrl) ?>">#<?= $idOrdenVinculada ?> · Ver / editar</a>
                    <?php else: ?>
                        Sin orden vinculada
                    <?php endif; ?>
                </strong>
            </p>
        </div>
    </section>

    <div class="recepcion-opciones">
        <section class="form-section card-like recepcion-opcion">
            <header class="recepcion-opcion-head">
                <h2 class="form-section-title"><i class="bi bi-cash-coin" aria-hidden="true"></i> Paciente particular</h2>
                <p class="muted">Cobrar la atención y registrar el movimiento en caja.</p>
            </header>
            <form method="post" class="form-paciente">
                <?= csrf_field() ?>
                <input type="hidden" name="turno" value="<?= (int) $turno['id'] ?>">
                <input type="hidden" name="tipo_recepcion" value="particular">
                <div class="form-grid-ext recepcion-form-grid">
                    <label>Importe a cobrar *
                        <input type="text" name="importe" required inputmode="decimal" placeholder="0,00">
                    </label>
                    <label>Forma de pago
                        <select name="forma_pago">
                            <option value="efectivo">Efectivo</option>
                            <option value="debito">Tarjeta débito</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="electronico">Electrónico</option>
                            <option value="credito">Tarjeta crédito</option>
                            <option value="otro">Otro</option>
                        </select>
                    </label>
                    <label>Turno caja
                        <select name="turno_caja">
                            <option value="mañana"<?= $turnoCajaDefault === 'mañana' ? ' selected' : '' ?>>Mañana</option>
                            <option value="tarde"<?= $turnoCajaDefault === 'tarde' ? ' selected' : '' ?>>Tarde</option>
                        </select>
                    </label>
                </div>
                <?php if ($puedeMarcarLlegada): ?>
                    <div class="checkbox-inline-group recepcion-llegada-opcion">
                        <label><input type="checkbox" name="marcar_llego" value="1"> También marcar como <strong>Llegó</strong> en Agenda</label>
                    </div>
                <?php endif; ?>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle" aria-hidden="true"></i> Registrar cobro</button>
                </div>
            </form>
        </section>

        <section class="form-section card-like recepcion-opcion">
            <header class="recepcion-opcion-head">
                <h2 class="form-section-title"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Obra social</h2>
                <p class="muted">Usá el formulario completo de órdenes, con los mismos datos que una orden manual.</p>
            </header>
            <?php if ($idOrdenVinculada > 0): ?>
                <p class="alert alert-info">Este turno ya tiene una orden vinculada. Para cambiar datos de cobertura, práctica o autorización, abrí la orden existente.</p>
                <p class="form-actions">
                    <a class="btn btn-primary" href="<?= h($ordenVinculadaUrl) ?>"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Ver / editar orden #<?= $idOrdenVinculada ?></a>
                </p>
            <?php else: ?>
                <p class="muted">Se abrirá la orden completa ya prellenada con paciente, fecha y profesional del turno. Al guardarla, quedará vinculada a este turno.</p>
                <p class="form-actions">
                    <a class="btn btn-primary" href="<?= h($ordenTurnoUrl) ?>"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i> Cargar orden completa</a>
                </p>
            <?php endif; ?>
        </section>
    </div>
</div>

