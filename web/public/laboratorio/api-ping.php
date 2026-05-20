<?php

declare(strict_types=1);

/**
 * Prueba sin base de datos. Debe devolver JSON.
 * https://tu-dominio/laboratorio/api-ping.php
 */
header('Content-Type: application/json; charset=utf-8');
echo '{"success":true,"data":{"ping":"ok","via":"api-ping.php"},"error":null}';
