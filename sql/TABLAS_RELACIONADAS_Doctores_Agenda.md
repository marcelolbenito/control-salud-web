# Tablas relacionadas con `Lista Doctores` y `Agenda Turnos` (Control Salud.exe / `Datos.mdb`)

En Access, el profesional y la agenda se enlazan con el resto del sistema por **IDs** (no siempre hay restricciones `FOREIGN KEY` en el `.mdb`). En MySQL del proyecto se replica ese modelo: relaciones **lógicas** e índices para consultas; el script `migration_004_lista_motivos_consulta.sql` añade la tabla de motivos, un índice sobre `agenda_turnos.motivo` y la vista `v_agenda_turnos_detalle`.

## 1. `lista_doctores` — quién referencia al doctor (`id`)

| Tabla MySQL | Campo | Uso |
|-------------|--------|-----|
| `agenda_turnos` | `Doctor` | Turno asignado al profesional |
| `pacientes_ordenes` | `iddoctor` | Orden médica |
| `pacientes_sesiones` | `iddoctor` | Sesión vinculada a orden/paciente |
| `consultas` | `iddoctor` | Consulta registrada |
| `caja` | `doctor` | Movimiento de caja por doctor |

Borrar o reutilizar un `id` de doctor sin actualizar estas tablas puede dejar **huérfanos** (como en el exe si se hace mal la operación).

## 2. `agenda_turnos` — relaciones del turno

| Campo | Apunta a |
|--------|-----------|
| `NroHC` | `pacientes.NroHC` (paciente) |
| `Doctor` | `lista_doctores.id` |
| `idorden` | `pacientes_ordenes.id` (opcional; orden asociada al turno) |
| `motivo` | `lista_motivos_consulta.id` (tras `migration_003` + `migration_004`; catálogo Access: **Lista Motivos Consulta**) |

Otros campos de la migración 003 (`paciente_nombre`, flags, sesión/caja, etc.) son **denormalizados o auxiliares** como en Access; no sustituyen la relación con `pacientes` por `NroHC`.

## 3. Catálogo de motivos de consulta

- Access: tabla **Lista Motivos Consulta**.
- MySQL: tabla `lista_motivos_consulta` creada en **`migration_004_lista_motivos_consulta.sql`**.
- Conviene importar los mismos `id` que en Access si migrás datos, para que `agenda_turnos.motivo` siga coincidiendo.

## 4. Vista de apoyo (solo lectura)

- `v_agenda_turnos_detalle`: une turno + paciente + doctor + texto del motivo (creada en `migration_004_lista_motivos_consulta.sql`; requiere `migration_003` aplicada).

## 5. Orden sugerido de scripts SQL

1. `schema_mysql.sql`
2. `migration_002_pacientes_campos_exe.sql` (si ampliás pacientes)
3. `migration_003_doctores_agenda_exe.sql` (columnas extra doctores + agenda, incluye `motivo`)
4. **`migration_004_lista_motivos_consulta.sql`** (catálogo motivos + índice + vista)

Para catálogos tipo `lista_*` adicionales: `sql/migracion/schema_listas_minimo.sql` y datos generados/importados según el README de esa carpeta.

## 6. Herramientas

- `sql/listar_tablas_mdb.py` — inspección de tablas/columnas en `Datos.mdb`.
- Documentación paralela: `TABLAS_RELACIONADAS_Pacientes.md`.
