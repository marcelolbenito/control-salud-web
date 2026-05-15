<?php

declare(strict_types=1);

/**
 * @param string $orderBy 'nombre' (alfabético) o 'prioridad_id' (prioridad, id; apropiado para códigos).
 * @return list<array{id:int|string,nombre:?string}>
 */
function catalogo_lista(PDO $pdo, string $tabla, string $orderBy = 'nombre'): array
{
    if (!db_table_exists($pdo, $tabla)) {
        return [];
    }
    $orderSql = $orderBy === 'prioridad_id'
        ? 'prioridad IS NULL, prioridad, id'
        : 'nombre';
    try {
        return $pdo->query("SELECT id, nombre FROM `$tabla` ORDER BY $orderSql")->fetchAll();
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

/** Decimal desde POST (coma o punto). Vacío → null. */
function post_float_null(string $key): ?float
{
    $v = trim(str_replace(',', '.', (string) ($_POST[$key] ?? '')));
    if ($v === '') {
        return null;
    }
    if (!is_numeric($v)) {
        return null;
    }

    return (float) $v;
}

/**
 * Si/No/Todos para SMALLINT: '' → null, '1' → 1, '0' → 0.
 */
function post_smallint_tri(string $key): ?int
{
    $v = trim((string) ($_POST[$key] ?? ''));
    if ($v === '') {
        return null;
    }
    if ($v === '1') {
        return 1;
    }
    if ($v === '0') {
        return 0;
    }

    return null;
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

/** Etiqueta unificada para buscador con datalist: «123 - Nombre». */
function catalogo_etiqueta_lista(int $id, ?string $nombre): string
{
    if ($id < 1) {
        return '';
    }
    $nombre = trim((string) $nombre);
    if ($nombre === '') {
        return (string) $id;
    }

    return $id . ' - ' . $nombre;
}

/**
 * Resuelve id de catálogo desde texto (código, «id - nombre» o nombre).
 * Evita confundir «1» con id 1 cuando el usuario aún escribe «11».
 */
function catalogo_resolver_id(array $opts, string $texto): int
{
    $texto = trim($texto);
    if ($texto === '') {
        return 0;
    }

    if (preg_match('/^(\d+)\s*-\s*/u', $texto, $m)) {
        $id = (int) $m[1];

        return catalogo_id_existe_en_opts($opts, $id) ? $id : 0;
    }

    if (preg_match('/^\d+$/', $texto)) {
        $id = (int) $texto;

        return catalogo_id_existe_en_opts($opts, $id) ? $id : 0;
    }

    $norm = mb_strtolower($texto);
    $exactos = [];
    $parciales = [];
    foreach ($opts as $o) {
        $id = (int) ($o['id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        $nombre = mb_strtolower(trim((string) ($o['nombre'] ?? '')));
        if ($nombre === $norm) {
            $exactos[] = $id;
        } elseif ($nombre !== '' && (str_contains($nombre, $norm) || str_contains($norm, $nombre))) {
            $parciales[] = $id;
        }
    }
    if (count($exactos) === 1) {
        return $exactos[0];
    }
    if (count($parciales) === 1) {
        return $parciales[0];
    }

    return 0;
}

/** @param list<array{id:int|string,nombre:?string}> $opts */
function catalogo_id_existe_en_opts(array $opts, int $id): bool
{
    if ($id < 1) {
        return false;
    }
    foreach ($opts as $o) {
        if ((int) ($o['id'] ?? 0) === $id) {
            return true;
        }
    }

    return false;
}

/** Valor inicial del buscador según id seleccionado. */
function catalogo_valor_datalist(array $opts, $selectedId): string
{
    $sid = (int) $selectedId;
    if ($sid < 1) {
        return '';
    }
    foreach ($opts as $o) {
        if ((int) ($o['id'] ?? 0) === $sid) {
            return catalogo_etiqueta_lista($sid, (string) ($o['nombre'] ?? ''));
        }
    }

    return (string) $sid;
}

/** Imprime &lt;datalist&gt; para catálogos buscables (mismo criterio que órdenes). */
function catalogo_imprimir_datalist(array $opts, string $listId): void
{
    $e = static function (?string $s): string {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    };
    echo '<datalist id="' . $e($listId) . '">';
    foreach ($opts as $o) {
        $oid = (int) ($o['id'] ?? 0);
        if ($oid < 1) {
            continue;
        }
        $val = catalogo_etiqueta_lista($oid, (string) ($o['nombre'] ?? ''));
        echo '<option value="' . $e($val) . '" data-id="' . $oid . '"></option>';
    }
    echo '</datalist>';
}

/** Script del buscador de catálogo (una sola vez por página). */
function catalogo_buscar_script_tag(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    echo '<script src="' . htmlspecialchars(url('/js/catalogo-buscar.js'), ENT_QUOTES, 'UTF-8') . '" defer></script>';
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
