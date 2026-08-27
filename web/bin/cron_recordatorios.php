<?php

declare(strict_types=1);

/**
 * Cron de recordatorios (reemplazo de Recordatorios.exe).
 *
 * No programar en producción hasta definir proveedor (manual / WAHA / Meta API).
 * Con modo manual solo arma mensajes y enlaces wa.me; no requiere WAHA.
 * Con modo whatsapp_web requiere WAHA activo y whatsapp_web.enabled en config.local.php.
 *
 * Uso:
 *   php bin/cron_recordatorios.php run [--clinica=1]
 *   php bin/cron_recordatorios.php encolar-manana [--clinica=1]
 *   php bin/cron_recordatorios.php procesar [--clinica=1]
 *
 * Switches MySQL (tabla config, por id_clinica):
 *   recordatorios.enabled = 1|0
 *   recordatorios.modo = manual | whatsapp_web | gesis
 *
 * Modo gesis: gesis_whatsapp en config.local.php; prueba con test_only_nro_hc.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Repositories/RecordatorioRepository.php';
require_once dirname(__DIR__) . '/src/Services/RecordatorioService.php';

$accion = $argv[1] ?? 'run';
$idClinica = 1;
foreach (array_slice($argv, 2) as $arg) {
    if (preg_match('/^--clinica=(\d+)$/', $arg, $m)) {
        $idClinica = max(1, (int) $m[1]);
    }
}

$pdo = db();
$svc = new RecordatorioService($pdo, $idClinica);
$repo = new RecordatorioRepository($pdo, $idClinica);

if (!$repo->tableExists()) {
    fwrite(STDERR, "Falta migration_035_agenda_recordatorios.sql\n");
    exit(1);
}

if (!recordatorio_habilitado($pdo, $idClinica)) {
    echo "Recordatorios deshabilitados (config recordatorios.enabled).\n";
    exit(0);
}

switch ($accion) {
    case 'encolar-manana':
        $fecha = date('Y-m-d', strtotime('+1 day'));
        $n = $svc->encolarRecordatoriosDia($fecha);
        echo "Encolados recordatorio para {$fecha}: {$n}\n";
        break;
    case 'procesar':
        $p = $svc->procesarCola(200);
        echo "Procesados: {$p['procesados']}, enviados: {$p['enviados']}, listos: {$p['listos']}, omitidos prueba: {$p['omitidos_prueba']}, errores: {$p['errores']}\n";
        break;
    case 'run':
    default:
        $r = $svc->ejecutarCiclo();
        echo 'Ciclo recordatorios — encolados: ' . $r['encolados_recordatorio']
            . ', procesados: ' . $r['procesados']
            . ', enviados: ' . ($r['enviados'] ?? 0)
            . ', listos: ' . $r['listos']
            . ', omitidos prueba: ' . ($r['omitidos_prueba'] ?? 0)
            . ', errores: ' . $r['errores'] . "\n";
        break;
}

exit(0);
