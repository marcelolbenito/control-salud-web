<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/db_schema.php';

$error = '';
$pdo = null;

try {
    $pdo = db();
} catch (Throwable $e) {
    $error = 'No se pudo conectar: ' . $e->getMessage();
}

if ($error === '') {
    try {
        $count = (int) $pdo->query('SELECT COUNT(*) c FROM usuarios')->fetch()['c'];
    } catch (Throwable $e) {
        $count = -1;
        $error = 'La tabla usuarios no existe. Importá el archivo Control Salud/sql/schema_mysql.sql en tu base MySQL.';
    }
}

if ($error === '' && $count > 0) {
    header('Location: /login.php');
    exit;
}

if ($error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');
    $clave2 = (string) ($_POST['clave2'] ?? '');

    if ($usuario === '' || $clave === '') {
        $error = 'Usuario y contraseña son obligatorios.';
    } elseif ($clave !== $clave2) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($clave) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        if (db_table_has_column($pdo, 'usuarios', 'id_clinica') && db_table_has_column($pdo, 'usuarios', 'rol')) {
            $st = $pdo->prepare('INSERT INTO usuarios (usuario, password_hash, nombre, activo, id_clinica, rol) VALUES (?, ?, ?, 1, 1, ?)');
            $st->execute([$usuario, $hash, $nombre ?: $usuario, 'superadmin']);
        } elseif (db_table_has_column($pdo, 'usuarios', 'id_clinica')) {
            $st = $pdo->prepare('INSERT INTO usuarios (usuario, password_hash, nombre, activo, id_clinica) VALUES (?, ?, ?, 1, 1)');
            $st->execute([$usuario, $hash, $nombre ?: $usuario]);
        } else {
            $st = $pdo->prepare('INSERT INTO usuarios (usuario, password_hash, nombre, activo) VALUES (?, ?, ?, 1)');
            $st->execute([$usuario, $hash, $nombre ?: $usuario]);
        }
        header('Location: /login.php');
        exit;
    }
}

ob_start();
?>
<div class="container container-narrow">
    <h1>Configuración inicial</h1>
    <p>Creá el primer usuario administrador (superadmin). Esta pantalla solo funciona mientras la tabla <code>usuarios</code> esté vacía.</p>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <?php if ($pdo && $error === ''): ?>
        <form method="post" class="form-card">
            <?= csrf_field() ?>
            <label>
                Usuario
                <input type="text" name="usuario" required autocomplete="username" value="<?= h($_POST['usuario'] ?? '') ?>">
            </label>
            <label>
                Nombre visible (opcional)
                <input type="text" name="nombre" autocomplete="name" value="<?= h($_POST['nombre'] ?? '') ?>">
            </label>
            <label>
                Contraseña (mín. 8 caracteres)
                <input type="password" name="clave" required autocomplete="new-password" minlength="8">
            </label>
            <label>
                Repetir contraseña
                <input type="password" name="clave2" required autocomplete="new-password" minlength="8">
            </label>
            <button type="submit" class="btn btn-primary">Crear administrador</button>
        </form>
    <?php endif; ?>
    <p class="hint">Copiá <code>web/config/config.example.php</code> a <code>web/config/config.local.php</code> y definí host, base <code>control_salud</code>, usuario y clave MySQL.</p>
</div>
<?php
$body = ob_get_clean();
layout_render('Configuración inicial', $body, null);
