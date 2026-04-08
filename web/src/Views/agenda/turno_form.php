<?php

declare(strict_types=1);
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="<?= h($volver) ?>">← Volver a la agenda</a></p>
    </div>
    <?php if (!$ext): ?>
        <p class="alert alert-error" style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
            Para campos como en Access (llegó, confirmado, motivo, etc.), ejecutá <code>sql/migration_003_doctores_agenda_exe.sql</code>.
        </p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-paciente">
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">

        <section class="form-section">
            <h2 class="form-section-title">Turno</h2>
            <div class="form-grid-ext">
                <label>Fecha *
                    <input type="date" name="Fecha" required value="<?= h((string) $row['Fecha']) ?>">
                </label>
                <label>Hora
                    <input type="time" name="hora" value="<?= h((string) $row['hora']) ?>">
                </label>
                <label>Nro HC *
                    <input type="number" name="NroHC" required min="1" value="<?= $row['NroHC'] === '' ? '' : (int) $row['NroHC'] ?>">
                </label>
                <?php if ($ext): ?>
                    <label class="span-2">Nombre paciente (texto, como en exe)
                        <input type="text" name="paciente_nombre" value="<?= h((string) ($row['paciente_nombre'] ?? '')) ?>" maxlength="60" placeholder="Se puede completar solo al guardar desde el paciente">
                    </label>
                <?php endif; ?>
                <label>Profesional *
                    <select name="Doctor" required>
                        <option value="">— Elegí —</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= (int) $row['Doctor'] === (int) $d['id'] ? ' selected' : '' ?>><?= h($d['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>ID orden
                    <input type="number" name="idorden" min="1" value="<?= h((string) $row['idorden']) ?>">
                </label>
                <label>Estado (resumen web)
                    <select name="estado">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= h($e) ?>"<?= $row['estado'] === $e ? ' selected' : '' ?>><?= h($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if ($ext): ?>
                    <label>Motivo (id — Lista Motivos en exe)
                        <input type="number" name="motivo" value="<?= $row['motivo'] !== null && $row['motivo'] !== '' ? (int) $row['motivo'] : '' ?>">
                    </label>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($ext): ?>
        <section class="form-section">
            <h2 class="form-section-title">Flags (como Access)</h2>
            <div class="form-grid-ext">
                <label class="form-check"><input type="checkbox" name="atendido" value="1" <?= !empty($row['atendido']) ? ' checked' : '' ?>> Atendido</label>
                <label class="form-check"><input type="checkbox" name="pagado" value="1" <?= !empty($row['pagado']) ? ' checked' : '' ?>> Pagado</label>
                <label class="form-check"><input type="checkbox" name="llegado" value="1" <?= !empty($row['llegado']) ? ' checked' : '' ?>> Llegó</label>
                <label class="form-check"><input type="checkbox" name="confirmado" value="1" <?= !empty($row['confirmado']) ? ' checked' : '' ?>> Confirmado</label>
                <label class="form-check"><input type="checkbox" name="falta_turno" value="1" <?= !empty($row['falta_turno']) ? ' checked' : '' ?>> Faltó</label>
                <label class="form-check"><input type="checkbox" name="reingresar" value="1" <?= !empty($row['reingresar']) ? ' checked' : '' ?>> Reingresar</label>
                <label>Hora llegada
                    <input type="time" name="llegado_hora" value="<?= h((string) ($row['llegado_hora'] ?? '')) ?>">
                </label>
                <label>Primera vez (código)
                    <input type="number" name="primera_vez" value="<?= h((string) ($row['primera_vez'] ?? '')) ?>">
                </label>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Sesión / caja (exe)</h2>
            <div class="form-grid-ext">
                <label>Nº sesión
                    <input type="number" name="num_sesion" value="<?= h((string) ($row['num_sesion'] ?? '')) ?>">
                </label>
                <label>ID sesión
                    <input type="number" name="id_sesion" value="<?= h((string) ($row['id_sesion'] ?? '')) ?>">
                </label>
                <label>ID caja
                    <input type="number" name="id_caja" value="<?= h((string) ($row['id_caja'] ?? '')) ?>">
                </label>
                <label>Usuario asignó turno
                    <input type="text" name="usuario_asignado" value="<?= h((string) ($row['usuario_asignado'] ?? '')) ?>" maxlength="50">
                </label>
                <label>Fecha/hora asignado
                    <input type="datetime-local" name="fechahora_asignado" value="<?= h((string) ($row['fechahora_asignado'] ?? '')) ?>">
                </label>
                <label>Alta paciente web
                    <input type="number" name="alta_paci_web" value="<?= h((string) ($row['alta_paci_web'] ?? '')) ?>">
                </label>
            </div>
        </section>
        <?php endif; ?>

        <section class="form-section">
            <h2 class="form-section-title">Observaciones</h2>
            <div class="form-grid-ext">
                <label class="span-2">
                    <textarea name="observaciones" rows="3"><?= h((string) $row['observaciones']) ?></textarea>
                </label>
            </div>
        </section>

        <div class="form-actions form-section">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="<?= h($volver) ?>">Cancelar</a>
        </div>
    </form>
</div>
