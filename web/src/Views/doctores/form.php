<?php

declare(strict_types=1);
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="/doctores.php">← Volver al listado</a></p>
    </div>
    <?php if (!$ext): ?>
        <p class="alert alert-error" style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
            Para datos como en el exe (especialidad, matrícula, etc.), ejecutá en MySQL <code>sql/migration_003_doctores_agenda_exe.sql</code>.
        </p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-paciente">
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">

        <section class="form-section">
            <h2 class="form-section-title">Identificación</h2>
            <div class="form-grid-ext">
                <label class="span-2">Nombre completo *
                    <input type="text" name="nombre" required value="<?= h((string) $row['nombre']) ?>">
                </label>
                <?php if ($ext): ?>
                    <?php if (!empty($especialidadesOpts)): ?>
                        <label>Especialidad (catálogo)
                            <select name="especialidad_catalogo_id">
                                <option value="">— Elegí —</option>
                                <?php foreach ($especialidadesOpts as $esp): ?>
                                    <?php
                                    $espNombre = trim((string) ($esp['nombre'] ?? ''));
                                    $selected = $espNombre !== '' && strtolower($espNombre) === strtolower((string) ($row['especialidad'] ?? ''));
                                    ?>
                                    <option value="<?= (int) $esp['id'] ?>"<?= $selected ? ' selected' : '' ?>><?= h($espNombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Especialidad (texto libre)
                            <input type="text" name="especialidad" value="<?= h((string) ($row['especialidad'] ?? '')) ?>" maxlength="100" placeholder="Opcional si no está en catálogo">
                        </label>
                    <?php else: ?>
                        <label>Especialidad
                            <input type="text" name="especialidad" value="<?= h((string) ($row['especialidad'] ?? '')) ?>" maxlength="100">
                        </label>
                    <?php endif; ?>
                    <label>Matrícula
                        <input type="text" name="matricula" value="<?= h((string) ($row['matricula'] ?? '')) ?>" maxlength="20">
                    </label>
                    <label>Teléfono
                        <input type="text" name="telefono" value="<?= h((string) ($row['telefono'] ?? '')) ?>">
                    </label>
                    <label>Consultorio
                        <input type="text" name="consultorio" value="<?= h((string) ($row['consultorio'] ?? '')) ?>" maxlength="30">
                    </label>
                    <label>Domicilio
                        <input type="text" name="domicilio" value="<?= h((string) ($row['domicilio'] ?? '')) ?>">
                    </label>
                    <label>Localidad
                        <input type="text" name="localidad" value="<?= h((string) ($row['localidad'] ?? '')) ?>">
                    </label>
                <?php endif; ?>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Opciones</h2>
            <div class="form-grid-ext">
                <label class="form-check">
                    <input type="checkbox" name="medicoconvenio" value="1" <?= (int) $row['medicoconvenio'] ? ' checked' : '' ?>>
                    Médico convenio
                </label>
                <label class="form-check">
                    <input type="checkbox" name="bloquearmisconsultas" value="1" <?= (int) $row['bloquearmisconsultas'] ? ' checked' : '' ?>>
                    Bloquear mis consultas
                </label>
                <label class="form-check">
                    <input type="checkbox" name="activo" value="1" <?= (int) $row['activo'] ? ' checked' : '' ?>>
                    Activo
                </label>
                <label class="span-2">Notas internas
                    <textarea name="notas"><?= h((string) $row['notas']) ?></textarea>
                </label>
                <?php if (!$ext): ?>
                    <p class="hint span-2">En Access hay muchos permisos por usuario (accesoagenda, etc.); la web por ahora no los replica.</p>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($legacyAgendaDisponible)): ?>
            <?php
            $dias = [
                'do' => 'Domingo',
                'lu' => 'Lunes',
                'ma' => 'Martes',
                'mi' => 'Miércoles',
                'ju' => 'Jueves',
                'vi' => 'Viernes',
                'sa' => 'Sábado',
            ];
            ?>
            <section class="form-section">
                <h2 class="form-section-title">Agenda semanal (horarios y duración)</h2>
                <p class="hint">Configuración usada por la grilla de turnos: si un día queda sin franjas, se interpreta como día no laborable.</p>
                <div class="page-actions">
                    <button type="button" id="agenda-copiar-lu-vie" class="btn btn-ghost btn-sm">
                        <i class="bi bi-files" aria-hidden="true"></i>
                        Copiar Lunes a Viernes
                    </button>
                </div>
                <div class="table-scroll">
                    <table class="table table-compact">
                        <thead>
                        <tr>
                            <th>Día</th>
                            <th>Duración (min)</th>
                            <th>Mañana desde</th>
                            <th>Mañana hasta</th>
                            <th>Tarde desde</th>
                            <th>Tarde hasta</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dias as $k => $nombreDia): ?>
                            <?php $cfg = $agendaSemana[$k] ?? ['duracion' => '15', 'manana_desde' => '', 'manana_hasta' => '', 'tarde_desde' => '', 'tarde_hasta' => '']; ?>
                            <tr>
                                <td><strong><?= h($nombreDia) ?></strong></td>
                                <td><input type="number" min="5" step="5" name="agenda_duracion[<?= h($k) ?>]" value="<?= h((string) ($cfg['duracion'] ?? '15')) ?>"></td>
                                <td><input type="time" name="agenda_manana_desde[<?= h($k) ?>]" value="<?= h((string) ($cfg['manana_desde'] ?? '')) ?>"></td>
                                <td><input type="time" name="agenda_manana_hasta[<?= h($k) ?>]" value="<?= h((string) ($cfg['manana_hasta'] ?? '')) ?>"></td>
                                <td><input type="time" name="agenda_tarde_desde[<?= h($k) ?>]" value="<?= h((string) ($cfg['tarde_desde'] ?? '')) ?>"></td>
                                <td><input type="time" name="agenda_tarde_hasta[<?= h($k) ?>]" value="<?= h((string) ($cfg['tarde_hasta'] ?? '')) ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <script>
                    (function () {
                        const btn = document.getElementById('agenda-copiar-lu-vie');
                        if (!btn) return;

                        function val(name, day) {
                            const el = document.querySelector('[name="' + name + '[' + day + ']"]');
                            return el ? String(el.value || '') : '';
                        }

                        function setVal(name, day, value) {
                            const el = document.querySelector('[name="' + name + '[' + day + ']"]');
                            if (el) {
                                el.value = value;
                            }
                        }

                        btn.addEventListener('click', function () {
                            const sourceDay = 'lu';
                            const targetDays = ['ma', 'mi', 'ju', 'vi'];
                            const fields = [
                                'agenda_duracion',
                                'agenda_manana_desde',
                                'agenda_manana_hasta',
                                'agenda_tarde_desde',
                                'agenda_tarde_hasta'
                            ];

                            targetDays.forEach(function (day) {
                                fields.forEach(function (field) {
                                    setVal(field, day, val(field, sourceDay));
                                });
                            });
                        });
                    })();
                </script>
            </section>
        <?php endif; ?>

        <div class="form-actions form-section">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="/doctores.php">Cancelar</a>
        </div>
    </form>
</div>
