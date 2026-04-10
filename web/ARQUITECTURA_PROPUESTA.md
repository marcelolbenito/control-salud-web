# Arquitectura web (estado actual y guía)

Objetivo: mantener una base ordenada, consistente con Control Salud original (exe + BD), y fácil de extender sin mezclar SQL, lógica y vista.

## Estructura actual (real)

```text
web/
  public/                    # Entrypoints HTTP
    index.php
    login.php
    logout.php
    setup.php
    pacientes.php
    paciente_form.php
    paciente_eliminar.php
    doctores.php
    doctor_form.php
    doctor_eliminar.php
    agenda.php
    turno_form.php
    turno_eliminar.php
    ordenes.php
    orden_form.php
    orden_eliminar.php
    catalogos.php            # ABM catálogos lista_*
    sistema.php              # Configuración web + mapeo Config.exe
    css/
    js/
  src/
    Controllers/             # Orquestación HTTP (request -> repo -> view)
    Repositories/            # SQL y acceso a datos
    Views/                   # Plantillas por módulo
      pacientes/
      doctores/
      agenda/
      ordenes/
      catalogos/
      sistema/
    Catalog/
      CatalogRegistry.php    # Lista blanca y metadatos de catálogos
    Config/
      ConfigExeFieldMap.php  # Mapeo Config.exe -> config web
  includes/
    bootstrap.php
    auth.php
    flash.php
    layout.php
    catalogos.php            # Helpers comunes de catálogos
    db_schema.php            # utilidades de introspección de esquema
  config/
    config.php
    config.example.php
    config.local.php         # local, no versionado
    database.php
```

## Reglas por capa

- `public/`: sin SQL; solo autenticación básica, selección de acción y delegación al controller.
- `src/Controllers/`: validación de entrada, flujo y redirecciones; no renderizan SQL directo.
- `src/Repositories/`: consultas SQL, mapeos de columnas/tablas y operaciones CRUD.
- `src/Views/`: HTML/PHP de presentación; sin reglas de negocio pesadas.
- `includes/`: infraestructura transversal (sesión, auth, layout, helpers comunes).

## Convenciones de consistencia

- Catálogos tipificados: solo tablas `lista_*` registradas en `CatalogRegistry` (lista blanca).
- Configuración web: tabla `config` como `clave -> valor` (no replicar tabla ancha `Config` del exe).
- Migraciones SQL incrementales: `sql/migration_###_descripcion.sql`.
- Evitar archivos temporales en repo (`probar_*.py`, `validar_*.py`, dumps `.sql` gigantes).

## Cómo reflejar futuros cambios (protocolo)

Cada cambio de arquitectura o módulo debe incluir estos 3 pasos:

1. **Código**: implementar cambios en la capa correcta.
2. **SQL** (si aplica): agregar migración incremental en `sql/`.
3. **Documentación**: actualizar este archivo en la sección correspondiente:
   - estructura si se agregan carpetas/entrypoints,
   - convenciones si cambian reglas,
   - módulo nuevo si aparece (`catalogos`, `sistema`, etc.).

Checklist mínimo por PR/cambio grande:

- [ ] ¿Se agregó/quitó algún entrypoint en `public/`?
- [ ] ¿Se creó/renombró Controller/Repository/View?
- [ ] ¿Hubo cambios de esquema/migraciones?
- [ ] ¿Este archivo (`web/ARQUITECTURA_PROPUESTA.md`) quedó actualizado?

## Próximos pasos recomendados

1. Incorporar autoload PSR-4 simple para `src/` (evitar `require_once` repetidos).
2. Centralizar validaciones reutilizables (por ejemplo, `src/Support/Validator.php`).
3. Definir política de “datos de referencia” para catálogos (semillas SQL versionadas).
4. Mantener un changelog técnico breve por iteración (puede ser sección al final de este archivo).

## Historial técnico

### 2026-04-10

- Se consolidó el patrón por capas activo (`public` -> `Controllers` -> `Repositories` -> `Views`).
- Se agregaron módulos de administración:
  - `catalogos.php` + `CatalogosController` + `CatalogosListaRepository` + vistas `catalogos/*`.
  - `sistema.php` + `SistemaController` + vistas `sistema/*`.
- Se incorporó `CatalogRegistry` para lista blanca de catálogos `lista_*` editables.
- Se incorporó `ConfigExeFieldMap` para mapear `Config` del exe a `config` web (clave/valor).
- Se actualizó navegación principal (`layout`) con accesos a Catálogos y Sistema.
- Se formalizó convención de limpieza del repo (sin scripts de prueba/dumps grandes versionados).

