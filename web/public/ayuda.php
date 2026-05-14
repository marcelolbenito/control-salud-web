<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_auth();

$user = auth_user();
$mod = strtolower(trim((string) ($_GET['mod'] ?? 'inicio')));

$ayudas = [
    'inicio' => [
        'titulo' => 'Ayuda: flujo diario',
        'resumen' => 'Guía rápida del circuito operativo recomendado para recepción, profesionales y administración.',
        'puntos' => [
            '1. Agenda diaria: se crea o revisa el turno del paciente.',
            '2. Llegada: si el paciente ya está en el centro, se marca "Llegó" desde Agenda.',
            '3. Gestión administrativa: usar "Cobro / Orden" para registrar pago particular o cargar/ver la orden de obra social.',
            '4. Atención: el profesional llama al paciente con "Llamar" y luego marca "Atendido".',
            '5. Control diario: administración revisa turnos sin orden, sin pago o sin caja.',
            '6. Caja: los movimientos no se editan ni se borran; las correcciones se hacen con contra movimientos.',
            '7. Cierre de caja: al final del turno o día se declara efectivo, se revisa diferencia y se cierra.',
        ],
    ],
    'agenda' => [
        'titulo' => 'Pantalla: Agenda diaria',
        'resumen' => 'Pantalla central para ver turnos, marcar estados y acceder a la gestión administrativa.',
        'puntos' => [
            'Usar filtros de fecha y profesional para ver la agenda del día.',
            '"Llegó" marca presencia física del paciente en el centro. No significa que esté cobrado.',
            '"Cobro / Orden" abre la pantalla administrativa del turno. Sirve para pago particular u obra social.',
            '"Llamar" aparece cuando el paciente ya llegó y lo envía al Anunciador de sala.',
            '"Atendido" aparece después del llamado y cierra el circuito asistencial del turno.',
            'Si el paciente ya llegó, se ocultan acciones de edición/borrado para evitar cambios inseguros.',
            'La tabla conserva el orden elegido, por ejemplo por paciente u hora.',
        ],
    ],
    'cobro_orden' => [
        'titulo' => 'Pantalla: Cobro / Orden',
        'resumen' => 'Gestión administrativa vinculada a un turno.',
        'puntos' => [
            'Paciente particular: registrar importe, forma de pago y turno de caja.',
            'Obra social: usar "Cargar orden completa" para abrir el mismo formulario de órdenes manuales, prellenado con paciente, fecha y profesional.',
            'Si el turno ya tiene orden, se muestra "Ver / editar orden" para no duplicarla.',
            'La llegada física se maneja aparte. Solo marcar "También marcar como Llegó" si corresponde.',
            'El pago particular genera movimiento en Caja para que entre en el control diario.',
        ],
    ],
    'control_diario' => [
        'titulo' => 'Pantalla: Control diario',
        'resumen' => 'Revisión administrativa de turnos del día.',
        'puntos' => [
            'Permite detectar turnos atendidos sin orden, sin pago o con pago sin caja.',
            'Los indicadores superiores funcionan como accesos rápidos a cada filtro.',
            'Desde cada fila se puede volver a "Cobro / Orden" para resolver pendientes.',
            'También tiene accesos a pagos y caja del día para revisar el detalle.',
            'Es la pantalla recomendada antes de cerrar caja o facturar.',
        ],
    ],
    'caja' => [
        'titulo' => 'Pantalla: Caja y Cierre de caja',
        'resumen' => 'Registro de movimientos, correcciones y cierre diario.',
        'puntos' => [
            'Caja muestra ingresos y egresos del día, con filtros por fecha, profesional, cobertura y texto.',
            'Los movimientos de caja no se editan ni se borran.',
            'Si hubo un error, usar "Contra movimiento" para generar un movimiento opuesto.',
            'Cierre de caja calcula ingresos, egresos, total sistema y permite declarar efectivo.',
            'Una caja cerrada queda como registro histórico; se puede ver su detalle, pero no reescribir el cierre.',
            'Para multi-clínica, cada caja y cierre pertenece a la clínica del usuario.',
        ],
    ],
    'anunciador' => [
        'titulo' => 'Pantalla: Anunciador',
        'resumen' => 'Llamados en sala con dos modos de uso.',
        'puntos' => [
            'Modo monitor: /anunciador.php (sin menú ni botones, solo pacientes llamados).',
            'Modo operador: /anunciador.php?modo=operador (gestiona estados de llamado).',
            'Desde Agenda, "Llamar" publica paciente, consultorio y profesional en el monitor.',
            'Al marcar "Atendido" en Agenda se finaliza automáticamente el llamado activo.',
            'Si no hay llamados activos, el monitor muestra un aviso simple.',
        ],
    ],
    'agenda_web' => [
        'titulo' => 'Pantalla pública: Agenda Web',
        'resumen' => 'URL para que el paciente solicite/confirmé un turno disponible.',
        'puntos' => [
            'URL base: /agenda_web.php?clinica=1.',
            'El paciente ingresa con su número de documento registrado.',
            'Luego elige fecha, profesional y horario disponible.',
            'Al confirmar, el turno queda registrado en Agenda diaria como pendiente y confirmado.',
            'El sistema respeta disponibilidad, turnos ya ocupados y bloqueos de agenda.',
            'MVP actual: todavía falta política de cancelación/reprogramación y confirmaciones por WhatsApp.',
        ],
    ],
    'recordatorios' => [
        'titulo' => 'Pantalla: Recordatorios (próximamente)',
        'resumen' => 'Módulo planificado para avisos de turnos por WhatsApp.',
        'puntos' => [
            'Canal previsto v1: WhatsApp con proveedor externo (Twilio).',
            'Checklist y diccionario de datos documentados en REQUISITOS_Sistema_ControlSalud.md.',
            'Aún no implementado en la aplicación.',
        ],
    ],
];

if (!isset($ayudas[$mod])) {
    $mod = 'inicio';
}
$actual = $ayudas[$mod];

ob_start();
?>
<div class="container">
    <div class="page-head">
        <h1><?= h($actual['titulo']) ?></h1>
        <p class="muted"><?= h($actual['resumen']) ?></p>
    </div>

    <div class="page-actions">
        <a class="btn btn-ghost<?= $mod === 'inicio' ? ' is-active' : '' ?>" href="/ayuda.php?mod=inicio">Todas las pantallas</a>
        <a class="btn btn-ghost<?= $mod === 'agenda' ? ' is-active' : '' ?>" href="/ayuda.php?mod=agenda">Pantalla Agenda</a>
        <a class="btn btn-ghost<?= $mod === 'cobro_orden' ? ' is-active' : '' ?>" href="/ayuda.php?mod=cobro_orden">Cobro / Orden</a>
        <a class="btn btn-ghost<?= $mod === 'control_diario' ? ' is-active' : '' ?>" href="/ayuda.php?mod=control_diario">Control diario</a>
        <a class="btn btn-ghost<?= $mod === 'caja' ? ' is-active' : '' ?>" href="/ayuda.php?mod=caja">Caja</a>
        <a class="btn btn-ghost<?= $mod === 'anunciador' ? ' is-active' : '' ?>" href="/ayuda.php?mod=anunciador">Pantalla Anunciador</a>
        <a class="btn btn-ghost<?= $mod === 'agenda_web' ? ' is-active' : '' ?>" href="/ayuda.php?mod=agenda_web">Agenda Web</a>
        <a class="btn btn-ghost<?= $mod === 'recordatorios' ? ' is-active' : '' ?>" href="/ayuda.php?mod=recordatorios">Pantalla Recordatorios</a>
    </div>

    <section class="card-like">
        <ul>
            <?php foreach ($actual['puntos'] as $p): ?>
                <li><?= h((string) $p) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<?php
$body = (string) ob_get_clean();
layout_render('Ayuda', $body, $user, ['skip_datatables' => true]);

