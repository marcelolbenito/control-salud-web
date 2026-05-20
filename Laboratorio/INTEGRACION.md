# INTEGRACION.md

Guía única para el dev del sistema clínico principal.
Si caíste acá por primera vez: **leelo entero antes de tocar nada**. Cubre
contrato de tablas, contrato de sesión, opciones de embebido e instalación
end-to-end. No hace falta abrir ningún otro archivo del repo.

---

## TL;DR

```bash
# 1. Bajar el código
git clone https://github.com/Dondita1/laboratorio.git
cd laboratorio
composer install
cp .env.example .env   # editar con credenciales de tu BD

# 2. Aplicar el esquema (crea 18 tablas con prefijo lab_)
mysql -u <usuario> -p <tu_bd> < sql/install/lab_schema.sql

# 3. Levantar en local
php -S localhost:8000 -t public public/index.php
```

Antes de embeber el módulo en tu sistema, tu código tiene que setear
`$_SESSION['usuario_id']` con el id del usuario logueado.

---

## Stack y requisitos

- PHP 8.2+
- Composer 2.x
- MySQL 8 o MariaDB 10.6+ (la **misma** BD que tu sistema)
- Extensiones PHP: `pdo_mysql`, `mbstring`, `json`, `dom`, `gd`
- Charset de la BD: `utf8mb4_unicode_ci`. Motor: `InnoDB`.

---

## Tablas compartidas — contrato

El módulo asume que tu sistema ya creó y mantiene las siguientes tablas.
Las **lee** en runtime; nunca las modifica.

### `pacientes`

| columna | tipo | nulable | notas |
|---|---|---|---|
| `id` | BIGINT UNSIGNED PK AUTO_INCREMENT | no | |
| `nro_hc` | VARCHAR(20) UNIQUE | no | número de historia clínica |
| `dni` | VARCHAR(20) | no | índice |
| `apellido` | VARCHAR(100) | no | índice |
| `nombres` | VARCHAR(100) | no | |
| `telefono` | VARCHAR(30) | sí | |
| `fecha_nacimiento` | DATE | sí | |
| `sexo` | ENUM('M','F','X') | no | |
| `obra_social_id` | BIGINT UNSIGNED | sí | FK → `obras_sociales.id` |
| `nro_afiliado` | VARCHAR(50) | sí | |
| `created_at` | DATETIME DEFAULT CURRENT_TIMESTAMP | no | |
| `updated_at` | DATETIME ON UPDATE CURRENT_TIMESTAMP | no | |
| `deleted_at` | DATETIME | sí | soft delete |

### `obras_sociales`

| columna | tipo | notas |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `nombre` | VARCHAR(150) UNIQUE | |
| `activo` | TINYINT(1) DEFAULT 1 | |
| `created_at`, `updated_at` | DATETIME | |

### `medicos`

| columna | tipo | nulable | notas |
|---|---|---|---|
| `id` | BIGINT UNSIGNED PK | no | |
| `apellido` | VARCHAR(100) | no | índice |
| `nombres` | VARCHAR(100) | no | |
| `matricula` | VARCHAR(40) UNIQUE | no | |
| `especialidad` | VARCHAR(100) | sí | |
| `telefono` | VARCHAR(30) | sí | |
| `email` | VARCHAR(150) | sí | |
| `activo` | TINYINT(1) DEFAULT 1 | no | |
| `created_at`, `updated_at` | DATETIME | no | |
| `deleted_at` | DATETIME | sí | |

### `usuarios`

Usada para auditoría. Esquema mínimo esperado: al menos
`id BIGINT UNSIGNED PK`. Las columnas `usuario_*_id` del módulo guardan
referencias pero **sin FK constraint**, así que el módulo levanta aunque
`usuarios` no exista todavía (la auditoría queda con `usuario_id = NULL`
hasta que la tabla y la sesión estén conectadas).

> **Importante**: si alguna de estas tablas tiene un esquema distinto al
> documentado, frená y avisame antes de seguir. No improvises mappings.

---

## Sesión / autenticación

El módulo **no implementa login propio**. Asume que tu sistema ya inició
sesión PHP y dejó el id del usuario en `$_SESSION['usuario_id']` antes de
servir cualquier vista o endpoint del laboratorio.

Contrato mínimo:

```php
session_start();
$_SESSION['usuario_id'] = (int) $usuarioLogueado->id;
// Opcional, para roles futuros:
// $_SESSION['rol'] = 'recepcion' | 'tecnico' | 'admin';
```

Si la variable no existe, los cambios quedan auditados con
`usuario_id = NULL` (modo dev sin sesión, no apto para producción).

**Roles**: pendiente de acordar. Por ahora los endpoints no chequean rol;
cualquier sesión válida puede hacer todo. Antes de exponer a producción
hay que definir el esquema de roles.

---

## Embebido del frontend

El módulo expone vistas standalone bajo `/public/views/` servidas por el
front-controller `/public/index.php`. Tres opciones para integrarlo:

1. **Rutas montadas** (recomendado): tu sistema mapea `/laboratorio/*` a
   `/public/index.php` del módulo (Apache alias, nginx location, o
   reescritura en tu router). Las URLs internas (`/pedidos`, `/aranceles`,
   etc.) se exponen con prefijo. Requiere ajuste menor en el
   front-controller para stripear el prefijo — avisar cuando se elija
   este camino.
2. **iframe**: cargar el módulo en un iframe. Requiere CSP compatible y
   cuidar la cookie de sesión.
3. **Sub-app**: subdominio (`lab.clinica.com`). Necesita SSO entre
   dominios (más complejo).

---

## Instalación paso a paso

### 1. Bajar el código

```bash
git clone https://github.com/Dondita1/laboratorio.git
cd laboratorio
composer install
cp .env.example .env
```

Editar `.env` con las credenciales de la BD compartida:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=<tu_bd>
DB_USER=<usuario>
DB_PASS=<password>
```

### 2. Verificar tablas compartidas

Tu BD debe tener `pacientes`, `obras_sociales`, `medicos` y (deseable)
`usuarios` con el esquema documentado más arriba. **Backup antes de
seguir**.

Si querés probar el módulo en local sin tu sistema todavía, hay seeds de
desarrollo en `sql/seeds/dev_*.sql`. No los uses en producción.

### 3. Aplicar el esquema del módulo

**Backup primero, en serio.**

```bash
mysql -u $DB_USER -p $DB_NAME < sql/install/lab_schema.sql
```

Esto crea 18 tablas con prefijo `lab_`:

```
lab_areas                        lab_perfiles
lab_determinaciones              lab_perfil_determinaciones
lab_valores_referencia           lab_pedidos
lab_pedido_items                 lab_resultados
lab_resultados_historico         lab_informes
lab_auditoria                    lab_pagos
lab_nbu_determinaciones          lab_nbu_valores_os
lab_lotes_os                     lab_lote_pedidos
lab_lote_pedido_item_excluido    lab_config
```

Solo `CREATE TABLE`, sin `DROP`. El dump **no tiene FKs hacia tus
tablas** (`pacientes`, `obras_sociales`, `medicos`, `usuarios`): las
columnas `paciente_id`, `obra_social_id`, etc. existen pero sin
constraint, así que se aplica aunque esas tablas no estén creadas
todavía. Igual tienen que existir cuando el sistema empiece a usarse,
porque el módulo las JOINea en runtime.

Verificar que se crearon las 18 tablas:

```sql
SELECT COUNT(*) FROM information_schema.tables
WHERE table_schema = '<tu_bd>' AND table_name LIKE 'lab\\_%';
-- Esperado: 18
```

> Si preferís aplicar las migrations en orden cronológico en vez del dump,
> el directorio `sql/migrations/` tiene 000–023. Llegan al mismo estado
> final pero crean y luego dropean tablas transitorias. Usar el dump es
> más limpio.

### 4. Cargar seeds (opcionales pero recomendados)

```bash
# Catálogo base: 5 áreas + 30 determinaciones genéricas + perfiles típicos
mysql -u $DB_USER -p $DB_NAME < sql/seeds/001_seed_catalogo.sql

# Configuración institucional inicial (claves vacías de lab_config)
mysql -u $DB_USER -p $DB_NAME < sql/seeds/sp9_lab_config_inicial.sql

# Opcional: 368 determinaciones reales del cliente (códigos oficiales)
mysql -u $DB_USER -p $DB_NAME < sql/seeds/002_seed_determinaciones_cliente.sql

# Opcional: 1381 valores NBU 2012 (~305 matchean el catálogo del cliente)
mysql -u $DB_USER -p $DB_NAME < sql/seeds/seed_nbu_completo.sql
```

**No cargar**:
- `dev_pacientes.sql`, `dev_medicos.sql`, `dev_bioquimicos.sql` — fixtures
  de desarrollo. En tu sistema esas tablas las gestionás vos.
  `dev_bioquimicos.sql` además ya no aplica: la tabla `lab_bioquimicos`
  fue eliminada (el firmante ahora vive en `lab_config`).
- `seed_nbu.sql` — versión parcial vieja. Usar `seed_nbu_completo.sql`.

### 5. Levantar el módulo

```bash
php -S localhost:8000 -t public public/index.php
```

Healthcheck:

```
GET http://localhost:8000/health
→ { "success": true, "data": { "module": "laboratorio", "status": "ok", ... } }
```

### 6. Completar datos institucionales

Abrir `http://localhost:8000/configuracion` y completar:

- Nombre del laboratorio, dirección, teléfono, email, web.
- Resolución del Colegio, registro SISA.
- Logo (PNG o JPG).
- Datos del firmante del informe: apellido, nombres, matrícula, título,
  imagen de firma.

Estos datos viven en `lab_config` y aparecen en cada PDF de informe.

---

## Vistas del módulo

| URL | Vista |
|---|---|
| `/` | Home (cards por módulo) |
| `/pacientes` | Buscador de pacientes |
| `/pedidos` | Listado y búsqueda de órdenes |
| `/pedidos/nuevo` | Alta de orden médica |
| `/pedidos/ver?id=N` | Detalle de orden |
| `/resultados/cargar` | Carga de resultados (técnico) |
| `/historial` | Historial por paciente |
| `/informes` | Emisión y descarga de PDFs |
| `/reportes` | Reporte financiero |
| `/aranceles` | NBU y valores por obra social (con vigencias) |
| `/facturacion-os` | Lotes de facturación a obras sociales |
| `/configuracion` | Datos institucionales |

---

## API HTTP (JSON)

Todas las respuestas siguen la estructura:

```json
{ "success": true,  "data": { ... }, "error": null }
{ "success": false, "data": null,    "error": { "code": "...", "message": "...", "fields": {...} } }
```

HTTP status codes: 200, 201, 400, 401, 403, 404, 409, 422, 500.

Endpoints disponibles bajo `/api/<recurso>`:

`aranceles`, `determinaciones`, `facturacion-os`, `historial`,
`informes`, `lab-config`, `medicos`, `pacientes`, `pedidos`, `perfiles`,
`planillas`, `reportes`, `resultados`.

Cada recurso acepta `?accion=<verbo>` (ej:
`/api/pacientes?accion=buscar&q=Perez`).

---

## Tests

```bash
composer test
# o
./vendor/bin/phpunit
```

---

## Troubleshooting

- **"Tabla `lab_X` ya existe"** — el módulo ya fue aplicado antes. Para
  reinstalar desde cero, dropealas a mano y volvé a aplicar `lab_schema.sql`.
- **"Cannot resolve foreign key"** — alguna FK interna del módulo falla.
  Improbable: las tablas están listadas en orden de dependencias en el
  dump. Si pasa, abrime un issue.
- **Errores de collation** — verificá que tu BD use `utf8mb4_unicode_ci`.
- **Auditoría con `usuario_id = NULL`** — `$_SESSION['usuario_id']` no
  está seteado. Tu sistema tiene que hacerlo antes de delegar al módulo.
- **`obra_social_nombre` aparece vacío** — el JOIN entre `lab_pedidos` y
  tu `obras_sociales` falla. Revisá nombres de columna y tipos.

---

## Estado del acuerdo

- [ ] Contrato `pacientes` confirmado.
- [ ] Contrato `obras_sociales` confirmado.
- [ ] Contrato `medicos` confirmado.
- [ ] Contrato `usuarios` confirmado.
- [ ] Mecanismo de sesión acordado (claves en `$_SESSION`).
- [ ] Estrategia de embebido acordada (rutas / iframe / sub-app).
- [ ] Esquema de roles acordado.

