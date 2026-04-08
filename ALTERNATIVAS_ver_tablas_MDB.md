# Cómo ver las tablas del .mdb sin MDB Viewer Plus

Si no podés descargar MDB Viewer Plus (el antivirus a veces lo bloquea, o el sitio no responde), podés usar cualquiera de estas alternativas.

---

## 1. LibreOffice Base (recomendado, gratis)

**Ventaja:** Es software libre, muy usado; no es “un viewer raro” y el antivirus no suele quejarse.

1. Si no tenés LibreOffice: descargalo desde **https://es.libreoffice.org/descarga/** (versión “Comunidad”).
2. Instalá **LibreOffice** (incluye Base).
3. Abrí **LibreOffice Base**.
4. Elegí **“Conectar con una base de datos existente”** → **Microsoft Access**.
5. Buscá y seleccioná `C:\Control Salud\Datos.mdb`.
6. Cuando pida contraseña, usá **control333** (o la que te haya funcionado en Access).
7. En la ventana de Base vas a ver la lista de **tablas** a la izquierda; hacé doble clic en una para ver los datos.

**Nota:** Con bases muy viejas a veces hay limitaciones, pero para ver tablas y datos suele ir bien.

---

## 2. MDB Admin (gratis, desde SourceForge o winget)

- **Descarga directa:**  
  **https://sourceforge.net/projects/mdbadmin/files/latest/download**  
  (es un .zip; lo descomprimís y ejecutás el .exe).

- **O con winget** (en PowerShell o CMD como administrador):  
  ```  
  winget install MarcielDegasperi.MDBAdmin  
  ```

Después abrís MDB Admin → Archivo → Abrir → `Datos.mdb` (contraseña **control333**). Ahí podés ver y listar todas las tablas.

**Requisito:** Para .mdb suele usar el motor que ya tengas (Jet/ACE). Si no abre, puede que necesites el **Microsoft Access Database Engine** (el que intentaste instalar con `/passive`).

---

## 3. Aryson MDB Viewer (gratis)

- **Sitio oficial:**  
  **https://www.arysontechnologies.com/mdb-viewer.html**  
  Ahí suele estar el enlace de descarga de la versión gratuita.

- **Otra opción:**  
  **https://download.cnet.com/aryson-mdb-viewer/**  
  (CNET a veces ofrece el instalador).

La versión gratuita permite **ver** tablas y datos; no guarda cambios en el .mdb, pero alcanza para explorar.

---

## 4. Usar Microsoft Access (si ya lo tenés abierto)

Si **Datos.mdb** se abre en Access pero solo ves “Información” e “Imprimir”:

1. Probá la tecla **F11** para mostrar el **panel de navegación** (donde están Tablas, Consultas, etc.).
2. Si no aparece: en la cinta de Access buscá **“Navegación”** o **“Panel de navegación”** y activalo.
3. En ese panel, elegí **“Tablas”** o **“Todos los objetos”** para listar las tablas.

La base que usa el sistema es **Datos.mdb** en `C:\Control Salud\`; las tablas están ahí, solo hay que hacer visible el panel.

---

## Resumen rápido

| Opción              | Enlace / comando |
|---------------------|-------------------|
| **LibreOffice Base** | https://es.libreoffice.org/descarga/ |
| **MDB Admin**        | https://sourceforge.net/projects/mdbadmin/files/latest/download o `winget install MarcielDegasperi.MDBAdmin` |
| **Aryson MDB Viewer**| https://www.arysontechnologies.com/mdb-viewer.html |
| **Access (F11)**     | Con Datos.mdb abierto en Access, pulsar F11 para ver tablas |

Recomendación: probar primero **LibreOffice Base** (ya tenés el .mdb y la contraseña; solo falta instalar LibreOffice). Si no podés descargar MDB Viewer Plus, una de estas alternativas debería permitirte ver las tablas sin problema.
