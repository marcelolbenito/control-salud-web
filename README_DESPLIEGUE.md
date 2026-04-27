# Control Salud Web — Guía de despliegue y operación

> Documento maestro de seguimiento técnico/funcional del proyecto.
> Desde ahora centralizamos aquí el estado y pendientes; reemplaza a `PENDIENTES.md` y `PENDIENTES_PARIDAD_EXE.md`.

Aplicación **PHP + MySQL**. Esta guía concentra instalación, migraciones SQL en orden útil, verificaciones y seguimiento de **paridad** con `Control Salud.exe` (referencia: `REQUISITOS_Sistema_ControlSalud.md` y `Datos.mdb` cuando haya dudas de campos).

---

## 1. Requisitos

- PHP con extensiones habituales (PDO MySQL, JSON, mbstring).
- MySQL/MariaDB.
- Configuración: `web/config/config.local.php` (partir de `.env.example` / documentación del proyecto si existe).

---

## 2. Instalación de esquema (nueva base o desde cero)

1. Cargar **`sql/schema_mysql.sql`** (incluye tablas base y, según versión del archivo, `agenda_bloqueos`, `lista_*` de órdenes, etc.).
2. Aplicar migraciones incrementales **en orden numérico** que falten en tu entorno (`sql/migration_00x_*.sql`, …). Si ya tenés datos legacy, algunos pasos pueden ser opcionales o sustituibles por sync desde backup (ver §3).

---

## 3. Orden recomendado: catálogos de órdenes y listas desde backup

Objetivo: que **`/orden_form.php`** muestre desplegables (cobertura, plan, práctica, derivación, sucursal) y no solo números.

| Paso | Script | Notas |
|------|--------|--------|
| A | `sql/migration_023_listas_ordenes_catalogos.sql` | Crea tablas `lista_*` de órdenes si no existen. Idempotente. |
| B | `sql/migration_021_sync_listas_from_sqlserver_backup_tables.sql` | Copia desde tablas del backup con nombres tipo Access (`Lista Coberturas`, `Lista Planes`, …). Prácticas: `Lista Practicas` o variantes de `Nomenclador` / **`Lista Nomenclador`** (sin columna `prioridad` en algunos backups: usar `NULL` en `prioridad`). |
| C | `sql/migration_024_sync_ordenes_catalogos_resiliente.sql` | **Opcional pero recomendado** si 021 no alcanza: planes/prácticas/derivaciones con nombres variables (`Lista Derivadores`, etc.). |

**Verificación:** en MySQL, `SELECT COUNT(*) FROM lista_planes;` y `lista_practicas;` — deben tener filas si el backup trae datos. Si falla una tabla origen, comentar el bloque correspondiente en 021 o usar 024.

**Herramienta opcional:** `sql/diagnostico_fuentes_catalogos_ordenes.sql` — solo para inspeccionar tablas/columnas candidatas cuando algo no aparece (ejecutar con `USE tu_base;`).

---

## 4. Multi-clínica

- Script: **`sql/migration_022_multi_clinica.sql`**.
- Verificar usuarios y filas con **`id_clinica`** coherente con el uso en repos/controladores.

---

## 5. Agenda — bloqueos de horario

- Script: **`sql/migration_025_agenda_bloqueos.sql`** (tabla `agenda_bloqueos`).
- **Web:** menú **Bloqueos** → `/agenda_bloqueos.php` (listado, alta/edición, eliminación).
- La grilla de disponibilidad en **nuevo/editar turno** y en **bloqueo** usa `/agenda_slots.php`; los huecos bloqueados no se ofrecen para turnos nuevos.
- En el formulario de bloqueo: **doble clic** en la grilla para sumar/quitar varios turnos; al guardar varios horarios se crean **varias filas** (mismo profesional, rango de fechas y motivo). **Día completo** = un registro sin horas.

---

## 5.1. Historia clínica — evolución inmutable (v1)

- Script: **`sql/migration_026_hc_notas_inmutables.sql`** (tabla `pacientes_hc_notas`).
- Web: **`/historia_clinica.php?id=...`** permite agregar nuevas anotaciones con fecha/hora y conservar historial sin edición/borrado.
- `hc_texto` queda como resumen histórico de solo lectura; nuevas evoluciones van en notas.

---

## 5.2. Historia clínica — adjuntos (v2)

- Script: **`sql/migration_027_hc_adjuntos.sql`** (tabla `pacientes_hc_adjuntos`).
- En la misma alta de anotación permite adjuntar **archivo** (PDF/JPG/PNG/WebP) y/o **link** de estudio.
- Los adjuntos quedan listados junto a cada anotación en `/historia_clinica.php`.

---

## 5.3. Usuarios y roles (ACL inicial)

- Script: **`sql/migration_028_usuarios_roles.sql`** (columna `usuarios.rol`).
- Script: **`sql/migration_029_usuarios_id_doctor.sql`** (columna `usuarios.id_doctor`).
- Roles activos: `superadmin`, `admin_clinica`, `doctor`.
- Administración de usuarios: módulo **Sistema** (`/sistema.php`) con alta/edición/baja.
- Restricción inicial para `doctor`: acceso a agenda y pacientes/historia clínica (sin módulos administrativos).
- Para rol `doctor` se recomienda vincular `id_doctor` para filtrar agenda por su profesional asociado.

---

## 6. Caja / pagos (recordatorio operativo)

- **Pagos:** `/pagos.php`, alta `/pagos_form.php`, recibo `/pagos_recibo.php?id=…`. Desde **Editar orden** → *Registrar pago* si la orden tiene `id`.
- **Tabla:** `pacientes_pagos` (`quien` P/C/O, `NroPaci`, `idorden` opcional, importe, fecha, `forma_pago`).
- **`Pacientes Ordenes.pago`:** sincronizado como suma de pagos con **`quien = 'P'`** por `idorden`.
- **Caja:** movimientos automáticos al crear/editar/borrar pagos **con** `idorden`; ingresos/egresos manuales en módulo Caja.
- **Exe:** recibo RTF y listados avanzados siguen en requisitos; la web hoy usa recibo HTML y flujo básico.

---

## 7. Verificación rápida post-despliegue

1. Login y home.
2. **Órdenes:** formulario con combos de cobertura/plan/práctica.
3. **Agenda:** día + profesional; nuevo turno; **Bloqueos:** crear bloqueo parcial y comprobar que la grilla de turnos marca gris / no asigna.
4. **Pagos** con orden → aparece en **Caja** si aplica.

---

## 8. Paridad con Control Salud.exe — estado y pendientes

**Cubierto en web (alto / parcial):** Pacientes, Doctores, Agenda/Turnos, bloqueos de agenda, Órdenes, Odontograma, Tablas auxiliares, Caja (inicial), Pagos, Sesiones.

**Brechas principales:** filtros de **Sesiones** dentro de Órdenes; liquidar/anular honorarios masivos; multi-estado A/F/P; informes (honorarios, caja por período, etc.) alineados al exe.

### Checklist por área

**Caja**

- [x] Listado, filtros, totales, exportar/imprimir, migración legacy validada en un entorno.
- [ ] Enlaces cruzados órdenes/pagos cuando exista `idorden` o `NroPaci`.
- [ ] Cierre diario de caja (`cerrada` o equivalente) si el negocio lo exige.

**Órdenes**

- [x] Buscador, filtros (`pagaiva`, `honorariofecha`, `numeautorizacion`), grilla con columnas clave, totales.
- [ ] Filtros del bloque Sesiones; multi-selección estados A/F/P; liquidar/anular honorarios; `Pagó Cob` / `Debe Cob` con reglas claras; “no sumar honorarios con cero sesiones” si sigue vigente.
- [ ] Relación **Obra social -> Plan** validada extremo a extremo (catálogos + formulario): al elegir cobertura deben verse solo planes compatibles.
- [ ] Revisar calidad de datos en `lista_planes.id_cobertura` (si queda `NULL`, el formulario muestra planes no acotados).

**Sesiones**

- [x] ABM + filtros + vínculo a orden y `sesionesreali`.
- [ ] Totales por período/doctor e informe/exportación dedicada.

**Pagos**

- [x] ABM, recibo, sync `pago` paciente, impacto en caja con orden.
- [ ] Deuda en reportes; validar reglas exe (estado vs pago real).

**Listados / informes**

- [ ] Separar vistas Órdenes vs Sesiones; listados mínimos (por doctor, cobertura/plan, honorarios, caja); unificar exportación/impresión.

**Historia clínica**

- [ ] Evolución con entradas inmutables: cada anotación con fecha/hora; sin edición ni borrado del texto histórico.
- [ ] Adjuntos por HC: permitir cargar y visualizar archivos (PDF/JPG) y registrar links de estudios.
- [ ] Definir alcance de permisos: quién puede agregar notas/adjuntos y cómo se audita en paridad con el exe.

### Política inicial de roles y permisos (acordada)

- Roles base: **superadmin**, **admin de clínica**, **doctor**.
- Historia clínica (notas y adjuntos): **inmutable** para operación normal (sin editar/borrar).
- Excepción de última instancia: solo **superadmin** puede realizar anulación administrativa (idealmente desde BD o herramienta administrativa restringida).
- Alcance operativo inicial:
  - **Doctor:** acceso a su agenda y a historia clínica.
  - **Admin de clínica:** gestión operativa de la clínica (agenda/pacientes/HC según alcance de clínica).
  - **Superadmin:** control total multi-clínica + soporte excepcional.
- Próximo paso técnico: implementar control por rol con permisos ampliables/reducibles (matriz configurable).

### Criterio de “cerrado” por ítem

- UI visible, persistencia en tabla correcta, filtro básico, imprimir/exportar, validado con un caso real del consultorio.

---

## 9. Scripts Python (migración Access → MySQL)

En **`sql/migracion/`** (`migrar_mdb_a_mysql.py`, `generar_migration_sql.py`, `list_mdb_tablas.py`, etc.): útiles si el origen es **.mdb**; no son necesarios en runtime solo con MySQL + backup ya importado.

---

## 10. Convención de commits (recomendación)

Separar por tema en git: `sql/`, agenda/bloqueos, órdenes/catálogos, caja/pagos — facilita revisiones y rollbacks.

---

*Última unificación de notas: reemplaza `PENDIENTES.md` y `PENDIENTES_PARIDAD_EXE.md`.*
