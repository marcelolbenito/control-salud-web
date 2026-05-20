# Carpeta `export/` — dumps legacy

Los archivos `*.sql` **no van al repositorio** (están en `.gitignore`). Se generan en tu PC.

## Generar todo

```powershell
cd "C:\Control Salud\sql\migracion"
.\preparar_legacy_para_produccion.ps1
```

Con dump intermedio ya hecho:

```powershell
.\preparar_legacy_para_produccion.ps1 -SkipExport
```

## Archivos que produce

| Archivo | Uso |
|---------|-----|
| `datos_legacy_Pacientes_Doctores_Turnos.sql` | Dump intermedio (~50 MB). Sirve para `-SkipExport`. Podés borrarlo y volver a exportar desde SQL Server. |
| `datos_legacy_doctores.sql` | Subir a producción |
| `datos_legacy_pacientes.sql` | Subir a producción |
| `datos_legacy_turnos_fixed.sql` | Subir a producción (turnos ≥ 2026) |

## Scripts (sí van al repo)

- `extract_doctores_pacientes_from_dump.py`
- `rebuild_turnos_from_dump.py`

Guía completa: [`../PROCEDIMIENTO_DUMP_BAK_PRODUCCION.md`](../PROCEDIMIENTO_DUMP_BAK_PRODUCCION.md)

## Limpieza de disco

Después de importar en producción podés borrar todos los `.sql` de esta carpeta; cuando haga falta, ejecutá de nuevo `preparar_legacy_para_produccion.ps1`.

**No usar:** archivos `*_part*.sql` ni filtros viejos por líneas (eliminados del proyecto).
