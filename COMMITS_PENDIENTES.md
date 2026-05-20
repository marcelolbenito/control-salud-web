# Commits pendientes — plan sugerido

Estado al **2026-05-19** (rama `main`, último commit: `ae6b18f` — usuario médico en doctores).

Objetivo: ordenar lo que hay en el working tree **sin** subir secretos, dumps ni `DATA/`.

---

## Antes de commitear (obligatorio)

- [ ] `config.env` **no** debe ir al repo (ya está en `.gitignore`; si seguía trackeado: `git rm --cached sql/migracion/config.env`).
- [ ] No agregar `control_salud*.sql`, `control_salud_datos/`, `DATA/`, `sql/migracion/export/*.sql`.
- [ ] Revisar `web/config/config.example.php` — solo valores de ejemplo, sin claves de producción.

---

## Commit 1 — Higiene del repositorio

**Archivos:**

- `.gitignore` (ampliado: DATA, dumps raíz, export, config.env)
- `.gitattributes`, `.editorconfig` (si querés normalizar LF)

**Mensaje sugerido:**

```
chore: ignora dumps locales, DATA y config.env con secretos
```

**Comandos:**

```powershell
git add .gitignore .gitattributes .editorconfig
git rm --cached sql/migracion/config.env   # si aún figura trackeado
git commit -m "chore: ignora dumps locales, DATA y config.env con secretos"
```

---

## Commit 2 — Migración legacy (SQL Server → MySQL web)

**Archivos:**

- `sql/migration_033_merge_legacy_into_web_sin_truncar.sql`
- `sql/migracion/PROCEDIMIENTO_DUMP_BAK_PRODUCCION.md`
- `sql/migracion/preparar_legacy_para_produccion.ps1`
- `sql/migracion/exportar_dump.ps1`
- `sql/migracion/export/README.md`
- `sql/migracion/export/extract_doctores_pacientes_from_dump.py`
- `sql/migracion/export/rebuild_turnos_from_dump.py`
- `sql/migracion/export/.gitkeep`
- `sql/migracion/README.md`
- `sql/migracion/config.example.env`
- `sql/migracion/sqlserver_backup_to_mysql_sql.py` (escape de saltos de línea en strings)

**No incluir:** `sql/migracion/config.env`, ningún `*.sql` en `export/`.

**Mensaje sugerido:**

```
feat(sql): migración legacy pacientes/doctores/turnos sin truncar (033)
```

---

## Commit 3 — Docker + menú web (soporte Laboratorio)

**Archivos:**

- `docker-compose.yml`
- `docker/web/Dockerfile`
- `docker/web/apache-vhost.conf`
- `web/includes/layout.php` (entrada menú Laboratorio)
- `web/config/config.example.php` (`laboratorio.enabled`)
- `scripts/install-laboratorio.ps1`

**Mensaje sugerido:**

```
feat(docker): integra módulo Laboratorio en Apache local
```

---

## Commit 4 — Módulo Laboratorio (código)

**Archivos:** carpeta `Laboratorio/` completa **excepto:**

- `vendor/` (generar en servidor/CI con `composer install`)
- `.env` (solo `.env.example` si existe)
- `storage/logs/*`, archivos generados

**Mensaje sugerido:**

```
feat(laboratorio): módulo LIS embebido (pedidos, resultados, aranceles)
```

**Nota:** Si `vendor/` no está en repo, documentar en `Laboratorio/README.md` que hay que correr `composer install` al desplegar.

---

## Commit 5 — Puente web + despliegue

**Archivos:**

- `web/public/laboratorio/` (index, api.php, assets, health, probe, .htaccess)
- `web/public/lab_health.php`
- `web/public/check_lab_setup.php`
- `web/deploy/` (`DESPLIEGUE_LABORATORIO.md`, `preparar-lab-servidor.ps1`, `nginx-laboratorio-produccion.conf`, etc.)

**Mensaje sugerido:**

```
feat(web): puente Nginx/Apache y guía de despliegue del laboratorio
```

---

## Commit 6 — Documentación (opcional)

**Archivos:**

- `DEV_INTEGRATION.md`
- `AUDITORIA_CALIDAD.md`

**Mensaje sugerido:**

```
docs: integración laboratorio y notas de auditoría
```

---

## Qué NO commitear (queda solo en disco / .gitignore)

| Ruta | Motivo |
|------|--------|
| `DATA/` | Binarios SQL Server |
| `control_salud_datos/`, `control_salud*.sql` | Dumps phpMyAdmin / export viejos |
| `sql/migracion/export/*.sql` | Generados por `preparar_legacy_para_produccion.ps1` |
| `sql/migracion/config.env` | Contraseñas reales |
| `Laboratorio/.env` | Producción local |
| `Logo Centro Privado Salud.zip` | Asset suelto |

---

## Orden recomendado

```text
1 → 2 → 3 → 4 → 5 → 6
```

Los commits **4 y 5** son los más grandes; conviene probar en local entre 3 y 4 (`docker compose up`, `/laboratorio/`, `lab_health.php`).

---

## Sincronizar con remoto

Cuando los commits estén listos:

```powershell
git push origin main
```

(Solo si querés publicar; revisá que no entre nada ignorado con `git status` limpio.)

---

## Verificación rápida pre-commit

```powershell
git status
git diff --cached --name-only
```

Ningún nombre de la tabla “Qué NO commitear” debería aparecer en `--cached`.
