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
    csrf_verify();
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');

    if ($usuario === '' || $clave === '') {
        $error = 'Completá usuario y contraseña.';
    } else {
        $selRol = db_table_has_column($pdo, 'usuarios', 'rol') ? ', rol' : '';
        $selDoctor = db_table_has_column($pdo, 'usuarios', 'id_doctor') ? ', id_doctor' : '';
        $st = $pdo->prepare('SELECT id, usuario, nombre, password_hash, activo, id_clinica' . $selRol . $selDoctor . ' FROM usuarios WHERE usuario = ? LIMIT 1');
        $st->execute([$usuario]);
        $row = $st->fetch();

        if (!$row || !(int) $row['activo']) {
            $error = 'Usuario o contraseña incorrectos.';
        } elseif (!password_verify($clave, $row['password_hash'])) {
            $error = 'Usuario o contraseña incorrectos.';
        } else {
            $idClinica = isset($row['id_clinica']) ? (int) $row['id_clinica'] : 1;
            $rol = isset($row['rol']) ? (string) $row['rol'] : 'admin_clinica';
            $idDoctor = isset($row['id_doctor']) ? (int) $row['id_doctor'] : null;
            auth_login_with_role((int) $row['id'], $row['usuario'], (string) $row['nombre'], $idClinica > 0 ? $idClinica : 1, $rol, $idDoctor);
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
        <?= csrf_field() ?>
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
