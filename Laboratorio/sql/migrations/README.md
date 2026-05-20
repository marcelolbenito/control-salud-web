# Migrations

Las migrations crean el esquema `lab_*` del modulo. Se aplican en orden numerico estricto (las posteriores dependen de FKs de las anteriores).

---

## Como aplicar (Laragon + MariaDB)

### 1. Crear la base de datos

Si todavia no existe, creala. Podes hacerlo desde **HeidiSQL** (incluido en Laragon) o por consola:

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS clinica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Aplicar las migrations en orden

Una por una:

```powershell
mysql -u root clinica -e "source sql/migrations/001_create_lab_areas.sql"
mysql -u root clinica -e "source sql/migrations/002_create_lab_determinaciones.sql"
mysql -u root clinica -e "source sql/migrations/003_create_lab_valores_referencia.sql"
mysql -u root clinica -e "source sql/migrations/004_create_lab_perfiles.sql"
mysql -u root clinica -e "source sql/migrations/005_create_lab_perfil_determinaciones.sql"
mysql -u root clinica -e "source sql/migrations/006_create_lab_pedidos.sql"
mysql -u root clinica -e "source sql/migrations/007_create_lab_pedido_items.sql"
mysql -u root clinica -e "source sql/migrations/008_create_lab_muestras.sql"
mysql -u root clinica -e "source sql/migrations/009_create_lab_resultados.sql"
mysql -u root clinica -e "source sql/migrations/010_create_lab_resultados_historico.sql"
mysql -u root clinica -e "source sql/migrations/011_create_lab_informes.sql"
mysql -u root clinica -e "source sql/migrations/012_create_lab_auditoria.sql"
```

O todo de una con un loop PowerShell desde la raiz del proyecto:

```powershell
Get-ChildItem -Path "sql\migrations\*.sql" |
    Where-Object { $_.Name -match "^\d{3}_" } |
    Sort-Object Name |
    ForEach-Object {
        Write-Host "Aplicando $($_.Name)..." -ForegroundColor Cyan
        Get-Content $_.FullName -Raw | mysql -u root clinica
    }
```

### 3. Verificar

Conectate desde HeidiSQL a la base `clinica` y revisa que existan las 12 tablas `lab_*`. Tambien podes hacer:

```powershell
mysql -u root clinica -e "SHOW TABLES LIKE 'lab_%';"
```

Tienen que aparecer las 12: `lab_areas`, `lab_determinaciones`, `lab_valores_referencia`, `lab_perfiles`, `lab_perfil_determinaciones`, `lab_pedidos`, `lab_pedido_items`, `lab_muestras`, `lab_resultados`, `lab_resultados_historico`, `lab_informes`, `lab_auditoria`.

---

## Reglas (no romperlas)

- Una migration ya aplicada **NUNCA se modifica**. Si hay que cambiar algo, se crea una nueva (`013_*.sql`, etc.).
- Numeracion estricta `NNN_descripcion.sql` con tres digitos, lowercase y snake_case.
- Las migrations son idempotentes (`CREATE TABLE IF NOT EXISTS`): correrlas dos veces no rompe nada.
- Orden por dependencias FK: si crees una tabla que referencia a otra, la otra ya tiene que estar creada en una migration anterior.
- FKs a tablas externas (`pacientes`, `usuarios`, `medicos`, `obras_sociales`) se dejan como columnas + INDEX **sin FK explicita** hasta acordar tipos en `INTEGRACION.md`. Despues se agregan via `ALTER TABLE` en una migration nueva.

---

## Estado actual

| #   | Tabla                          | Descripcion                                          | Depende de                                |
|-----|--------------------------------|------------------------------------------------------|-------------------------------------------|
| 001 | lab_areas                      | Areas del laboratorio                                | -                                         |
| 002 | lab_determinaciones            | Catalogo de analisis                                 | lab_areas                                 |
| 003 | lab_valores_referencia         | Rangos por sexo y edad                               | lab_determinaciones                       |
| 004 | lab_perfiles                   | Agrupaciones (perfil tiroideo, etc.)                 | -                                         |
| 005 | lab_perfil_determinaciones     | Pivote perfil-determinacion                          | lab_perfiles, lab_determinaciones         |
| 006 | lab_pedidos                    | Ordenes medicas                                      | (FKs externas)                            |
| 007 | lab_pedido_items               | Items de cada pedido                                 | lab_pedidos, lab_determinaciones, lab_perfiles |
| 008 | lab_muestras                   | Tubos / codigos de barras                            | lab_pedidos                               |
| 009 | lab_resultados                 | Resultado actual de cada item                        | lab_pedido_items                          |
| 010 | lab_resultados_historico       | Versiones anteriores (rectificaciones)               | lab_resultados, lab_pedido_items          |
| 011 | lab_informes                   | PDFs emitidos                                        | lab_pedidos                               |
| 012 | lab_auditoria                  | Log de cambios                                       | -                                         |

---

## Pendiente

- **Seeds**: datos semilla de areas, determinaciones, valores de referencia y perfiles. Van en `sql/seeds/` y se cargan despues de las migrations. Se generan en el siguiente paso del proyecto.
- **FKs externas**: se agregan cuando exista `INTEGRACION.md`.
- **Script `bin/migrate.php`**: tabla `lab_migrations` para registrar que migrations corrieron y aplicarlas idempotentemente. Util cuando el proyecto crezca; por ahora se aplican a mano.
