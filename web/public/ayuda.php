<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_auth();

$user = auth_user();
$mod = strtolower(trim((string) ($_GET['mod'] ?? 'inicio')));

$ayudas = [
    'inicio' => [
        'titulo' => 'Ayuda por pantalla',
        'resumen' => 'Seleccioná una pantalla para ver pasos simples de uso.',
        'puntos' => [
            'Agenda diaria: flujo recomendado -> Llegó -> Llamar -> Atendido.',
            'Anunciador de sala: pantalla monitor (solo visual) y pantalla operador (gestión).',
            'Recordatorios WhatsApp: módulo planificado para próxima etapa.',
            'Si una pantalla cambia, su ayuda se actualiza en esta sección.',
        ],
    ],
    'agenda' => [
        'titulo' => 'Pantalla: Agenda diaria',
        'resumen' => 'Uso rápido por turno para recepción y profesionales.',
        'puntos' => [
            'Doble click en una fila: marca "Llegó".',
            '"Llamar" aparece solo si el turno está en "Llegó".',
            '"Atendido" aparece cuando el paciente ya fue llamado.',
            'Si el turno está atendido, queda solo la acción "Nueva orden".',
            'La tabla conserva el orden elegido (por ejemplo, por Paciente).',
        ],
    ],
    'anunciador' => [
        'titulo' => 'Pantalla: Anunciador',
        'resumen' => 'Llamados en sala con dos modos de uso.',
        'puntos' => [
            'Modo monitor: /anunciador.php (sin menú ni botones, solo datos de llamado).',
            'Modo operador: /anunciador.php?modo=operador (gestiona estados de llamado).',
            'Al marcar "Atendido" en Agenda se finaliza automáticamente el llamado activo.',
            'Si no hay llamados activos, el monitor muestra un aviso simple.',
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
        <a class="btn btn-ghost<?= $mod === 'anunciador' ? ' is-active' : '' ?>" href="/ayuda.php?mod=anunciador">Pantalla Anunciador</a>
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

