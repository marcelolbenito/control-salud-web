<?php

declare(strict_types=1);

/**
 * Helpers de caja alineados al exe (Control Salud VB6).
 *
 * turnocaja legacy: 0 = sin turno / día, 1 = mañana, 2 = tarde.
 * modopago legacy: 0 = efectivo, 1 = otro, 3 = débito, 4 = crédito, 5 = electrónico/transferencia.
 * (El valor 2 casi no se usa en datos migrados.)
 */

/** @return array<int, string> */
function caja_modopago_labels(): array
{
    return [
        0 => 'Efectivo',
        1 => 'Otro',
        3 => 'Tarjeta débito',
        4 => 'Tarjeta crédito',
        5 => 'Electrónico / transferencia',
    ];
}

function caja_modopago_label(?int $modopago): string
{
    if ($modopago === null) {
        return 'Sin especificar';
    }
    $labels = caja_modopago_labels();

    return $labels[$modopago] ?? ('Modo #' . $modopago);
}

function caja_modopago_from_forma_pago(string $formaPago): int
{
    $fp = strtolower(trim($formaPago));
    if ($fp === '' || $fp === 'efectivo') {
        return 0;
    }
    if ($fp === 'debito' || str_contains($fp, 'débito') || str_contains($fp, 'debito')) {
        return 3;
    }
    if ($fp === 'credito' || str_contains($fp, 'crédito') || str_contains($fp, 'credito')) {
        return 4;
    }
    if ($fp === 'transferencia' || $fp === 'electronico' || str_contains($fp, 'mercadopago')) {
        return 5;
    }

    return 1;
}

function caja_forma_pago_from_modopago(?int $modopago): string
{
    if ($modopago === 3) {
        return 'debito';
    }
    if ($modopago === 4) {
        return 'credito';
    }
    if ($modopago === 5) {
        return 'transferencia';
    }
    if ($modopago === 1) {
        return 'otro';
    }

    return 'efectivo';
}

/** Turno de cierre web: dia | mañana | tarde */
function caja_turno_cierre_key(?string $turnocaja): string
{
    $t = strtolower(trim((string) $turnocaja));
    if ($t === '1' || $t === 'mañana' || $t === 'manana') {
        return 'mañana';
    }
    if ($t === '2' || $t === 'tarde') {
        return 'tarde';
    }

    return 'dia';
}

/** Valor persistido en caja.turnocaja (compatible legacy). */
function caja_turno_store_value(string $turnoInput): ?string
{
    $t = strtolower(trim($turnoInput));
    if ($t === 'mañana' || $t === 'manana' || $t === '1') {
        return '1';
    }
    if ($t === 'tarde' || $t === '2') {
        return '2';
    }
    if ($t === '0' || $t === 'dia' || $t === 'día') {
        return '0';
    }
    if ($t === '') {
        return null;
    }

    return $turnoInput;
}

function caja_turno_label(?string $turnocaja): string
{
    $key = caja_turno_cierre_key($turnocaja);
    if ($key === 'mañana') {
        return 'Mañana';
    }
    if ($key === 'tarde') {
        return 'Tarde';
    }
    $raw = trim((string) $turnocaja);
    if ($raw === '0' || $raw === '') {
        return '—';
    }
    if (str_starts_with($raw, 'Orden #') || str_starts_with($raw, 'Contra movimiento')) {
        return $raw;
    }

    return $raw;
}

function caja_turno_cierre_label(string $turnoCierre): string
{
    if ($turnoCierre === 'mañana') {
        return 'Mañana';
    }
    if ($turnoCierre === 'tarde') {
        return 'Tarde';
    }

    return 'Día completo';
}

function caja_turno_default_por_hora(string $hora = ''): string
{
    if ($hora === '') {
        $hora = date('H:i');
    }
    $h = (int) substr($hora, 0, 2);

    return ($h > 0 && $h < 13) ? '1' : '2';
}

/**
 * Condición SQL para filtrar movimientos por turno de cierre.
 *
 * @return array{0:string,1:list<mixed>}
 */
function caja_sql_turno_filter(string $turnoCierre, string $column = 'turnocaja'): array
{
    if ($turnoCierre === 'mañana') {
        return [
            ' AND LOWER(COALESCE(' . $column . ", '')) IN ('1', 'mañana', 'manana')",
            [],
        ];
    }
    if ($turnoCierre === 'tarde') {
        return [
            ' AND LOWER(COALESCE(' . $column . ", '')) IN ('2', 'tarde')",
            [],
        ];
    }

    return ['', []];
}

function caja_turno_esta_cerrado(PDO $pdo, int $idClinica, string $fechaYmd, ?string $turnocaja): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaYmd) || !db_table_exists($pdo, 'caja_cierres')) {
        return false;
    }

    require_once dirname(__DIR__) . '/src/Repositories/CajaCierreRepository.php';
    $repo = new CajaCierreRepository($pdo, $idClinica);
    $turnoKey = caja_turno_cierre_key($turnocaja);
    if ($turnoKey === 'dia') {
        return $repo->findCierre($fechaYmd, 'dia') !== null;
    }

    return $repo->findCierre($fechaYmd, $turnoKey) !== null
        || $repo->findCierre($fechaYmd, 'dia') !== null;
}

function caja_mensaje_turno_cerrado(string $fechaYmd, ?string $turnocaja): string
{
    $turno = caja_turno_cierre_key($turnocaja);
    $label = caja_turno_cierre_label($turno);

    return 'La caja ya está cerrada para ' . $fechaYmd . ' (' . $label . '). Registrá un contra movimiento solo si el administrador lo autoriza.';
}
