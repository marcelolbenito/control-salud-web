# Procedimiento: migrar doctores, pacientes y turnos desde `Datos.bak` a producción

Guía repetible para cada vez que tengan un **nuevo backup** del sistema VB6 (SQL Server) y quieran actualizar MySQL de Control Salud Web **sin borrar** lo que ya cargaron en la web.

**Alcance de esta migración**

| Origen (SQL Server / exe) | Tablas legacy en MySQL | Tablas web (PHP) |
|---------------------------|------------------------|------------------|
| `Lista Doctores` | `` `Lista Doctores` `` | `lista_doctores` |
| `Pacientes` | `` `Pacientes` `` | `pacientes` |
| `Agenda Turnos` (solo **Fecha ≥ 2026-01-01**) | `` `Agenda Turnos` `` | `agenda_turnos` |

**Qué NO hace este flujo**

- No reemplaza toda la base (no usa `migration_005`, que hace `TRUNCATE`).
- No importa el `.bak` directo en MySQL (MySQL no lee `.bak`).
- No uses phpMyAdmin para archivos grandes (límites de subida y errores de análisis).

**Script de fusión final (siempre el mismo):** `sql/migration_033_merge_legacy_into_web_sin_truncar.sql`

---

## Resumen en 6 pasos

```text
1. PC: Restaurar Datos.bak en SQL Server (si hace falta)
2. PC: Exportar 3 tablas → datos_legacy_Pacientes_Doctores_Turnos.sql
3. PC: Generar 3 archivos listos para import (doctores, pacientes, turnos≥2026)
4. PC → servidor: scp de los 3 .sql + migration_033
5. Servidor: docker exec mysql < cada archivo (orden fijo)
6. Verificar conteos y probar en el navegador
```

---

## Requisitos previos

### En tu PC (Windows)

- SQL Server con la base **Datos** (restaurada desde `.bak` o la instancia viva del exe).
- Python 3 + dependencias:

```powershell
cd "C:\Control Salud\sql\migracion"
pip install -r requirements.txt
```

- Archivo `sql\migracion\config.env` (copiar de `config.example.env` y ajustar).

### En producción (Gesis2)

- SSH: `ubuntu@ssh.gesis2.com`
- MySQL en Docker, contenedor **`mysqldb`** (confirmar con `docker ps`).
- Credenciales MySQL: las de `config.php` de la web (no las de SSH).

**Ruta real de la app en el servidor** (verificada):

```text
/home/ubuntu/entorno_web/web-php8/clinica/
  config/config.php      ← user/pass MySQL de la web
  public/                ← sitio
  Laboratorio/
```

**Equivalencia PC → servidor**

| En tu PC | En el servidor |
|----------|----------------|
| `web\config\config.php` | `.../clinica/config/config.php` |
| `web\public\` | `.../clinica/public/` |

---

## Fase 1 — SQL Server: restaurar el `.bak` (solo si hace falta)

Si **Control Salud.exe** ya abre y ves datos actuales, podés **saltar** al paso 2 usando esa misma base.

Si solo tenés el archivo `.bak`:

1. Abrí **SSMS** → clic derecho en **Bases de datos** → **Restaurar base de datos…**
2. Origen: dispositivo → elegir `Datos.bak` (ej. `C:\Control Salud\sql\migracion\export\Datos.bak`)
3. Destino: nombre **`Datos`**
4. Pestaña **Opciones** → marcar **Sobrescribir** si ya existía una base `Datos`

Anotá servidor y nombre de base para `config.env`:

```env
SQLSERVER_HOST=.\SQLEXPRESS
SQLSERVER_DATABASE=Datos
```

---

## Atajo: un solo script en PC (fases 2 + 3)

Desde PowerShell:

```powershell
cd "C:\Control Salud\sql\migracion"
.\preparar_legacy_para_produccion.ps1
```

Si **ya tenés** `export\datos_legacy_Pacientes_Doctores_Turnos.sql` y solo querés regenerar doctores/pacientes/turnos:

```powershell
.\preparar_legacy_para_produccion.ps1 -SkipExport
```

Al terminar muestra tamaños de archivos y los comandos `scp` / `docker exec` para copiar y pegar.

---

## Fase 2 — PC: exportar desde SQL Server a MySQL (.sql)

### 2.1 Configurar `config.env`

```env
SQLSERVER_TO_MYSQL_OUT=C:\Control Salud\sql\migracion\export\datos_legacy_Pacientes_Doctores_Turnos.sql
SQLSERVER_INSERT_BATCH_SIZE=200
SQLSERVER_ONLY_TABLE_REGEX=^(Pacientes|Lista Doctores|Agenda Turnos)$
```

Solo esas tres tablas; el dump completo de todas las tablas sería enorme.

### 2.2 Ejecutar export

```powershell
cd "C:\Control Salud\sql\migracion"
python sqlserver_backup_to_mysql_sql.py
```

**Salida esperada:** `sql\migracion\export\datos_legacy_Pacientes_Doctores_Turnos.sql` (~50 MB, varios minutos).

El generador ya convierte saltos de línea dentro de textos a espacio (evita filas cortadas en observaciones).

### 2.3 Comprobar en PC

```powershell
Test-Path "C:\Control Salud\sql\migracion\export\datos_legacy_Pacientes_Doctores_Turnos.sql"
```

---

## Fase 3 — PC: preparar archivos para import (3 tablas)

**No uses** scripts viejos por índices de línea o partes para phpMyAdmin (eliminados del repo).

**No uses** phpMyAdmin para subir turnos.

### 3.1 Doctores y pacientes

```powershell
cd "C:\Control Salud\sql\migracion\export"
python extract_doctores_pacientes_from_dump.py
```

Genera:

- `datos_legacy_doctores.sql` (~1 MB)
- `datos_legacy_pacientes.sql` (~9 MB)

### 3.2 Turnos (solo desde 2026-01-01)

```powershell
python rebuild_turnos_from_dump.py
```

Genera:

- `datos_legacy_turnos_fixed.sql` (~2,3 MB, ~17 300 filas)

Para otro corte de fecha, editá en `rebuild_turnos_from_dump.py`:

```python
FECHA_MIN = "2026-01-01"
```

### 3.3 Checklist rápido antes de subir

```powershell
Get-ChildItem "C:\Control Salud\sql\migracion\export\datos_legacy_doctores.sql",
  "C:\Control Salud\sql\migracion\export\datos_legacy_pacientes.sql",
  "C:\Control Salud\sql\migracion\export\datos_legacy_turnos_fixed.sql" |
  Select-Object Name, @{N='MB';E={[math]::Round($_.Length/1MB,2)}}
```

| Archivo | Tamaño aprox. |
|---------|----------------|
| `datos_legacy_doctores.sql` | ~1 MB |
| `datos_legacy_pacientes.sql` | ~9 MB |
| `datos_legacy_turnos_fixed.sql` | ~2,3 MB |

También necesitás (una vez por migración):

- `C:\Control Salud\sql\migration_033_merge_legacy_into_web_sin_truncar.sql`

---

## Fase 4 — Subir archivos al servidor (scp)

**PowerShell** — una línea por comando (sin `\` al final):

```powershell
scp "C:\Control Salud\sql\migracion\export\datos_legacy_doctores.sql" ubuntu@ssh.gesis2.com:/tmp/
scp "C:\Control Salud\sql\migracion\export\datos_legacy_pacientes.sql" ubuntu@ssh.gesis2.com:/tmp/
scp "C:\Control Salud\sql\migracion\export\datos_legacy_turnos_fixed.sql" ubuntu@ssh.gesis2.com:/tmp/datos_legacy_turnos_fixed.sql
scp "C:\Control Salud\sql\migration_033_merge_legacy_into_web_sin_truncar.sql" ubuntu@ssh.gesis2.com:/tmp/
```

Contraseña: usuario **ubuntu** (SSH), no la de MySQL.

Verificar:

```powershell
ssh ubuntu@ssh.gesis2.com "ls -lh /tmp/datos_legacy_*.sql /tmp/migration_033*.sql"
```

Los nombres deben terminar en **`.sql`**, nunca **`.sql~`**.

---

## Fase 5 — Import en MySQL (servidor)

### 5.1 Entrar y confirmar Docker

```bash
ssh ubuntu@ssh.gesis2.com
docker ps
```

Contenedor MySQL: **`mysqldb`**.

### 5.2 Credenciales MySQL

```bash
grep -A8 "'db'" /home/ubuntu/entorno_web/web-php8/clinica/config/config.php
```

Usá ese `user` y `pass` en los comandos (ejemplo: `marcelo`).

Probar login:

```bash
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud -e "SELECT 1 AS ok;"
```

Si falla **1045**, probá con `root` y la clave de:

```bash
docker inspect mysqldb --format '{{range .Config.Env}}{{println .}}{{end}}' | grep MYSQL
```

### 5.3 Import — orden obligatorio

Sustituí `marcelo` y la clave si son otras. **No cierres la sesión** durante pacientes/turnos (varios minutos sin mensajes).

```bash
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud < /tmp/datos_legacy_doctores.sql
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud < /tmp/datos_legacy_pacientes.sql
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud < /tmp/datos_legacy_turnos_fixed.sql
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud < /tmp/migration_033_merge_legacy_into_web_sin_truncar.sql
```

**Opcional — sesión que no se cuelga:** `screen -S import` antes de los imports; desconectar con `Ctrl+A`, `D`.

### 5.4 Si falla el import de turnos

| Error | Causa | Solución |
|-------|--------|----------|
| `near '(536300...'` + `);,` | Archivo viejo sin regenerar | Volver a Fase 3.2 (`rebuild_turnos_from_dump.py`) y resubir |
| `near '2026-01-07...'` | Saltos de línea en observaciones | Mismo: regenerar con `rebuild_turnos_from_dump.py` |
| `1045 Access denied` | Clave/usuario incorrectos | Usar credenciales de `config.php` o `root` del contenedor |
| Parece colgado | MySQL trabaja en silencio | Esperar o verificar en otra SSH con `SELECT COUNT(*)` |

---

## Fase 6 — Verificación

### 6.1 Conteos en MySQL

```bash
docker exec -i mysqldb mysql -h 127.0.0.1 -u marcelo -p control_salud -e "
SELECT COUNT(*) AS legacy_doctores FROM \`Lista Doctores\`;
SELECT COUNT(*) AS legacy_pacientes FROM \`Pacientes\`;
SELECT COUNT(*) AS legacy_turnos FROM \`Agenda Turnos\`;
SELECT COUNT(*) AS web_doctores FROM lista_doctores;
SELECT COUNT(*) AS web_pacientes FROM pacientes;
SELECT COUNT(*) AS web_turnos FROM agenda_turnos;
"
```

Turnos legacy (filtrados ≥ 2026): ~**17 300** filas.

### 6.2 Navegador

- Control Salud: **Pacientes**, **Doctores**, **Agenda**
- Laboratorio: buscar paciente en **Nuevo pedido**

### 6.3 Limpiar `/tmp` (opcional)

```bash
rm /tmp/datos_legacy_*.sql /tmp/migration_033_merge_legacy_into_web_sin_truncar.sql
```

---

## Mapa de archivos del proyecto

```text
sql/migracion/
  preparar_legacy_para_produccion.ps1  ← atajo: export + partir (fases 2 y 3)
  config.env                          ← conexión SQL Server + MySQL local
  sqlserver_backup_to_mysql_sql.py    ← paso 2: export desde SQL Server
  PROCEDIMIENTO_DUMP_BAK_PRODUCCION.md ← este documento
  export/
    datos_legacy_Pacientes_Doctores_Turnos.sql  ← dump intermedio (no subir a prod si ya partiste)
    extract_doctores_pacientes_from_dump.py
    rebuild_turnos_from_dump.py
    datos_legacy_doctores.sql           ← subir a prod
    datos_legacy_pacientes.sql          ← subir a prod
    datos_legacy_turnos_fixed.sql       ← subir a prod (turnos ≥ 2026)

sql/
  migration_033_merge_legacy_into_web_sin_truncar.sql  ← fusionar a tablas web
```

**No ejecutar en producción:** `migration_005_sync_backup_to_web_tables.sql` (borra y reemplaza todo).

---

## Prueba en local (Docker) antes de producción

Misma secuencia, cambiando solo el destino MySQL:

```powershell
cd "C:\Control Salud"
docker compose up -d mysql

docker cp "sql\migracion\export\datos_legacy_doctores.sql" control-salud-mysql:/tmp/
docker cp "sql\migracion\export\datos_legacy_pacientes.sql" control-salud-mysql:/tmp/
docker cp "sql\migracion\export\datos_legacy_turnos_fixed.sql" control-salud-mysql:/tmp/
docker cp "sql\migration_033_merge_legacy_into_web_sin_truncar.sql" control-salud-mysql:/tmp/

docker exec control-salud-mysql sh -c "mysql -uroot -psalud_root_dev control_salud < /tmp/datos_legacy_doctores.sql"
docker exec control-salud-mysql sh -c "mysql -uroot -psalud_root_dev control_salud < /tmp/datos_legacy_pacientes.sql"
docker exec control-salud-mysql sh -c "mysql -uroot -psalud_root_dev control_salud < /tmp/datos_legacy_turnos_fixed.sql"
docker exec control-salud-mysql sh -c "mysql -uroot -psalud_root_dev control_salud < /tmp/migration_033_merge_legacy_into_web_sin_truncar.sql"
```

(Ajustá la clave `root` según tu `.env` / `docker-compose.yml`.)

---

## Checklist imprimible — día del lanzamiento

```
[ ] Nuevo Datos.bak recibido y restaurado en SQL Server (base Datos)
[ ] config.env apunta a SQL Server correcto
[ ] .\preparar_legacy_para_produccion.ps1  (o los 3 python por separado)
[ ] archivos en export\: doctores, pacientes, turnos_fixed (~17301 turnos)
[ ] scp: doctores, pacientes, turnos_fixed, migration_033 → /tmp/
[ ] ssh: docker ps → mysqldb
[ ] ssh: SELECT 1 con user de config.php
[ ] import: doctores → pacientes → turnos_fixed → migration_033
[ ] verificar COUNT(*) legacy y web
[ ] probar pacientes / agenda / lab en navegador
[ ] borrar /tmp/*.sql (opcional)
```

---

## Errores que ya nos pasaron (no repetir)

1. **Ruta incorrecta en servidor:** no es `.../web/config/`, es `.../clinica/config/`.
2. **`mysql: command not found`:** usar `docker exec -i mysqldb mysql ...`.
3. **Confundir usuario SSH (`ubuntu`) con MySQL (`marcelo`).**
4. **phpMyAdmin / archivos partidos (`part1`, `part2`):** generan SQL inválido para turnos.
5. **Import de turnos sin `rebuild_turnos_from_dump.py`:** falla por `);,` y Enter en observaciones.
7. **Pegar comandos con `.sql~` o `^[[200~`:** escribir una línea por comando.
8. **`migration_005` en prod con datos web nuevos:** borra todo; usar solo **033**.

---

## Cambiar el año de corte de turnos

Editar `sql/migracion/export/rebuild_turnos_from_dump.py`:

```python
FECHA_MIN = "2026-01-01"   # ej. "2027-01-01" para el próximo ciclo
```

Volver a ejecutar Fase 3.2 y repetir Fase 4–5 solo para turnos (+ `migration_033` si querés refrescar la web).

---

*Última actualización: alineado con scripts `extract_doctores_pacientes_from_dump.py`, `rebuild_turnos_from_dump.py` y servidor Gesis2 (`mysqldb`, ruta `entorno_web/web-php8/clinica`).*
