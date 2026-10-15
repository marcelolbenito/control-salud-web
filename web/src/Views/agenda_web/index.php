<?php

declare(strict_types=1);

/** @var int $idClinica */
/** @var array{nrohc:int,dni:string,nombre:string}|null $paciente */
/** @var string $error */
/** @var int $doctor */
/** @var list<array<string,mixed>> $doctores */
/** @var list<array{fecha:string,titulo:string,horas:list<string>}> $diasLibres */
/** @var list<array<string,mixed>> $turnosPaciente */

$doctorNombre = '';
$doctorEspecialidad = '';
foreach ($doctores as $d) {
    if ((int) ($d['id'] ?? 0) === $doctor) {
        $doctorNombre = (string) ($d['nombre'] ?? '');
        $doctorEspecialidad = trim((string) ($d['especialidad'] ?? ''));
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
            <h2>Elegir profesional</h2>
            <form method="get" class="agenda-filters" action="/agenda_web.php">
                <input type="hidden" name="clinica" value="<?= (int) $idClinica ?>">
                <div class="filter-row">
                    <label>Profesional
                        <select name="doctor" required>
                            <option value="0">— Elegí —</option>
                            <?php foreach ($doctores as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"<?= $doctor === (int) $d['id'] ? ' selected' : '' ?>><?= h((string) ($d['nombre'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Ver turnos</button>
                </div>
            </form>
        </section>

        <?php if ($doctor > 0): ?>
            <section class="card-like agenda-web-card agenda-web-board-card">
                <div class="agenda-web-board-head">
                    <a class="btn btn-ghost btn-sm" href="/agenda_web.php?clinica=<?= (int) $idClinica ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Atrás</a>
                    <div>
                        <h2>Turnos online</h2>
                        <?php if ($doctorEspecialidad !== ''): ?>
                            <p class="agenda-web-esp"><?= h($doctorEspecialidad) ?></p>
                        <?php endif; ?>
                        <p class="agenda-web-prof"><?= h($doctorNombre) ?></p>
                    </div>
                </div>
                <?php if ($diasLibres === []): ?>
                    <p class="empty-state">No hay turnos libres en los próximos días para este profesional.</p>
                <?php else: ?>
                    <form method="post" class="form-paciente">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="reservar">
                        <input type="hidden" name="clinica" value="<?= (int) $idClinica ?>">
                        <input type="hidden" name="doctor" value="<?= (int) $doctor ?>">
                        <div class="agenda-web-board">
                            <?php foreach ($diasLibres as $dia): ?>
                                <section class="agenda-web-col">
                                    <h3><?= h((string) $dia['titulo']) ?></h3>
                                    <div class="agenda-web-col-horas">
                                        <?php foreach ($dia['horas'] as $hora): ?>
                                            <label class="agenda-web-slot">
                                                <input type="radio" name="turno_slot" value="<?= h((string) $dia['fecha'] . '|' . $hora) ?>" required>
                                                <span><?= h((string) $hora) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                        </div>
                        <p class="muted small agenda-web-board-hint">Desplazate hacia los costados para ver más días, y hacia arriba o abajo dentro de cada día para ver todos los horarios. Elegí un horario y confirmá.</p>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-check" aria-hidden="true"></i> Confirmar turno</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
