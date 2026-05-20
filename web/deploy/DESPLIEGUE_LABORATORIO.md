# Laboratorio en producción (Nginx)

En **local** Apache reenvía todo `/laboratorio/*` al `index.php` del módulo. En el servidor hay que **pedir ese mismo comportamiento en Nginx** (una sola vez). Después el lab se comporta como en Docker.

## 1. Pedir al hosting (copiar y pegar)

> Necesito agregar en el `server` de **clinica.gesis2.com** un bloque para el módulo Laboratorio.  
> DocumentRoot actual: `/var/www/php8/clinica/public`  
> Módulo en: `/var/www/php8/clinica/Laboratorio/public`  
>  
> Adjunto el archivo `web/deploy/nginx-laboratorio-produccion.conf` (**Opción A**).  
> Debe ir **antes** del `location /` que envía todo a Control Salud.  
> Después: `nginx -t` y recargar Nginx.

Si no pueden usar Opción A, pedir **Opción B** del mismo archivo (puente en `public/laboratorio/`).

## 2. Subir archivos (FTP / panel)

Desde tu PC, en el servidor:

| Origen (repo) | Destino en servidor |
|---------------|---------------------|
| `Laboratorio/` **entera** (con `vendor`, `.env`) | `/var/www/php8/clinica/Laboratorio/` |
| `web/public/laboratorio/` | `/var/www/php8/clinica/public/laboratorio/` |
| `web/public/lab_health.php`, `check_lab_setup.php` | `public/` (diagnóstico; borrar luego) |

En Windows, antes de subir, ejecutá:

```powershell
cd "c:\Control Salud"
.\web\deploy\preparar-lab-servidor.ps1
```

Eso copia CSS/JS al puente (`public/laboratorio/assets/`).

## 3. `Laboratorio/.env` en el servidor

```env
APP_ENV=production
APP_DEBUG=false
APP_BASE_PATH=/laboratorio

# Con Nginx Opción A o B (try_files): false
# Solo true si NO pudieron configurar Nginx (modo degradado ?r=pedidos.nuevo)
LAB_QUERY_ROUTER=false

DB_HOST=mysqldb
DB_PORT=3306
DB_NAME=control_salud
DB_USER=...
DB_PASS=...

LAB_INTEGRATION=control_salud
LAB_CLINICA_ID=1
```

`DB_HOST` debe ser el **mismo** que usa Control Salud Web (no `mysql` salvo que sea Docker).

## 4. Base de datos (una vez)

1. Tablas del lab: `Laboratorio/sql/install/lab_schema_phpmyadmin.sql`
2. Catálogo: `Laboratorio/sql/seeds/001_seed_catalogo.sql`
3. Si buscás pacientes del sistema: `sql/migration_002_pacientes_campos_exe.sql` (columnas `apellido`, `id_cobertura`, etc.)

## 5. Comprobar (en el navegador)

| URL | Resultado esperado |
|-----|-------------------|
| `/check_lab_setup.php` | Checklist en verde |
| `/lab_health.php?deep=1` | JSON `"db":"ok"` |
| `/laboratorio/probe.php` | JSON con `routing: "ok"` |
| `/laboratorio/pedidos/nuevo` | Pantalla nuevo pedido (no login de CS) |
| `/laboratorio/api.php?e=pacientes&accion=buscar&q=a&limite=5` | JSON con pacientes |
| Buscar paciente en la UI | Sin error “Respuesta no JSON” |

Si `/laboratorio/pedidos/nuevo` muestra el **login de Control Salud** o 404, Nginx **aún no** reenvía al lab → reenviar el bloque al hosting o usar modo degradado (`LAB_QUERY_ROUTER=true` y enlaces `/?r=pedidos.nuevo`).

## 6. Cuando todo funcione

Borrar del servidor cuando todo funcione: `check_lab_setup.php`, `lab_health.php`, `laboratorio/probe.php` (opcional).

En el repo solo quedan `check_lab_setup.php` y `lab_health.php` como diagnóstico (no uses `_check_lab_*.php`, eliminados).

## Resumen

| | Local (Docker) | Producción sin Nginx | Producción con Nginx (A o B) |
|--|----------------|----------------------|------------------------------|
| Router | Apache rewrite | Puente + `?r=` + `api.php` | `try_files` → `index.php` |
| URLs | `/laboratorio/api/...` | `api.php?e=...` | `/laboratorio/api/...` |
| Mantenimiento | Bajo | Muchos parches | Bajo (como local) |
