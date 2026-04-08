# Base "rota" – reparar y ver tablas

LibreOffice a veces dice que un .mdb está "roto" cuando en realidad es una base antigua (Jet) que no interpreta bien. Probá en este orden:

---

## 1. Reparar con Microsoft Access (si tenés Access)

Si **Datos.mdb** se abre en Access (aunque solo veas Información e Imprimir), la base puede estar bien y solo hay que **compactar/reparar**:

1. **Hacé una copia de seguridad:**  
   Copiá `Datos.mdb` a otra carpeta (ej. `Datos_respaldo.mdb`). No toques el original hasta terminar.

2. Cerrando **todos** los programas que usen esa base (Control Salud, etc.), abrí **solo Microsoft Access**.

3. **Archivo → Abrir** y elegí `C:\Control Salud\Datos.mdb` (contraseña **control333** si la pide).

4. En el menú: **Herramientas de base de datos** (o **Base de datos**) → **Compactar y reparar base de datos**.  
   O: **Archivo → Información** y buscar la opción **Compactar y reparar**.

5. Access va a compactar/reparar y guardar. Después probá de nuevo abrir la base y usar **F11** para ver el panel de navegación y las **tablas**.

Si no encontrás el menú, en versiones recientes suele estar en: **Archivo → Información → Compactar y reparar base de datos**.

---

## 2. Ver la estructura con Datos_Vacio.mdb

En la carpeta está **Datos_Vacio.mdb** (plantilla vacía). Ese archivo suele estar sano y tiene **la misma estructura** de tablas (solo que sin datos).

- Abrí **Datos_Vacio.mdb** con **LibreOffice Base** (o con Access).  
- Si abre bien, ahí podés ver **todas las tablas y campos** para replicar en web.  
- Los **datos** siguen en **Datos.mdb**; la estructura la podés tomar de **Datos_Vacio.mdb**.

---

## 3. Probar con MDB Admin u otro visor

Algunos programas leen .mdb antiguos mejor que LibreOffice:

- **MDB Admin:**  
  https://sourceforge.net/projects/mdbadmin/files/latest/download  
  O: `winget install MarcielDegasperi.MDBAdmin`  
  Abrí **Datos.mdb** (contraseña **control333**) y revisá si lista las tablas.

Si MDB Admin también dice que está dañada, entonces sí conviene reparar primero con Access (paso 1).

---

## 4. Si Control Salud sigue abriendo la base

Si el programa **Control Salud** abre y usa la base sin errores, la base no está totalmente rota; suele ser que **LibreOffice** no la soporta bien. En ese caso:

- Usá **Access** para ver las tablas (F11 / panel de navegación).  
- O usá **Datos_Vacio.mdb** en LibreOffice para ver la estructura.  
- Para los datos, seguí usando Access o MDB Admin sobre **Datos.mdb**.

---

## Resumen

| Qué hacer | Para qué |
|-----------|----------|
| **Copia de respaldo** de `Datos.mdb` | No perder nada antes de reparar. |
| **Compactar y reparar** en Access | Intentar arreglar el .mdb. |
| Abrir **Datos_Vacio.mdb** en LibreOffice | Ver estructura de tablas si Datos.mdb no abre. |
| **MDB Admin** sobre Datos.mdb | Otro visor que a veces lee mejor .mdb viejos. |

Siempre hacer la copia de **Datos.mdb** antes de compactar y reparar.
