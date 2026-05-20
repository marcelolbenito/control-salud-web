# Sistema de Laboratorio Clinico

Modulo de gestion de analisis de laboratorio para una clinica. Es un **modulo embebible** que se integra con un sistema clinico mayor (gestion de turnos, historia clinica, facturacion) desarrollado por otro programador en paralelo.

> Para integrar el modulo a un sistema existente, leer [`INTEGRACION.md`](./INTEGRACION.md). Cubre contrato de tablas compartidas, sesion, embebido del frontend e instalacion end-to-end.

---

## Requisitos

- PHP 8.2 o superior
- Composer 2.x
- MySQL 8 o MariaDB 10.6+ (compartida con el sistema principal)
- Extensiones PHP: `pdo_mysql`, `mbstring`, `json`, `dom`, `gd` (para Dompdf)

---

## Instalacion rapida (dev local)

```bash
composer install
cp .env.example .env   # editar con credenciales
mysql -u <usuario> -p <base> < sql/install/lab_schema.sql
php -S localhost:8000 -t public public/index.php
```

Healthcheck: `GET http://localhost:8000/health` deberia devolver:

```json
{ "success": true, "data": { "module": "laboratorio", "version": "0.1.0", "status": "ok" }, "error": null }
```

---

## Tests

```bash
composer test
# o
./vendor/bin/phpunit
```

---

## Estructura del proyecto

```
/laboratorio
|-- /api                  # Endpoints HTTP que devuelven JSON
|-- /src                  # Logica del backend (PSR-4: App\)
|   |-- /Controllers
|   |-- /Models
|   |-- /Services         # Logica de negocio
|   |-- /Repositories     # Acceso a BD
|   |-- /Helpers
|   `-- /Exceptions
|-- /public               # Unico punto de entrada web
|   |-- index.php
|   |-- /assets (js, css, img)
|   `-- /views
|-- /config
|-- /sql
|   |-- /migrations
|   |-- /install          # Schema dump del estado final
|   `-- /seeds
|-- /tests                # PHPUnit
|-- /storage
|   |-- /informes         # PDFs generados
|   `-- /logs
|-- /vendor               # Composer (no versionar)
|-- .env.example
|-- composer.json
|-- README.md
`-- INTEGRACION.md
```

---

## Convenciones rapidas

- **Backend:** PHP 8.2, PSR-12, PSR-4, `declare(strict_types=1)`, PDO con prepared statements **siempre**.
- **Base de datos:** todas las tablas del modulo con prefijo `lab_`. Las tablas `pacientes`, `obras_sociales`, `medicos` y `usuarios` son del sistema principal: solo lectura.
- **Frontend:** Vanilla JS con modulos ES6, sin bundler, sin frameworks pesados.
- **Respuestas API:** siempre JSON con la estructura `{ success, data, error }`.
- **Borrado:** soft delete (`deleted_at`), nunca `DELETE` fisico de datos clinicos.

---

## Datos de prueba (desarrollo local)

Para correr el modulo en local sin el sistema principal, hay seeds con datos
ficticios de pacientes, medicos y obras sociales en `sql/seeds/dev_*.sql`.

```bash
mysql -u $DB_USER -p $DB_NAME < sql/seeds/dev_pacientes.sql
mysql -u $DB_USER -p $DB_NAME < sql/seeds/001_seed_catalogo.sql
mysql -u $DB_USER -p $DB_NAME < sql/seeds/002_seed_determinaciones_cliente.sql
```

> Solo para desarrollo. En produccion esas tablas las gestiona el sistema principal.
