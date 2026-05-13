<?php

declare(strict_types=1);

/** @var int $idClinica */
/** @var array{nrohc:int,dni:string,nombre:string}|null $paciente */
/** @var string $error */
/** @var string $fecha */
/** @var int $doctor */
/** @var list<array<string,mixed>> $doctores */
/** @var array<string,mixed> $disp */
/** @var list<array<string,mixed>> $turnosPaciente */

$slots = is_array($disp['slots'] ?? null) ? $disp['slots'] : [];
$occupied = is_array($disp['occupied'] ?? null) ? $disp['occupied'] : [];
$blocked = is_array($disp['blocked'] ?? null) ? $disp['blocked'] : [];
$fechaValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) && $fecha >= date('Y-m-d');
$doctorNombre = '';
foreach ($doctores as $d) {
    if ((int) ($d['id'] ?? 0) === $doctor) {
        $doctorNombre = (string) ($d['nombre'] ?? '');
        break;
    }
}
?>
<div class="container agenda-web-page">
    <div class="page-head">
        <div>
            <h1>Agenda Web</h1>
            <p class="muted">Reservá un turno ingresando con tu número de documento.</p>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($paciente === null): ?>
        <section class="card-like agenda-web-card">
            <h2>Ingresar</h2>
            <p class="muted">Usá el mismo documento registrado en la clínica. Si no aparecés, comunicate con recepción.</p>
            <form method="post" class="form-paciente">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="login">
                <input type="hidden" name="clinica" value="<?= (int) $idClinica ?>">
                <div class="form-grid-ext">
                    <label>Número de documento
                        <input type="text" name="documento" required inputmode="numeric" autocomplete="off" placeholder="Ej. 30111222">
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Ingresar</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="card-like agenda-web-card">
            <div class="agenda-web-paciente-head">
                <div>
                    <h2><?= h($paciente['nombre'] !== '' ? $paciente['nombre'] : 'Paciente') ?></h2>
                    <p class="muted">DNI <?= h($paciente['dni']) ?> · HC <?= (int) $paciente['nrohc'] ?></p>
                </div>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="logout">
                    <input type="hidden" name="clinica" value="<?= (int) $idClinica ?>">
                    <button type="submit" class="btn btn-ghost btn-sm">Salir</button>
                </form>
            </div>
        </section>

        <?php if ($turnosPaciente !== []): ?>
            <section class="card-like agenda-web-card">
                <h2>Mis próximos turnos</h2>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Profesional</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($turnosPaciente as $t): ?>
                                <tr>
                                    <td><?= h((string) ($t['fecha'] ?? '')) ?></td>
                                    <td><?= h((string) ($t['hora'] ?? '')) ?></td>
                                    <td><?= h((string) ($t['doctor'] ?? '')) ?></td>
                                    <td><span class="badge-estado estado-pendiente"><?= h((string) ($t['estado'] ?? 'pendiente')) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <section class="card-like agenda-web-card">
            <h2>Buscar disponibilidad</h2>
            <form method="get" class="agenda-filters" action="/agenda_web.php">
                <input type="hidden" name="clinica" value="<?= (int) $idClinica ?>">
                <div class="filter-row">
                    <label>Fecha
                        <input type="date" name="fecha" min="<?= h(date('Y-m-d')) ?>" value="<?= h($fecha) ?>">
                    </label>
                    <label>Profesional
                        <select name="doctor" required>
                            <option value="0">— Elegí —</option>
                            <?php foreach ($doctores as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"<?= $doctor === (int) $d['id'] ? ' selected' : '' ?>><?= h((string) ($d['nombre'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Ver horarios</button>
                </div>
            </form>
        </section>

        <?php if ($doctor > 0): ?>
            <section class="card-like agenda-web-card">
                <h2>Horarios disponibles</h2>
                <p class="muted"><?= h($fecha) ?><?= $doctorNombre !== '' ? ' · ' . h($doctorNombre) : '' ?></p>
                <?php if (!$fechaValida): ?>
                    <p class="alert alert-error">Elegí una fecha desde hoy en adelante.</p>
                <?php else: ?>
                    <?php
                    $hayLibres = false;
                    foreach ($slots as $slot) {
                        if ((int) ($occupied[$slot] ?? 0) === 0 && (int) ($blocked[$slot] ?? 0) === 0) {
                            $hayLibres = true;
                            break;
                        }
                    }
                    ?>
                    <?php if (!$hayLibres): ?>
                        <p class="empty-state">No hay horarios libres para ese día y profesional.</p>
                    <?php else: ?>
                        <form method="post" class="form-paciente">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="reservar">
                            <input type="hidden" name="clinica" value="<?= (int) $idClinica ?>">
                            <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
                            <input type="hidden" name="doctor" value="<?= (int) $doctor ?>">
                            <div class="agenda-web-slots">
                                <?php foreach ($slots as $slot): ?>
                                    <?php
                                    $slot = (string) $slot;
                                    $ocup = (int) ($occupied[$slot] ?? 0);
                                    $bloq = (int) ($blocked[$slot] ?? 0);
                                    $free = $ocup === 0 && $bloq === 0;
                                    ?>
                                    <?php if ($free): ?>
                                        <label class="agenda-web-slot">
                                            <input type="radio" name="hora" value="<?= h($slot) ?>" required>
                                            <span><?= h($slot) ?></span>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-check" aria-hidden="true"></i> Confirmar turno</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
