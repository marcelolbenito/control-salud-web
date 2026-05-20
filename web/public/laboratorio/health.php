<?php

declare(strict_types=1);

/**
 * Diagnóstico cuando Nginx no reenvía /laboratorio/health al index.php del puente.
 * Usar: /laboratorio/health.php?deep=1
 */
$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';
$_SERVER['REQUEST_URI'] = '/laboratorio/health' . $query;

require __DIR__ . '/index.php';
