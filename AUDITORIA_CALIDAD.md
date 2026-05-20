# Auditoría de calidad — Control Salud Web

> Documento para entregar al desarrollador. Cubre seguridad, performance, arquitectura y mantenibilidad. Fecha: 2026-04-27.

## Contexto

App PHP 8 + MySQL, layered (`public/` → `Controllers/` → `Repositories/` → `Views/`). Sin framework, sin autoload PSR-4, sin tests. Stack maduro funcionalmente; el riesgo está en **seguridad de producción** y **deuda de mantenibilidad**, no en la arquitectura base (que es coherente).

**Veredicto general:** arquitectura correcta y consistente, pero con problemas de seguridad bloqueantes para producción y un puñado de god objects que ya cuesta mantener. No requiere reescritura — sí remediación priorizada.

---

## Hallazgos por área

### 1. Seguridad (bloqueante para prod)

#### Críticos
- **`web/public/setup.php`** — endpoint sin `require_auth()` que crea superadmin si la tabla `usuarios` está vacía. Race condition + accesible públicamente.
  **Fix:** gatear con env var `APP_SETUP_ENABLED` y devolver 404 cuando esté off; o eliminar tras instalación inicial.
- **`web/config/config.local.php` adentro del docroot.** Si Apache/Nginx apuntan mal o se cambia docroot, las credenciales quedan servibles.
  **Fix:** mover a `/var/www/config/` fuera de `public/` o, si el docroot ya es `web/public/`, agregar igual `<Files "*.php"> Deny </Files>` en `web/config/.htaccess` para defensa en profundidad.
- **`web/public/odontograma_superficies_api.php`** — POST JSON muta BD **sin `csrf_verify()`**. SameSite=Strict atenúa pero no elimina.
  **Fix:** leer token de header `X-CSRF-Token` o del body JSON y validar con `hash_equals`.
- **Adjuntos HC + foto paciente bajo `web/public/uploads/`** — servidos directamente por el web server, sin auth. URLs con `bin2hex(4)` = 32 bits son **adivinables a escala** y exponen PHI.
  **Fix:** mover `uploads/` fuera de `public/`, servir vía `download.php?id_adjunto=...` con auth + scoping `id_clinica`.

#### Altos
- **Login `web/public/login.php` sin rate limiting / lockout.**
  **Fix:** contador por IP+usuario en tabla `login_attempts` con backoff exponencial.
- **Validación de uploads sólo por `finfo` MIME**, sin doble check de extensión vs MIME mapeado. Riesgo bajo dado destino servido como estático, pero combinado con el item de uploads PHI eleva.
  **Fix:** validar extensión derivada del MIME mapeado (`$map[$mime]`) y forzar `Content-Disposition: attachment` al servir.

#### Medios
- **ACL en `bootstrap.php:80-105` por path exacto:** cualquier endpoint nuevo queda accesible a `superadmin`/`admin_clinica` por default (`'*'`), pero invisible a `doctor`. Riesgo: agregás un endpoint sensible y no lo restringe automáticamente.
  **Fix:** invertir a allowlist por rol explícita, default deny.
- **`auth_user_role()` cachea rol en sesión al login** y no se refresca si cambia en BD hasta logout/timeout (30 min). Aceptable salvo eliminación de doctor.
  **Fix opcional:** revalidar rol cada N minutos contra BD.

---

### 2. Performance

**Schema introspection en cada request es el costo dominante.** Una page load de `/pacientes.php` ejecuta ~6–8 queries a `information_schema` (cache es per-request, no cross-request). En `/ordenes.php` son 8+. Para una clínica con tráfico moderado, es un problema real.

- **`web/includes/db_schema.php:5,23`** — caché `static $cache` muere al final del request.
  **Fix prioritario:** mover caché a APCu (clave: `dsn + table + column`), invalidar manualmente al correr migraciones (`bin/clear-schema-cache.php`).
- **`web/src/Repositories/OrdenesRepository.php:104-119`** — 8 `db_table_has_column` en un solo método.
  **Fix:** batch en `__construct`, asignar a `$this->hasApellido`, etc.
- **`web/src/Repositories/PacientesRepository.php:392`** — `SHOW COLUMNS` cacheado por `spl_object_hash($pdo)`, frágil.
  **Fix:** APCu con clave por DSN.
- **AJAX endpoints sin `session_write_close()`**: `agenda_slots.php`, `pacientes_lookup.php`, `odontograma_superficies_api.php`. Bloquean concurrent requests del mismo usuario (PHP file sessions = lock por sesión).
  **Fix:** `session_write_close()` después de leer `$_SESSION`.
- **Listados sin paginación expuesta** — `pacientes` LIMIT 500, `ordenes` LIMIT 500, `sesiones` LIMIT 1000 hardcoded. A 20k+ filas se vuelve costoso y no hay UI para ir más allá.
  **Fix:** parámetro `?page=N&size=50` y total con `COUNT(*)` separado.
- **Índices faltantes** que importan a escala:
  - `agenda_turnos`: agregar `(id_clinica, Fecha, Doctor)` — ya tiene `(id_clinica, Fecha)` solo.
  - `pacientes_hc_notas`: confirmar `(id_paciente, fecha_hora DESC)` en migration_026.
  - `pacientes_hc_adjuntos`: confirmar `id_nota_hc` indexed para los `IN (...)` batch.

---

### 3. Arquitectura y legibilidad

#### Capas
Correctas en general. **Excepciones a corregir:**
- **`web/public/index.php:32-77`** — SQL directo en el dashboard (SELECT COUNTs y agrupaciones). Viola la regla "sin SQL en `public/`".
  **Fix:** `DashboardRepository::summary()`.
- **`web/public/login.php:29-32`** — SQL directo de auth.
  **Fix:** mover a `AuthRepository::findUserByCredentials()`.
- Vistas no tienen SQL — bien.

#### God objects
- **`TurnosRepository.php`** (~796 líneas) — agenda + slots + búsqueda + state.
  **Fix:** extraer `TurnoStateManager` (anular/confirmar) y `TurnoSlotsService`.
- **`PacientesController.php`** (722 líneas) — index + HC + form + delete + upload + RTF→text.
  **Fix:** sacar `HistoriaClinicaController`, `PacienteFotoService`, y mover `legacyHcToDisplayText` a un util `LegacyRtf`.
- **`PacientesRepository.php`** (600) — base + extended + HC notas + HC adjuntos.
  **Fix:** extraer `PacienteHistoriaClinicaRepository`.
- **`OrdenesRepository.php`** (550) — CRUD + recetas + adjuntos + factura.
  **Fix:** ídem (separar por subdominio).

#### Duplicación recurrente que vale extraer
- 15+ instancias de `if ($this->pacientesTieneClinica()) { $sql .= ' AND id_clinica = ?'; $params[] = $this->idClinica; }` → helper `applyClinicaScope(string &$sql, array &$params, string $alias = '')`.
- Quote escaping `str_replace('` `', '', $c)` repetido 5+ veces → `Sql::quoteIdent($name)`.
- `collectExtendedPacientePayloadFromPost()` 77 campos → `PacientePayloadMapper::fromPost()` con DTO.

#### Manejo de errores — punto débil real
10+ `catch (Throwable $e) { return []; }` en:
- `OdontogramaRepository.php:99, 130`
- `OrdenesRepository.php:371, 395, 409, 427`
- `PacientesRepository.php:201, 283`
- `DoctoresRepository.php:192`

Esconden bugs de schema drift y de queries rotas — el síntoma usuario es "lista vacía, sin razón aparente".
**Fix mínimo:** `error_log($e->getMessage())` antes del return; idealmente Result type o re-throw en dev.

#### Tests
**Cero.** Ningún `tests/`, ni `phpunit.xml`, ni `composer.json`. Para cualquier refactor de los items anteriores es un piso peligroso.
**Fix:** sumar `composer.json` con phpunit + 5 tests de humo (login, alta paciente, alta turno, búsqueda, alta orden) antes de tocar god objects.

#### Otros
- Sin autoload PSR-4 — cada archivo arrastra `require_once`. Aceptable; ya documentado como pendiente. Costo bajo de migrar (Composer + `psr-4` map de `App\\` → `web/src/`).
- ACL hardcodeada en `bootstrap.php` — funciona, pero impide multi-clínica con permisos finos sin refactor.
- Naming legacy `NroHC`, `Nombres`, `HC` mezclado con snake_case — deliberado para paridad con exe; documentado y tolerado.

---

## Plan de remediación recomendado (orden sugerido)

| Prioridad | Acción | Costo | Impacto |
|-----------|--------|-------|---------|
| **P0** | Mover `uploads/` fuera de `public/` + endpoint `download.php` con auth | M | Cierra exposición PHI |
| **P0** | CSRF en `odontograma_superficies_api.php` | S | Cierra mutación CSRF |
| **P0** | Gatear/eliminar `setup.php` en prod | XS | Cierra creación admin |
| **P0** | `.htaccess` deny en `web/config/` + revisar docroot | XS | Defensa en profundidad credenciales |
| **P1** | Schema cache APCu + invalidación manual | S | Quita 6–8 queries info_schema/request |
| **P1** | `session_write_close()` en endpoints AJAX | XS | Desbloquea concurrent requests |
| **P1** | Rate limit login | S | Cierra brute force |
| **P1** | `composer.json` + phpunit + 5 tests humo | M | Habilita refactor seguro |
| **P2** | Helper `applyClinicaScope` + reemplazar 15+ sitios | S | Legibilidad, menos bugs por olvido |
| **P2** | Logging en `catch(Throwable)` silenciosos | S | Cierra dolor de debugging |
| **P2** | Extraer `HistoriaClinicaController` + `PacienteFotoService` | M | Baja PacientesController de 722 a ~300 |
| **P2** | Paginación expuesta en listados (pacientes/ordenes/sesiones) | M | Performance + UX a escala |
| **P3** | Extraer `TurnoStateManager` + `TurnoSlotsService` | L | Mantenibilidad agenda |
| **P3** | `DashboardRepository` (sacar SQL de `index.php`) | S | Cumple regla de capas |
| **P3** | Autoload PSR-4 | S | Quita 200+ `require_once` |
| **P3** | Índices `(id_clinica, Fecha, Doctor)` + review FKs | XS | Performance a escala |

**Costos:** XS = ≤30 min, S = ≤2 h, M = ≤1 d, L = 2–3 d.

---

## Archivos críticos a tocar (referencia rápida)

- **Seguridad uploads:** `web/src/Controllers/PacientesController.php:319-403, 617-670`, `web/public/uploads/*`
- **CSRF API:** `web/public/odontograma_superficies_api.php`
- **Setup:** `web/public/setup.php`
- **Schema cache:** `web/includes/db_schema.php`, `web/src/Repositories/PacientesRepository.php:382-403`
- **Sessions AJAX:** `web/public/agenda_slots.php`, `web/public/pacientes_lookup.php`, `web/public/odontograma_superficies_api.php`
- **ACL/login rate limit:** `web/includes/bootstrap.php:75-109`, `web/public/login.php`
- **God objects:** `web/src/Controllers/PacientesController.php`, `web/src/Repositories/TurnosRepository.php`, `web/src/Repositories/OrdenesRepository.php`
- **SQL en public:** `web/public/index.php:32-77`, `web/public/login.php:29-32`
- **Excepciones tragadas:** `web/src/Repositories/OdontogramaRepository.php:99,130`, `OrdenesRepository.php:371,395,409,427`, `PacientesRepository.php:201,283`, `DoctoresRepository.php:192`

---

## Verificación end-to-end (cuando se ejecute)

1. Por cada P0/P1 cerrado: `docker compose exec webapp php -l <archivo>` + flujo manual.
2. Tras schema cache: medir queries con `SET GLOBAL general_log=1` antes/después en `/pacientes.php` y `/ordenes.php` — esperado caer de ~7 a 1.
3. Tras P1 tests humo: `docker compose exec webapp vendor/bin/phpunit tests/`.
4. Smoke manual mínimo: login, alta paciente con foto, agendar turno, crear orden, agregar nota HC con adjunto, eliminar paciente. Verificar que adjuntos HC ya no son accesibles sin login.

---

## Lo que NO está roto (para no tocar)

- Estructura de capas `public → Controllers → Repositories → Views` — coherente y aplicada.
- PDO prepared statements — todo el SQL parametrizado, sin concatenación de input usuario.
- CSRF — bien implementado para forms HTML (sólo falta el endpoint JSON).
- Headers de seguridad en `bootstrap.php` (X-Frame-Options, CSP-lite, SameSite=Strict, session timeout) — sólidos.
- Multi-clínica — el patrón `id_clinica` está consistentemente aplicado en repos.
- Compatibilidad con schema drift (`db_table_has_column`) — útil dada la convivencia con base legacy; el problema es el **costo**, no el patrón.
- Naming legacy — la decisión de mantener `NroHC`/`Nombres` es correcta y está justificada por paridad con el `.exe`.
