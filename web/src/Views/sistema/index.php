<?php
declare(strict_types=1);
?>
<div class="container">
    <h1>Sistema y configuración</h1>
    <?php if ($backupConfigTable === null): ?>
        <p class="alert alert-error">
            No se encontró <code>backup_legacy_Config_*</code> en esta base. El mapeo se muestra como referencia y la importación desde .exe queda deshabilitada hasta restaurar ese backup.
        </p>
    <?php endif; ?>

    <section class="card-like">
        <h2>Configuración del programa original (.exe)</h2>
        <p>
            En el escritorio, <strong>Control Salud</strong> guarda ajustes en la tabla Access/SQL Server <code>Config</code>:
            muchas columnas (decenas) con flags, rutas y opciones poco documentadas. No es un simple “panel de preferencias”:
            mezcla parámetros de negocio, impresión, integraciones, etc.
        </p>
        <p>
            En la <strong>versión web</strong> no replicamos esa tabla tal cual: usamos la tabla MySQL <code>config</code>
            con pares <strong>clave → valor</strong> (texto) para todo lo que definamos a futuro (nombre de sucursal, rutas de adjuntos, etc.).
        </p>
        <?php if (!empty($legacyBackup)): ?>
            <p class="muted">
                Tras la migración, el volcado legacy quedó respaldado como
                <?php foreach ($legacyBackup as $i => $name): ?>
                    <code><?= h($name) ?></code><?= $i < count($legacyBackup) - 1 ? ', ' : '' ?>
                <?php endforeach; ?>.
                Ahí están los valores originales del .exe por si hace falta consultarlos (Workbench/phpMyAdmin).
            </p>
        <?php endif; ?>
    </section>

    <section class="card-like">
        <h2>Mapeo: columnas <code>Config</code> (.exe) → claves <code>config</code> (web)</h2>
        <p class="muted">
            Cada fila del backup (una sola fila de parámetros en el exe típico) se puede traducir a claves con nombre estable.
            Las columnas en <strong>RTF</strong> se guardan como texto RTF hasta definir si se convierten a HTML en la web.
        </p>
        <?php if ($backupConfigTable === null): ?>
            <p class="muted">No hay tabla <code>backup_legacy_Config_*</code> en esta base: columna «Vista backup» vacía hasta que exista un respaldo.</p>
        <?php else: ?>
            <p class="muted">Tabla fuente: <code><?= h($backupConfigTable) ?></code></p>
        <?php endif; ?>
        <?php if (!empty($canSeedFromExe)): ?>
            <form method="post" action="/sistema.php?a=seed_exe_config" class="toolbar" onsubmit="return confirm('Se actualizarán claves existentes con el mismo nombre. ¿Continuar?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary">Importar valores desde backup (.exe) a config</button>
            </form>
        <?php endif; ?>
        <div class="table-wrap table-scroll">
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
    </section>

    <section class="card-like">
        <h2>Parámetros web (<code>config</code>)</h2>
        <p class="toolbar">
            <a class="btn btn-primary" href="/sistema.php?a=config_form&amp;id=0">Nuevo parámetro</a>
            <a class="btn btn-ghost" href="/catalogos.php">Catálogos (listas)</a>
        </p>
        <?php if ($configRows === []): ?>
            <p class="muted">No hay claves cargadas. Podés crear las que necesites (ej. <code>app.nombre_sucursal</code>).</p>
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
                                <a class="btn btn-sm btn-ghost" href="/sistema.php?a=config_form&amp;id=<?= (int) $cr['id'] ?>">Editar</a>
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
    </section>
</div>
