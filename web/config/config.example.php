<?php

declare(strict_types=1);

/**
 * Copiá este archivo como config.local.php y ajustá usuario/clave de MySQL.
 * La base debe existir y tener importado ../sql/schema_mysql.sql
 *
 * Con Docker (mysql del docker-compose en la raíz del proyecto): host 127.0.0.1,
 * user root, pass la de MYSQL_ROOT_PASSWORD del .env (ej. salud_root_dev).
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'control_salud',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Control Salud Web',
        // Dejar vacío para raíz (local). Para subcarpeta usar, por ejemplo: '/controlsalud'
        'base_path' => '',
    ],
    // Modulo Laboratorio embebido en /laboratorio (ver Laboratorio/.env.example)
    'laboratorio' => [
        'enabled' => false,
        'path' => '/laboratorio',
    ],
    /*
     * Recordatorios de turnos (reemplazo web de Recordatorios.exe).
     * La cola y la bandeja /recordatorios.php funcionan sin WAHA.
     *
     * --- Estado por defecto (recomendado hasta definir proveedor) ---
     * - whatsapp_web.enabled = false
     * - En MySQL: recordatorios.modo = 'manual' (enlace wa.me; envía secretaría)
     * - No levantar perfil Docker waha ni programar cron_recordatorios.php
     *
     * --- Activar envío automático WAHA (Core gratis = 1 número; Plus = varios) ---
     * 1. docker compose --profile waha up -d
     * 2. Vincular: /waha-vincular.php (o panel :3000)
     * 3. whatsapp_web.enabled = true y base_url (host: http://127.0.0.1:3000 | Docker webapp: http://waha:3000)
     * 4. Por clínica: UPDATE config SET valor='whatsapp_web' WHERE clave='recordatorios.modo' AND id_clinica=N;
     * 5. Cron cada 5-15 min: php web/bin/cron_recordatorios.php run [--clinica=N]
     *
     * --- Apagar WAHA sin perder código ---
     * - whatsapp_web.enabled = false
     * - UPDATE config SET valor='manual' WHERE clave='recordatorios.modo';
     * - docker stop control-salud-waha
     * - Opcional: recordatorios.enabled = '0' desactiva toda la cola
     *
     * --- Otra opción futura (Meta API, SMS, etc.) ---
     * Mantener cola agenda_recordatorios; nuevo proveedor en src/Integration/WhatsApp/
     */
    'whatsapp_web' => [
        'enabled' => false,
        'base_url' => 'http://127.0.0.1:3000',
        'api_key' => 'dev_waha_local_key',
        'session' => 'default',
    ],
    /*
     * WhatsApp vía gesis-services (mismo JWT que Facturación Electrónica).
     * Vincular número: /gesis-vincular.php (QR en vivo, polling cada 3 s).
     * Ver WHATSAPP_INTEGRATION.md en la raíz del proyecto.
     */
    'gesis_whatsapp' => [
        'base_url' => 'https://servicios.gesis2.com',
        'email' => '',
        'password' => '',
        // Solo si operás con cuenta admin sobre otro negocio:
        'custom_cuit' => '',
        // Pruebas: solo envía por API al HC indicado (0 = todos). Ej. 16059 = Gastón DONDA.
        'test_only_nro_hc' => 0,
    ],
];
