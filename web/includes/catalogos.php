<?php

declare(strict_types=1);

/**
 * @return list<array{id:int|string,nombre:?string}>
 */
function catalogo_lista(PDO $pdo, string $tabla): array
{
    if (!db_table_exists($pdo, $tabla)) {
        return [];
    }
    try {
        return $pdo->query("SELECT id, nombre FROM `$tabla` ORDER BY nombre")->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function post_int_null(string $key): ?int
{
    $v = trim((string) ($_POST[$key] ?? ''));
    if ($v === '') {
        return null;
    }

    return (int) $v;
}

function post_string_null(string $key): ?string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    if ($v === '') {
        return null;
    }

    return $v;
}

/** Fecha Y-m-d o datetime-local → NULL o cadena compatible con MySQL. */
function post_date_mysql_null(string $key): ?string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    if ($v === '') {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        return $v . ' 00:00:00';
    }
    $v = str_replace('T', ' ', $v);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) {
        return $v . ':00';
    }

    return $v;
}

/** Para input datetime-local (vacío → NULL). */
function post_datetime_local_mysql_null(string $key): ?string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    if ($v === '') {
        return null;
    }
    $v = str_replace('T', ' ', $v);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v)) {
        return $v . ':00';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $v)) {
        return $v;
    }

    return null;
}

/** Imprime &lt;option&gt; para un &lt;select&gt; de catálogo. */
function catalogo_select_options(array $opts, $selected, string $emptyLabel = '—'): void
{
    $e = static function (?string $s): string {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    };
    ?>
    <option value=""><?= $e($emptyLabel) ?></option>
    <?php
    foreach ($opts as $o) {
        $oid = (int) $o['id'];
        $sel = $selected !== null && $selected !== '' && (int) $selected === $oid;
        ?>
        <option value="<?= $oid ?>"<?= $sel ? ' selected' : '' ?>><?= $e((string) ($o['nombre'] ?? '')) ?></option>
        <?php
    }
}
