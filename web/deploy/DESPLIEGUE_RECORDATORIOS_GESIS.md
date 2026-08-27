# Despliegue producción — Recordatorios WhatsApp (Gesis) + Caja v2

Servidor: **clinica.gesis2.com** (SSH: `ubuntu@ssh.gesis2.com`)  
Ruta típica en servidor: `/var/www/php8/clinica/`

> **No subir** `config.local.php` desde git (crearlo en el servidor).  
> **No subir** `web/bin/gesis_test_*.php`, `gesis_refresh_qr.php`, `gesis_probe_*.php` (solo dev).  
> **No subir** `web/public/gesis-qr.png` (QR temporal de prueba).

---

## 1. Migraciones MySQL (ejecutar en `control_salud` prod)

### 1.1 Caja v2 (opcional si aún no está)

Subir y ejecutar: `sql/migration_034_caja_modopago.sql`

**Si phpMyAdmin marca muchos errores** (común con `PREPARE`/`EXECUTE` de la versión vieja):

1. Seleccioná la base `control_salud` antes de importar (`USE control_salud;`).
2. Ejecutá el archivo **completo** (versión actual usa procedimiento almacenado, más estable).
3. Si sigue fallando → `sql/migration_034_caja_modopago_manual.sql` (pasos uno por uno).
4. Verificar: `SHOW COLUMNS FROM caja LIKE 'modopago';` — si ya existe, solo hace falta el `UPDATE` del paso 4 del manual.

### 1.2 Recordatorios (obligatorio)

Subir y ejecutar: `sql/migration_035_agenda_recordatorios.sql`

### 1.3 Configuración post-migración

Reemplazar `30XXXXXXXXX` por el CUIT real (igual que en servicios.gesis2.com):

```sql
USE control_salud;

-- Modo Gesis (envío automático por API)
INSERT INTO config (id_clinica, clave, valor) VALUES (1, 'recordatorios.modo', 'gesis')
ON DUPLICATE KEY UPDATE valor = 'gesis';

INSERT INTO config (id_clinica, clave, valor) VALUES (1, 'recordatorios.enabled', '1')
ON DUPLICATE KEY UPDATE valor = '1';

INSERT INTO config (id_clinica, clave, valor) VALUES (1, 'recordatorios.auto_confirmar', '1')
ON DUPLICATE KEY UPDATE valor = '1';

INSERT INTO config (id_clinica, clave, valor) VALUES (1, 'recordatorios.sucursal_plantillas', '1')
ON DUPLICATE KEY UPDATE valor = '1';

INSERT INTO config (id_clinica, clave, valor) VALUES (1, 'clinica.cuit', '30XXXXXXXXX')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- Verificar
SELECT clave, valor FROM config
WHERE id_clinica = 1
  AND clave IN (
    'recordatorios.modo', 'recordatorios.enabled', 'recordatorios.auto_confirmar',
    'recordatorios.sucursal_plantillas', 'recordatorios.hora_recordatorio', 'clinica.cuit'
  )
ORDER BY clave;
```

---

## 2. `config.local.php` en el servidor

Archivo: `/var/www/php8/clinica/config/config.local.php`  
(Partir del que ya existe en prod y **agregar** o actualizar `gesis_whatsapp`. No usar `web/config/` — en el servidor no hay prefijo `web/`.)

```php
'gesis_whatsapp' => [
    'base_url' => 'https://servicios.gesis2.com',
    'email' => 'centrosalud@gmail.com',   // usuario integración
    'password' => '********',             // NO commitear
    'custom_cuit' => '',                  // solo si cuenta admin opera otro negocio
  // Producción: 0 = todos los pacientes. En pruebas locales usamos 16059 (Gastón DONDA).
    'test_only_nro_hc' => 0,
],
```

Mantener `whatsapp_web.enabled => false` (WAHA no se usa en prod).

---

## 3. Archivos a subir por FTP / SFTP

Raíz en servidor: `/var/www/php8/clinica/`

### 3.1 SQL (para ejecutar en MySQL, no van al docroot)

| Origen (PC) | Destino servidor |
|-------------|------------------|
| `sql/migration_034_caja_modopago.sql` | `/tmp/` o carpeta sql |
| `sql/migration_035_agenda_recordatorios.sql` | `/tmp/` o carpeta sql |

### 3.2 Recordatorios + Gesis (web)

| Origen | Destino |
|--------|---------|
| `web/public/recordatorios.php` | `public/` |
| `web/public/recordatorios_plantillas.php` | `public/` |
| `web/public/gesis-vincular.php` | `public/` |
| `web/public/gesis-qr-live.php` | `public/` |
| `web/public/gesis-status.php` | `public/` |
| `web/public/recordatorios_webhook.php` | `public/` (WAHA legacy; opcional) |
| `web/bin/cron_recordatorios.php` | `bin/` |
| `web/includes/gesis_whatsapp_helpers.php` | `includes/` |
| `web/includes/recordatorio_helpers.php` | `includes/` |
| `web/includes/layout.php` | `includes/` |
| `web/includes/bootstrap.php` | `includes/` |
| `web/includes/flash.php` | `includes/` |
| `web/config/config.example.php` | `config/` (referencia; no reemplaza local) |
| `web/src/Services/RecordatorioService.php` | `src/Services/` |
| `web/src/Services/RecordatorioWebhookService.php` | `src/Services/` |
| `web/src/Repositories/RecordatorioRepository.php` | `src/Repositories/` |
| `web/src/Controllers/RecordatoriosController.php` | `src/Controllers/` |
| `web/src/Controllers/RecordatorioPlantillasController.php` | `src/Controllers/` |
| `web/src/Controllers/TurnosController.php` | `src/Controllers/` |
| `web/src/Controllers/AgendaWebController.php` | `src/Controllers/` |
| `web/src/Integration/WhatsApp/GesisWhatsAppProvider.php` | `src/Integration/WhatsApp/` |
| `web/src/Integration/WhatsApp/WhatsAppWebProvider.php` | `src/Integration/WhatsApp/` |
| `web/src/Views/recordatorios/index.php` | `src/Views/recordatorios/` |
| `web/src/Views/recordatorios/plantillas.php` | `src/Views/recordatorios/` |

### 3.3 Caja v2 (si desplegás migration 034)

| Origen | Destino |
|--------|---------|
| `web/includes/caja_helpers.php` | `includes/` |
| `web/src/Repositories/CajaRepository.php` | `src/Repositories/` |
| `web/src/Repositories/CajaCierreRepository.php` | `src/Repositories/` |
| `web/src/Controllers/CajaController.php` | `src/Controllers/` |
| `web/src/Controllers/CajaCierreController.php` | `src/Controllers/` |
| `web/src/Controllers/PagosController.php` | `src/Controllers/` |
| `web/src/Views/caja/*.php` | `src/Views/caja/` |

---

## 4. Cron automático (SSH, sin cPanel)

```bash
ssh ubuntu@ssh.gesis2.com

mkdir -p /var/www/php8/clinica/logs

# Prueba manual (debe imprimir encolados/enviados sin error fatal)
cd /var/www/php8/clinica
php bin/cron_recordatorios.php run

# Programar cada 10 minutos
crontab -e
```

Línea para `crontab`:

```cron
*/10 * * * * cd /var/www/php8/clinica && /usr/bin/php bin/cron_recordatorios.php run >> /var/www/php8/clinica/logs/cron_recordatorios.log 2>&1
```

Verificar:

```bash
crontab -l
tail -20 /var/www/php8/clinica/logs/cron_recordatorios.log
```

---

## 5. Pasos en la web (después de subir)

1. Login admin → **https://clinica.gesis2.com/gesis-vincular.php**  
   Vincular número real de la clínica (estado **connected**).

2. **https://clinica.gesis2.com/recordatorios_plantillas.php**  
   Revisar textos `mensajerecordatorio` / `mensajerecordatorio2`.

3. **https://clinica.gesis2.com/recordatorios.php**  
   - Modo envío: `gesis`  
   - Gesis sesión: Conectada  
   - **Enviar mensaje de prueba** (solo si `test_only_nro_hc > 0`)  
   - O crear turno de prueba con celular → confirmación en bandeja

4. Tras validar: `test_only_nro_hc => 0` en `config.local.php` prod.

---

## 6. Checklist post-despliegue

- [ ] Migration 035 aplicada (`SHOW TABLES LIKE 'agenda_recordatorios'`)
- [ ] `recordatorios.modo = gesis`
- [ ] `gesis_whatsapp` en config.local.php prod
- [ ] CUIT coincide con negocio Gesis
- [ ] WhatsApp **connected** en gesis-vincular
- [ ] Mensaje de prueba o turno de prueba recibido en celular
- [ ] Cron en crontab y log sin errores PHP
- [ ] `test_only_nro_hc = 0` cuando salgan de prueba controlada

---

## 7. Segunda etapa (webhook SI/NO, anulación, datos clínica)

### 7.1 SQL

```bash
mysql ... < sql/migration_036_recordatorios_etapa2.sql
```

### 7.2 Archivos a subir

| Origen local | Destino servidor |
|--------------|------------------|
| `web/public/gesis_webhook.php` | `public/` |
| `web/includes/gesis_whatsapp_helpers.php` | `includes/` |
| `web/includes/recordatorio_helpers.php` | `includes/` |
| `web/src/Services/RecordatorioWebhookService.php` | `src/Services/` |
| `web/src/Services/RecordatorioService.php` | `src/Services/` |
| `web/src/Repositories/RecordatorioRepository.php` | `src/Repositories/` |
| `web/public/agenda_turno_anular.php` | `public/` |
| `web/src/Controllers/TurnosController.php` | `src/Controllers/` |
| `web/src/Controllers/RecordatorioPlantillasController.php` | `src/Controllers/` |
| `web/src/Views/recordatorios/plantillas.php` | `src/Views/recordatorios/` |
| `web/public/gesis-vincular.php` | `public/` |
| `web/bin/gesis_registrar_webhook.php` | `bin/` |

### 7.3 `config/gesis.local.php` — agregar

```php
'webhook_url' => 'https://clinica.gesis2.com/gesis_webhook.php',
'webhook_secret' => '...mínimo 32 caracteres aleatorios...',
```

Generar secret (ej.): `openssl rand -hex 24`

### 7.4 Registrar webhook en Gesis

Opción A — pantalla: **/gesis-vincular.php** → botón **Registrar webhook en Gesis** (con WhatsApp conectado).

Opción B — SSH:

```bash
cd /var/www/php8/clinica && php bin/gesis_registrar_webhook.php
```

### 7.5 Verificación

1. Paciente responde **SI** al mensaje de confirmación → bandeja **Confirmado**, turno `confirmado=1`.
2. Paciente responde **NO** → bandeja **Cancelado**, turno `estado=cancelado`.
3. Anular turno desde agenda → mensaje `mensajeanular` en bandeja / enviado.
4. **Plantillas** → editar nombre/dirección clínica y texto de anulación.

### 7.6 Pendiente futuro (no incluido)

| Ítem | Notas |
|------|--------|
| Limpieza WAHA local | Docker profile, `waha-vincular.php` |
| Cron multiclínica | Iterar `--clinica=N` |

---

## 8. Rollback rápido (apagar envío automático)

```sql
UPDATE config SET valor = 'manual' WHERE clave = 'recordatorios.modo' AND id_clinica = 1;
```

Opcional: comentar la línea en `crontab`. La bandeja sigue funcionando con enlaces wa.me manuales.
