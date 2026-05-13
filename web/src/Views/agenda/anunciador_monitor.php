<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anunciador de sala</title>
    <meta http-equiv="refresh" content="<?= (int) $autoRefresh ?>">
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #0a1020;
            color: #f3f6ff;
        }
        .wrap {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
        }
        h1 {
            margin: 0 0 18px 0;
            font-size: 40px;
            line-height: 1.1;
            letter-spacing: 0.3px;
        }
        .sub {
            margin: 0 0 20px 0;
            font-size: 20px;
            color: #b9c7f7;
        }
        .board {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .call {
            border: 1px solid #26365e;
            border-left: 6px solid #5aa7ff;
            background: #101a31;
            border-radius: 12px;
            padding: 16px;
            min-height: 120px;
        }
        .paciente {
            margin: 0 0 8px 0;
            font-size: 38px;
            line-height: 1;
            font-weight: 800;
        }
        .consultorio {
            margin: 0 0 8px 0;
            font-size: 30px;
            line-height: 1.1;
            color: #9ed0ff;
            font-weight: 700;
        }
        .prof {
            margin: 0;
            font-size: 21px;
            color: #dce6ff;
        }
        .empty {
            font-size: 30px;
            color: #c8d6ff;
            border: 1px dashed #3f5280;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            font-size: 16px;
            color: #9bb2e4;
        }
    </style>
</head>
<body>
<main class="wrap">
    <h1>Anunciador de sala</h1>
    <p class="sub">Dirigirse al consultorio indicado</p>

    <?php if (!$tablaDisponible): ?>
        <p class="empty">Falta configuración del módulo anunciador.</p>
    <?php elseif ($rows === []): ?>
        <p class="empty">No hay llamados activos en este momento.</p>
    <?php else: ?>
        <section class="board">
            <?php foreach ($rows as $r): ?>
                <article class="call">
                    <p class="paciente"><?= h((string) ($r['paciente_display'] ?? 'Paciente')) ?></p>
                    <p class="consultorio"><?= h((string) ($r['consultorio'] ?? 'Consultorio')) ?></p>
                    <p class="prof">Profesional: <?= h((string) ($r['doctor_nombre'] ?? '—')) ?></p>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <p class="footer">Actualización automática cada <?= (int) $autoRefresh ?> segundos</p>
</main>
</body>
</html>

