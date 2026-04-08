<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (auth_user() !== null) {
    header('Location: /index.php');
    exit;
}

$error = '';
$pdo = null;

try {
    $pdo = db();
} catch (Throwable $e) {
    $error = 'Base de datos no disponible. Configurá config.local.php e importá sql/schema_mysql.sql.';
}

if ($pdo !== null && $error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');

    if ($usuario === '' || $clave === '') {
        $error = 'Completá usuario y contraseña.';
    } else {
        $st = $pdo->prepare('SELECT id, usuario, nombre, password_hash, activo FROM usuarios WHERE usuario = ? LIMIT 1');
        $st->execute([$usuario]);
        $row = $st->fetch();

        if (!$row || !(int) $row['activo']) {
            $error = 'Usuario o contraseña incorrectos.';
        } elseif (!password_verify($clave, $row['password_hash'])) {
            $error = 'Usuario o contraseña incorrectos.';
        } else {
            auth_login((int) $row['id'], $row['usuario'], (string) $row['nombre']);
            header('Location: /index.php');
            exit;
        }
    }
}

ob_start();
?>
<div class="container container-narrow">
    <h1>Ingresar</h1>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <?php if ($error !== '' && $pdo === null): ?>
        <p><a href="/setup.php">Ir al asistente inicial (crear primer usuario)</a> — requiere BD creada y esquema importado.</p>
    <?php endif; ?>
    <?php if ($pdo !== null): ?>
    <form method="post" class="form-card">
        <label>
            Usuario
            <input type="text" name="usuario" required autocomplete="username" value="<?= h($_POST['usuario'] ?? '') ?>">
        </label>
        <label>
            Contraseña
            <input type="password" name="clave" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-primary">Entrar</button>
    </form>
    <?php endif; ?>
</div>
<?php
$body = ob_get_clean();
layout_render('Ingresar', $body, null);
