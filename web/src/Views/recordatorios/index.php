<?php

declare(strict_types=1);

/** @var list<array<string,mixed>> $rows */
/** @var string $estado */
/** @var string $error */
/** @var string $ok */
/** @var bool $habilitado */
/** @var bool $autoConf */
/** @var bool $tablaOk */
/** @var string $modo */
/** @var bool $waHabilitado */
/** @var array{ok:bool,connected:bool,message:string}|null $waStatus */
/** @var string $waDashboard */
/** @var bool $gesisHabilitado */
/** @var bool $gesisConfigurado */
/** @var int $gesisPruebaHc */
/** @var array{ok:bool,connected:bool,message:string}|null $gesisStatus */
$gesisHabilitado = $gesisHabilitado ?? false;
$gesisConfigurado = $gesisConfigurado ?? false;
$gesisPruebaHc = $gesisPruebaHc ?? 0;
$gesisStatus = $gesisStatus ?? null;
?>
<div class="container container-wide">
    <div class="page-head">
        <div>
            <h1>Recordatorios WhatsApp</h1>
            <p class="muted">Cola de confirmaciones y recordatorios (reemplazo de Recordatorios.exe).
                <a href="/recordatorios_plantillas.php">Editar plantillas WhatsApp</a>
                · <a href="/gesis-vincular.php">Vincular WhatsApp (Gesis)</a>
            </p>
        </div>
    </div>

    <?php if (!$tablaOk): ?>
        <p class="alert alert-error">Falta aplicar <code>sql/migration_035_agenda_recordatorios.sql</code>.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <?php if ($ok !== ''): ?>
        <p class="alert alert-info"><?= h($ok) ?></p>
    <?php endif; ?>

    <div class="agenda-summary">
        <div class="agenda-kpi"><span>Módulo</span><strong><?= $habilitado ? 'Activo' : 'Off' ?></strong></div>
        <div class="agenda-kpi"><span>Auto-confirmación</span><strong><?= $autoConf ? 'Sí' : 'No' ?></strong></div>
        <div class="agenda-kpi"><span>Modo envío</span><strong><?= h($modo) ?></strong></div>
        <?php if ($gesisHabilitado || $gesisConfigurado): ?>
            <div class="agenda-kpi"><span>Gesis sesión</span><strong><?= $gesisStatus && !empty($gesisStatus['connected']) ? 'Conectada' : 'Desconectada' ?></strong></div>
        <?php endif; ?>
        <div class="agenda-kpi"><span>En bandeja</span><strong><?= count($rows) ?></strong></div>
    </div>

    <?php if (!$gesisConfigurado): ?>
        <div class="form-card" style="margin-bottom:1rem;border-color:#f87171;background:#fef2f2;">
            <h2 style="margin-top:0;font-size:1.1rem;">WhatsApp Gesis — falta configurar</h2>
            <p class="muted">En el servidor, editá <code>config/config.local.php</code> y agregá el bloque <code>gesis_whatsapp</code> con <code>email</code>, <code>password</code> y <code>test_only_nro_hc</code> (ej. 16059). Luego recargá esta página.</p>
            <p><a class="btn btn-ghost" href="/gesis-vincular.php">Vincular WhatsApp (Gesis)</a></p>
        </div>
    <?php else: ?>
        <div class="form-card" style="margin-bottom:1rem;border-color:#fcd34d;background:#fffbeb;">
            <h2 style="margin-top:0;font-size:1.1rem;">Pruebas Gesis</h2>
            <?php if ($gesisPruebaHc > 0): ?>
                <p class="muted">Envío de prueba al paciente HC <code><?= (int) $gesisPruebaHc ?></code>.</p>
            <?php else: ?>
                <p class="muted">Para el botón de prueba, en <code>config.local.php</code> del servidor definí <code>'test_only_nro_hc' => 16059</code> dentro de <code>gesis_whatsapp</code>.</p>
            <?php endif; ?>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                <?php if ($gesisPruebaHc > 0): ?>
                <form method="post" style="display:inline;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="prueba_gesis">
                    <button type="submit" class="btn btn-primary">Enviar mensaje de prueba</button>
                </form>
                <?php endif; ?>
                <?php if ($gesisHabilitado): ?>
                    <form method="post" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="procesar">
                        <button type="submit" class="btn btn-ghost">Procesar cola pendiente</button>
                    </form>
                <?php endif; ?>
                <a class="btn btn-ghost" href="/gesis-vincular.php">Vincular / estado QR</a>
            </div>
            <?php if ($gesisStatus): ?>
                <p class="muted" style="margin-bottom:0;">Estado API: <strong><?= h((string) ($gesisStatus['message'] ?? '')) ?></strong>
                    — <?= !empty($gesisStatus['connected']) ? 'Conectada' : 'Desconectada' ?></p>
            <?php endif; ?>
            <?php if (!$gesisHabilitado): ?>
                <p class="muted" style="margin-top:0.75rem;margin-bottom:0;">Modo envío actual: <code><?= h($modo) ?></code>. Para envío automático: <code>UPDATE config SET valor='gesis' WHERE clave='recordatorios.modo' AND id_clinica=1;</code></p>
            <?php elseif ($gesisPruebaHc > 0): ?>
                <p class="muted" style="margin-top:0.75rem;margin-bottom:0;"><strong>Modo prueba:</strong> envío automático solo para HC <code><?= (int) $gesisPruebaHc ?></code>. El resto queda manual (wa.me).</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($waHabilitado): ?>
        <div class="form-card" style="margin-bottom:1rem;">
            <h2 style="margin-top:0;font-size:1.1rem;">WhatsApp Web (WAHA) — prueba local</h2>
            <p class="muted">Levantá el contenedor con <code>docker compose --profile waha up -d</code>, escaneá el QR en el panel WAHA y activá <code>recordatorios.modo = whatsapp_web</code> en config.</p>
            <?php if ($waDashboard !== ''): ?>
                <p><a class="btn btn-ghost" href="<?= h($waDashboard) ?>" target="_blank" rel="noopener">Abrir panel WAHA (QR)</a></p>
            <?php endif; ?>
            <?php if ($waStatus): ?>
                <p class="muted">Estado API: <?= h((string) ($waStatus['message'] ?? '')) ?></p>
            <?php else: ?>
                <p class="alert alert-error">No se pudo consultar WAHA. Revisá <code>whatsapp_web.base_url</code> en config.local.php.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="get" class="agenda-filters form-card" action="/recordatorios.php">
        <div class="filter-row">
            <label>Estado
                <select name="estado">
                    <option value="">Todos</option>
                    <?php foreach (['pendiente', 'listo', 'enviado', 'confirmado', 'cancelado', 'error'] as $e): ?>
                        <option value="<?= h($e) ?>"<?= $estado === $e ? ' selected' : '' ?>><?= h(recordatorio_estado_label($e)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Filtrar</button>
        </div>
    </form>

    <form method="post" class="form-card" style="margin-bottom:1rem;">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="ciclo">
        <p class="muted">Ejecuta el mismo ciclo que el cron: encola turnos de mañana y prepara mensajes pendientes.</p>
        <button type="submit" class="btn btn-ghost"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Ejecutar ciclo ahora</button>
    </form>

    <?php if ($rows === []): ?>
        <p class="empty-state">No hay recordatorios en esta vista.</p>
    <?php else: ?>
        <div class="table-wrap table-wrap-datatable">
            <table class="table" id="tbl-recordatorios">
                <thead>
                    <tr>
                        <th>Programado</th>
                        <th>Tipo</th>
                        <th>Turno</th>
                        <th>Paciente HC</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $wa = trim((string) ($r['enlace_whatsapp'] ?? ''));
                        $tf = (string) ($r['turno_fecha'] ?? '');
                        $th = !empty($r['turno_hora']) ? substr((string) $r['turno_hora'], 0, 5) : '';
                        ?>
                        <tr>
                            <td><?= h((string) ($r['programado_en'] ?? '')) ?></td>
                            <td><?= h(recordatorio_tipo_label((string) ($r['tipo'] ?? ''))) ?></td>
                            <td><?= h($tf . ($th !== '' ? ' ' . $th : '')) ?><br><span class="muted"><?= h(trim((string) ($r['doctor_nombre'] ?? ''))) ?></span></td>
                            <td><?= (int) ($r['nro_hc'] ?? 0) ?></td>
                            <td><?= h((string) ($r['telefono_e164'] ?? '—')) ?></td>
                            <td><?= h(recordatorio_estado_label((string) ($r['estado'] ?? ''))) ?></td>
                            <td class="table-actions">
                                <?php if ($wa !== ''): ?>
                                    <a class="btn btn-sm btn-primary" href="<?= h($wa) ?>" target="_blank" rel="noopener">WhatsApp</a>
                                <?php endif; ?>
                                <?php if (($r['estado'] ?? '') === 'listo'): ?>
                                    <form method="post" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="accion" value="enviado">
                                        <input type="hidden" name="id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                        <button type="submit" class="btn btn-sm btn-ghost">Marcar enviado</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
