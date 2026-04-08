# Arquitectura propuesta (siguiente etapa)

Objetivo: separar responsabilidades y dejar la base lista para crecer sin mezclar consultas SQL, lógica y HTML en el mismo archivo.

## Estructura objetivo

```text
web/
  public/
    index.php
    pacientes.php
    doctores.php
    agenda.php
    assets/
      css/
      js/
      img/
  src/
    Controllers/
    Repositories/
    Services/
    Views/
      layouts/
      pacientes/
      doctores/
      agenda/
    Support/
  includes/
    bootstrap.php
    auth.php
    flash.php
    layout.php
  config/
```

## Criterios por capa

- `public/`: solo entrypoints HTTP (validar auth básica y delegar al controller).
- `Controllers/`: orquestan request/response, validaciones simples, redirects.
- `Repositories/`: acceso a datos SQL (queries, mapeos de tablas legacy a web).
- `Services/`: reglas de negocio reutilizables (ej: sincronización backup -> tablas web).
- `Views/`: HTML/template puro, sin consultas SQL.

## Estado actual

- Ya migrado a patrón por capas (entrypoint en `public/` → Controller → Repository → View):
  - `pacientes.php`, `paciente_form.php`, `paciente_eliminar.php`
  - `historia_clinica.php`
  - `doctores.php`, `doctor_form.php`, `doctor_eliminar.php`
  - `agenda.php`, `turno_form.php`, `turno_eliminar.php`
- Pendiente para continuar:
  - `index.php`, `login.php`, `logout.php`, `setup.php`
  - autoload PSR-4 o bootstrap de clases en `src/`
  - carpeta `public/assets/` (mover `css` desde `public/css` si se desea)

## Próxima iteración sugerida

1. Extraer validaciones a `src/Support/Validator.php`.
2. Consolidar mapeos legacy en un servicio (`src/Services/LegacySyncService.php`).
3. Separar estilos por módulos:
   - `assets/css/base.css`
   - `assets/css/pacientes.css`
   - `assets/css/agenda.css`
4. Agregar JS progresivo por módulo (filtros, autocompletes) en `assets/js`.

