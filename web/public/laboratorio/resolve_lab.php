<?php

declare(strict_types=1);

/**
 * Resuelve la ruta a Laboratorio/public/index.php según el layout del servidor.
 *
 * @return array{entry: string|null, tried: list<string>}
 */
function laboratorio_resolve_entry(): array
{
    $bridgeDir = __DIR__;
    $localCfg = $bridgeDir . '/lab_path.local.php';
    if (is_file($localCfg)) {
        $custom = require $localCfg;
        if (is_string($custom) && $custom !== '' && is_file($custom)) {
            return ['entry' => $custom, 'tried' => [$custom . ' (lab_path.local.php)']];
        }
    }

    $docRoot = dirname($bridgeDir);
    $webRoot = dirname($docRoot);
    $projectRoot = dirname($webRoot);

    $moduleNames = ['Laboratorio', 'laboratorio', 'laboratori'];
    $bases = [
        $projectRoot,
        $webRoot . '/..',
        $webRoot,
        $docRoot . '/..',
        dirname($docRoot),
        $docRoot,
        $bridgeDir . '/..',
    ];
    $candidates = [
        $bridgeDir . '/public/index.php',
    ];
    foreach ($bases as $base) {
        foreach ($moduleNames as $name) {
            $candidates[] = rtrim($base, '/\\') . '/' . $name . '/public/index.php';
        }
    }

    $tried = [];
    foreach ($candidates as $path) {
        $norm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $tried[] = $norm;
        if (is_file($norm)) {
            return ['entry' => $norm, 'tried' => $tried];
        }
    }

    return ['entry' => null, 'tried' => $tried];
}
