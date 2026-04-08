# Tablas relacionadas con `Pacientes` (Control Salud.exe / `Datos.mdb`)

En Access hay **121 tablas**. Esto agrupa las que se relacionan con **Pacientes** de forma directa.

## 1. Catálogos referenciados por campos `id*` en `Pacientes`

| Campo en Pacientes | Tabla de lista (típico `id` + texto) |
|---------------------|--------------------------------------|
| `idcobertura` | **Lista Coberturas** (`id`, `nombre`, `Porcentaje_Cobertura`, …) |
| `idcobertura2` | **Lista Coberturas** (segunda obra social) |
| `idplan` | **Lista Planes** (`id`, `idcobertura`, `nombre`) |
| `idtipodoc` | **Lista Tipo de documento** (`id`, `nombre`) |
| `idocupacion` | **Lista Ocupacion** |
| `idocupacionpadre` / `idocupacionmadre` | **Lista Ocupacion** |
| `idrelacion` | **Lista Relacion con el paciente** |
| `idestadocivil` | **Lista Estado civil** |
| `idetnia` | **Lista Etnia** |
| `idciudad` | **Lista Ciudad** (`id`, `nombre`) |
| `idprovincia` | **Lista Provincia** |
| `idpais` | **Lista Pais** |
| `idestatus` | **Lista Estatus en el pais** |

`cobertura` (SMALLINT) en Pacientes puede ser índice/código auxiliar; conviene contrastar en el exe si no es FK directo a `Lista Coberturas`.

## 2. Tablas “hijas” (datos por paciente)

Prefijo **`Pacientes …`** — suelen enlazar por **`NroHC`** y/o **`id`** interno del paciente (según tabla):

- **Pacientes Ordenes**, **Pacientes Sesiones**, **Pacientes Pagos**
- **Pacientes Estudios**, **Pacientes Estudios Imagenes**, **Pacientes Analisis**
- **Pacientes Embarazos**, **Pacientes Embarazos Finalizados**, **Pacientes Control Feto**, **Pacientes Control Materno**
- **Pacientes Enfermedades Ginecologicas**, **Pacientes Operaciones Ginecologicas**, **Pacientes Esquema Ginecologico**
- **Pacientes Vacunas**, **Pacientes Odontogramas**, **Pacientes Presupuestos**, **Pacientes Pautas**
- **Pacientes Alimentacion**, **Pacientes Cirugias**, **Pacientes Biomicroscopia**, **Pacientes Fondo de Ojo**, etc.
- Varias tablas de **ultrasonido** y consultas gineco/oftalmo según nombres en el listado.

## 3. Agenda y consultas (vinculación habitual)

- **Agenda Turnos** — `NroHC`, `Doctor` → **Lista Doctores**
- **Consultas** / **Consultas Items** — historial por paciente/doctor
- Muchas tablas **Consulta - …** y **Consulta Exa - …** (módulos por especialidad)

## 4. Otras tablas de soporte (no solo Pacientes)

- **Config**, **Usuarios**, **Sucursales**, **Caja**, **Camas***, **Certificados***, **CIE10**, listas de **Lista Drogas**, **Lista Vacunas**, **Lista Estudios**, etc., usadas desde varios módulos.

## 5. Implicancia para la web

- Los **`id_*` en Pacientes** deben resolverse con **SELECT** a las tablas **Lista …** (desplegables o texto mostrado).
- Para paridad con el exe, conviene **replicar primero las `Lista *` críticas** (cobertura, ciudad, provincia, país, plan, tipo doc, ocupación, estado civil, etnia, relación) y luego enlazar en el formulario ampliado de paciente.
- El script `listar_tablas_mdb.py` en esta carpeta permite volver a listar tablas/columnas desde `Datos.mdb`.
