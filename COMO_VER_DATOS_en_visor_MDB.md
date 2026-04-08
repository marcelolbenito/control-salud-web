# Cómo ver los datos en el visor MDB

Si ves las **tablas** pero no los **datos**, seguí estos pasos.

---

## 1. Abrir el archivo que SÍ tiene datos

Hay dos archivos .mdb:

| Archivo | Contenido |
|---------|-----------|
| **Datos.mdb** | Base con **datos** (pacientes, turnos, pagos, etc.). |
| **Datos_Vacio.mdb** | Solo **estructura** (mismas tablas), **sin registros**. |

Si abriste **Datos_Vacio.mdb**, es normal no ver datos: está vacía a propósito.

**Qué hacer:** Cerrá el visor y abrí **Datos.mdb** (contraseña **control333**). Los datos están solo en ese archivo.

---

## 2. Cómo ver los registros en el visor

En MDB Admin (y en la mayoría de visores MDB):

1. En el **panel izquierdo** se listan las tablas (Pacientes, Agenda Turnos, etc.).
2. **Hacé clic en el nombre de una tabla** (por ejemplo **Pacientes**).
3. Los **datos** (filas/registros) suelen aparecer:
   - en un **panel derecho**, o
   - en una **pestaña** tipo "Datos" / "Records" / "Vista de datos", o
   - al **doble clic** sobre la tabla.

Si el visor tiene pestañas arriba (Estructura / Datos / SQL), elegí **Datos**.

---

## 3. Tablas donde suele haber datos

Probá abriendo estas (sobre **Datos.mdb**):

- **Pacientes** – lista de pacientes.
- **Agenda Turnos** – turnos.
- **Lista Doctores** – médicos.
- **Pacientes Pagos** – pagos.
- **Pacientes Ordenes** – órdenes.

Si en **Pacientes** no ves filas, puede que esa base tenga pocos o ningún registro; probá con **Agenda Turnos** o **Lista Doctores**.

---

## Resumen

1. Usar **Datos.mdb** (no Datos_Vacio.mdb).
2. En el visor: **elegir una tabla** en la lista (clic o doble clic).
3. Buscar la vista de **datos/registros** (panel derecho o pestaña "Datos").

Si seguís sin ver datos, decime qué visor usás (MDB Admin, otro) y qué ves exactamente al hacer clic en una tabla (por ejemplo: “solo columnas”, “mensaje de error”, “tabla vacía”).
