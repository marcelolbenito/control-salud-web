<?php
declare(strict_types=1);
?>
<div class="container">
    <h1>Sistema y configuración</h1>


    <section class="card-like">
        <h2>Usuarios y roles</h2>
        <?php if (!$rolesEnabled): ?>
            <p class="alert alert-error">Falta columna <code>rol</code> en <code>usuarios</code>. Ejecutá <code>sql/migration_028_usuarios_roles.sql</code>.</p>
        <?php endif; ?>
        <p class="toolbar">
            <a class="btn btn-primary" href="/sistema.php?a=user_form&amp;id=0"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo usuario</a>
        </p>
        <?php if ($usersRows === []): ?>
            <p class="muted">No hay usuarios cargados.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table data-table">
                    <thead>
                    <tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Doctor</th><th>Clínica</th><th>Activo</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usersRows as $u): ?>
                        <tr>
                            <td><code><?= h((string) $u['usuario']) ?></code></td>
                            <td><?= h((string) ($u['nombre'] ?? '')) ?></td>
                            <td><?= h((string) ($u['rol'] ?? 'admin_clinica')) ?></td>
                            <td><?= (int) ($u['id_doctor'] ?? 0) > 0 ? (int) $u['id_doctor'] : '—' ?></td>
                            <td><?= (int) ($u['id_clinica'] ?? 1) ?></td>
                            <td><?= !empty($u['activo']) ? 'Sí' : 'No' ?></td>
                            <td class="actions">
                                <a class="btn btn-sm btn-ghost" href="/sistema.php?a=user_form&amp;id=<?= (int) $u['id'] ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Editar</a>
                                <?php if ((int) ($u['id'] ?? 0) !== (int) ($currentUserId ?? 0)): ?>
                                <form method="post" action="/sistema.php?a=user_delete" class="inline-form" onsubmit="return confirm('¿Eliminar este usuario?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if (!empty($isSuperadmin)): ?>
    <section class="card-like">
        <h2>Avanzado / Migración desde .exe</h2>
        <p class="muted">Sección técnica para auditoría y migración de parámetros legacy. No es de uso operativo diario.</p>
        <?php if ($backupConfigTable === null): ?>
            <p class="alert alert-error">
                No se encontró <code>backup_legacy_Config_*</code> en esta base. La importación desde .exe queda deshabilitada hasta restaurar ese backup.
            </p>
        <?php endif; ?>
        <?php if (!empty($legacyBackup)): ?>
            <p class="muted">
                Backups detectados:
                <?php foreach ($legacyBackup as $i => $name): ?>
                    <code><?= h($name) ?></code><?= $i < count($legacyBackup) - 1 ? ', ' : '' ?>
                <?php endforeach; ?>.
            </p>
        <?php endif; ?>
        <?php if ($backupConfigTable !== null): ?>
            <p class="muted">Tabla fuente activa: <code><?= h($backupConfigTable) ?></code></p>
        <?php endif; ?>
        <?php if (!empty($canSeedFromExe)): ?>
            <form method="post" action="/sistema.php?a=seed_exe_config" class="toolbar" onsubmit="return confirm('Se actualizarán claves existentes con el mismo nombre. ¿Continuar?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary">Importar valores desde backup (.exe) a config</button>
            </form>
        <?php endif; ?>
        <details>
            <summary class="muted" style="cursor:pointer;">Ver mapeo técnico de columnas legacy</summary>
            <div class="table-wrap table-scroll" style="margin-top:0.75rem;">
                <table class="table data-table table-compact">
                    <thead>
                    <tr>
                        <th>Columna (exe)</th>
                        <th>Clave web</th>
                        <th>Descripción</th>
                        <th>Sembrar</th>
                        <th>Vista backup</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($exeMap as $col => $meta): ?>
                        <tr>
                            <td><code><?= h($col) ?></code></td>
                            <td><code><?= h($meta['clave']) ?></code></td>
                            <td><?= h($meta['descripcion']) ?></td>
                            <td><?= !empty($meta['sembrar']) ? 'Sí' : 'No' ?></td>
                            <td class="muted small"><?= h($exePreview[$col] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
    <?php endif; ?>

    <?php if (!empty($isSuperadmin)): ?>
    <section class="card-like">
        <details>
            <summary style="cursor:pointer;"><strong>Ajustes técnicos (config)</strong> <span class="muted">· Avanzado</span></summary>
            <div style="margin-top:0.75rem;">
                <p class="muted">Parámetros clave→valor para necesidades técnicas. No reemplaza Tablas auxiliares.</p>
                <p class="toolbar">
                    <a class="btn btn-primary" href="/sistema.php?a=config_form&amp;id=0"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo parámetro</a>
                    <a class="btn btn-ghost" href="/catalogos.php"><i class="bi bi-journals" aria-hidden="true"></i> Catálogos (listas)</a>
                </p>
                <?php if ($configRows === []): ?>
                    <p class="muted">No hay claves cargadas. Ejemplo: <code>app.nombre_sucursal</code>.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table data-table">
                            <thead>
                            <tr><th>Clave</th><th>Valor</th><th></th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($configRows as $cr): ?>
                                <tr>
                                    <td><code><?= h((string) $cr['clave']) ?></code></td>
                                    <td><?= h((string) ($cr['valor'] ?? '')) ?></td>
                                    <td class="actions">
                                        <a class="btn btn-sm btn-ghost" href="/sistema.php?a=config_form&amp;id=<?= (int) $cr['id'] ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Editar</a>
                                        <form method="post" action="/sistema.php?a=config_delete" class="inline-form" onsubmit="return confirm('¿Eliminar este parámetro?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $cr['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </details>
    </section>
    <?php endif; ?>
</div>
