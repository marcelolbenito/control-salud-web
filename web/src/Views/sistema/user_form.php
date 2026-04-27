<?php
declare(strict_types=1);
?>
<div class="container container-narrow">
    <h1><?= (int) ($row['id'] ?? 0) > 0 ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <label>
            Usuario
            <input type="text" name="usuario" required maxlength="50" value="<?= h((string) ($row['usuario'] ?? '')) ?>">
        </label>
        <label>
            Nombre visible
            <input type="text" name="nombre" maxlength="100" value="<?= h((string) ($row['nombre'] ?? '')) ?>">
        </label>
        <label>
            Email
            <input type="email" name="email" maxlength="100" value="<?= h((string) ($row['email'] ?? '')) ?>">
        </label>
        <?php if ($rolesEnabled): ?>
        <label>
            Rol
            <select name="rol" id="user_rol">
                <option value="doctor"<?= (string) ($row['rol'] ?? '') === 'doctor' ? ' selected' : '' ?>>Doctor</option>
                <option value="admin_clinica"<?= (string) ($row['rol'] ?? '') === 'admin_clinica' ? ' selected' : '' ?>>Admin de clínica</option>
                <?php if ($isSuperadmin): ?>
                <option value="superadmin"<?= (string) ($row['rol'] ?? '') === 'superadmin' ? ' selected' : '' ?>>Superadmin</option>
                <?php endif; ?>
            </select>
        </label>
        <?php endif; ?>
        <label id="wrap_id_doctor">
            Profesional asociado (para rol doctor)
            <select name="id_doctor" id="id_doctor">
                <option value="0">— Sin vincular —</option>
                <?php foreach (($doctoresOpts ?? []) as $d): ?>
                    <option value="<?= (int) ($d['id'] ?? 0) ?>"<?= (int) ($row['id_doctor'] ?? 0) === (int) ($d['id'] ?? 0) ? ' selected' : '' ?>>
                        <?= h((string) ($d['nombre'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="muted">Se listan doctores sin usuario asociado (y el actualmente vinculado al editar).</small>
        </label>
        <label>
            Id clínica
            <input type="number" name="id_clinica" min="1" value="<?= (int) ($row['id_clinica'] ?? 1) ?>"<?= $isSuperadmin ? '' : ' readonly' ?>>
        </label>
        <label class="form-check"><input type="checkbox" name="activo" value="1" <?= !empty($row['activo']) ? ' checked' : '' ?>> Activo</label>
        <label>
            <?= (int) ($row['id'] ?? 0) > 0 ? 'Nueva contraseña (opcional)' : 'Contraseña inicial (mín. 8)' ?>
            <input type="password" name="clave" minlength="8" autocomplete="new-password">
        </label>
        <label>
            Repetir contraseña
            <input type="password" name="clave2" minlength="8" autocomplete="new-password">
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="/sistema.php"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
        </div>
    </form>
</div>
<script>
(function () {
    var rol = document.getElementById('user_rol');
    var wrapDoc = document.getElementById('wrap_id_doctor');
    var selDoc = document.getElementById('id_doctor');
    if (!rol || !wrapDoc || !selDoc) return;
    function syncDocField() {
        var isDoctor = String(rol.value || '') === 'doctor';
        wrapDoc.style.display = isDoctor ? '' : 'none';
        selDoc.disabled = !isDoctor;
    }
    rol.addEventListener('change', syncDocField);
    syncDocField();
})();
</script>
