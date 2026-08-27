<?php

declare(strict_types=1);

/** @var string $error */
/** @var string $ok */
/** @var list<array{id:int,nombre:string}> $sucursales */
/** @var int $idSuc */
/** @var int $sucursalActiva */
/** @var string $nombreClinica */
/** @var string $direccionClinica */
/** @var bool $avisoAnulacion */
/** @var array{nombre:string,mensajerecordatorio:string,mensajerecordatorio2:string,mensajeanular:string} $plantillas */
$nombreClinica = $nombreClinica ?? 'CENTRO PRIVADO SALUD';
$direccionClinica = $direccionClinica ?? '';
$avisoAnulacion = $avisoAnulacion ?? true;
?>
<div class="container container-wide">
    <p><a href="/recordatorios.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Recordatorios</a></p>

    <div class="page-head">
        <h1>Plantillas WhatsApp</h1>
        <p class="muted">Textos de confirmación, recordatorio y anulación de turnos. Se guardan en <code>Sucursales</code> (igual que Recordatorios.exe).</p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <?php if ($ok !== ''): ?>
        <p class="alert alert-info"><?= h($ok) ?></p>
    <?php endif; ?>

    <?php if ($sucursales === []): ?>
        <p class="empty-state">No hay sucursales disponibles para editar plantillas.</p>
    <?php else: ?>
        <form method="get" class="form-card" style="margin-bottom:1rem;">
            <label>
                Sucursal
                <select name="sucursal" onchange="this.form.submit()">
                    <?php foreach ($sucursales as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"<?= (int) $s['id'] === $idSuc ? ' selected' : '' ?>>
                            <?= h($s['nombre']) ?> (id <?= (int) $s['id'] ?>)
                            <?= (int) $s['id'] === $sucursalActiva ? ' — activa en recordatorios' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>

        <div class="form-card" style="margin-bottom:1rem;background:#f8fafc;">
            <h2 class="form-section-title" style="margin-top:0;">Variables disponibles</h2>
            <p class="muted" style="margin-bottom:0.5rem;">Se reemplazan al enviar el mensaje:</p>
            <p class="muted" style="margin:0;line-height:1.6;">
                <code>&lt;paciente&gt;</code> · <code>&lt;fecha&gt;</code> · <code>&lt;hora&gt;</code> ·
                <code>&lt;medico&gt;</code> · <code>&lt;dni&gt;</code> ·
                <code>&lt;NOMBRE_CLINICA&gt;</code> · <code>&lt;DIRECCION_CLINICA&gt;</code>
            </p>
            <p class="muted small" style="margin:0.75rem 0 0;">También podés usar <code>*&lt;paciente&gt;*</code> para negrita en WhatsApp.</p>
        </div>

        <form method="post" class="form-card">
            <?= csrf_field() ?>
            <input type="hidden" name="id_sucursal" value="<?= (int) $idSuc ?>">

            <section class="form-section">
                <h2 class="form-section-title">Datos de la clínica</h2>
                <p class="muted small">Variables <code>&lt;NOMBRE_CLINICA&gt;</code> y <code>&lt;DIRECCION_CLINICA&gt;</code> en los mensajes.</p>
                <label>
                    Nombre
                    <input type="text" name="nombre_clinica" value="<?= h($nombreClinica) ?>" maxlength="120">
                </label>
                <label>
                    Dirección
                    <input type="text" name="direccion_clinica" value="<?= h($direccionClinica) ?>" maxlength="200">
                </label>
                <label class="form-check span-2">
                    <input type="checkbox" name="aviso_anulacion" value="1"<?= $avisoAnulacion ? ' checked' : '' ?>>
                    Enviar WhatsApp al anular un turno desde la agenda
                </label>
            </section>

            <section class="form-section">
                <h2 class="form-section-title">Confirmación al crear turno</h2>
                <p class="muted small">Columna <code>mensajerecordatorio</code> — se envía si está activa la auto-confirmación.</p>
                <label>
                    <textarea name="mensajerecordatorio" rows="8" class="span-2"><?= h($plantillas['mensajerecordatorio']) ?></textarea>
                </label>
            </section>

            <section class="form-section">
                <h2 class="form-section-title">Recordatorio (día anterior)</h2>
                <p class="muted small">Columna <code>mensajerecordatorio2</code> — cron / ciclo de recordatorios.</p>
                <label>
                    <textarea name="mensajerecordatorio2" rows="8" class="span-2"><?= h($plantillas['mensajerecordatorio2']) ?></textarea>
                </label>
            </section>

            <section class="form-section">
                <h2 class="form-section-title">Anulación de turno</h2>
                <p class="muted small">Columna <code>mensajeanular</code> — cuando se cancele un turno con aviso.</p>
                <label>
                    <textarea name="mensajeanular" rows="6" class="span-2"><?= h($plantillas['mensajeanular']) ?></textarea>
                </label>
            </section>

            <label class="form-check span-2">
                <input type="checkbox" name="usar_en_recordatorios" value="1"<?= $idSuc === $sucursalActiva ? ' checked' : '' ?>>
                Usar esta sucursal para los recordatorios del sistema
            </label>
            <p class="muted small">Guarda <code>recordatorios.sucursal_plantillas = <?= (int) $idSuc ?></code> en configuración.</p>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar plantillas</button>
            </div>
        </form>
    <?php endif; ?>
</div>
