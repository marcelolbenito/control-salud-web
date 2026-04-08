# Estructura extraída del .exe y credenciales BD – Para replicar en web

Se extrajeron cadenas del ejecutable **Control Salud.exe** para inferir tablas, campos y lógica. No es un desensamblado completo (haría falta una herramienta tipo VB Decompiler), pero alcanza para entender el modelo de datos y planificar una versión web.

---

## 1. ¿Se puede “desglosar” el .exe?

- **Sí, de forma limitada:** se puede extraer **textos** (nombres de tablas, consultas SQL, mensajes). Eso ya da una buena idea de la estructura.
- **Código fuente completo:** no. Hace falta un **decompilador VB6** (ej. VB Decompiler, comercial) para acercarse al código original. Con lo que tenemos aquí no se genera código VB desde el .exe.
- **Lo que sí se obtuvo:** nombres de tablas, relaciones (por las consultas SQL), campos citados y que soporta **Access y SQL Server**.

---

## 2. Credenciales de la base de datos Access

### 2.1 Uso normal (archivo `Datos.mdb`)

En aplicaciones VB6 + Access de esta época, la conexión al `.mdb` suele ser **sin usuario ni contraseña** a nivel de base de datos:

| Concepto        | Valor típico |
|-----------------|--------------|
| **Usuario**     | *(ninguno)*  |
| **Contraseña**  | *(ninguna)*  |
| **Archivo**     | `Datos.mdb` en la carpeta del programa (ej. `C:\Control Salud\Datos.mdb`) |
| **Proveedor**   | `Microsoft.Jet.OLEDB.4.0` (Jet 4.0) o `Microsoft.ACE.OLEDB.12.0` (ACE) |

El archivo **Datos.mdb** tiene cabecera **"Standard Jet DB"** (Jet 4.0). No se detecta contraseña en el formato del archivo; el acceso es por **ruta al archivo** y permisos de Windows.

**Cadena de conexión típica (Access):**
```text
Provider=Microsoft.Jet.OLEDB.4.0;Data Source=C:\Control Salud\Datos.mdb
```
o con ACE:
```text
Provider=Microsoft.ACE.OLEDB.12.0;Data Source=C:\Control Salud\Datos.mdb
```

La ruta se suele leer en tiempo de ejecución desde `ruta.dat` o `setup.dat` (en tu caso aparecen referencias a “(local)” y “sqlexpress” para el modo SQL Server).

### 2.2 Modo SQL Server (alternativo)

El programa **soporta también SQL Server**. En las cadenas aparece:
- Servidor: `(local)` o similar (SQL Server local / Express).
- Instancia: `sqlexpress`.
- Usuario: `sa` (u otro usuario de SQL).

Las credenciales exactas de SQL se guardan en `ruta.dat` / `setup.dat` (archivos binarios). Para saber usuario y contraseña de SQL habría que:
- verlas en la configuración dentro del programa (si tiene pantalla de configuración de BD), o  
- monitorear la conexión (ej. con Wireshark o un proxy de BD) si se usa en red.

**Resumen credenciales Access:** no hay usuario/contraseña; solo ruta a `Datos.mdb` y proveedor Jet/ACE.

---

## 3. Estructura de tablas y campos (inferida del .exe)

De las cadenas y fragmentos SQL extraídos se deducen estas **tablas** y **campos** (nombres exactos como en la BD):

### Tablas principales

| Tabla | Uso |
|-------|-----|
| **Pacientes** | Datos del paciente: NroHC, Nombres, DNI, convenio, etc. |
| **Agenda Turnos** | Turnos: Fecha, NroHC, Doctor, idorden, etc. |
| **Pacientes Ordenes** | Órdenes (estudios/practicas): id, NroPaci, iddoctor, autorizada, entregada |
| **Pacientes Sesiones** | Sesiones por orden: idorden, NroPaci, iddoctor |
| **Pacientes Pagos** | Pagos: quien ('P'=paciente), idorden o NroPaci, importe, fecha |
| **Lista Doctores** | Doctores: id, medicoconvenio, bloquearmisconsultas, sucursal1..sucursal10 |
| **Consultas** | Consultas médicas: iddoctor, etc. |
| **Caja** | Caja: doctor, fechacaja, importecaja, idcaja, idcoberturacaja, etc. |
| **Camas** | Camas: id, sucursal |
| **CamasPacientes** | Asignación cama–paciente: idcama, nropaci, id |
| **CamasGastos** | Gastos por cama: idcamapaci |
| **CamasInsumos** | Insumos por cama: idcamapaci |
| **Pacientes Enfermedades Ginecologicas** | Historial ginecológico |
| **Pacientes Operaciones Ginecologicas** | Operaciones ginecológicas |
| **Consultas Items** | Ítems de consulta |

### Campos recurrentes (por tabla)

- **Pacientes:** NroHC, Nombres, DNI, convenio  
- **Agenda Turnos:** NroHC, Doctor, Fecha, idorden  
- **Pacientes Ordenes:** id, NroPaci, iddoctor, autorizada, entregada  
- **Pacientes Sesiones:** idorden, NroPaci, iddoctor  
- **Pacientes Pagos:** quien, idorden, NroPaci, importe, fecha  
- **Lista Doctores:** id, medicoconvenio, bloquearmisconsultas, sucursal1..sucursal10  

### Otras entidades / conceptos (por textos de la app)

- Certificados, estudios (PDF), fotos, gráficos, odontogramas  
- Honorarios, caja, pagos con tarjeta (débito/crédito), MercadoPago, PayPal  
- Turnos web, agenda telefónica, sobreturnos, plantilla de turnos  
- Derivadores de pacientes, coberturas, sesiones  
- Múltiples sucursales (sucursal1…sucursal10)

---

## 4. Cómo replicar algo similar en web

### 4.1 Base de datos

- **Opción A:** Crear en **SQL Server** o **PostgreSQL** un esquema con las tablas anteriores (Pacientes, Agenda Turnos, Pacientes Ordenes, Pacientes Sesiones, Pacientes Pagos, Lista Doctores, Consultas, Caja, Camas, etc.) y relaciones por id, NroHC, idorden, iddoctor.
- **Opción B:** Si en algún momento puedes abrir `Datos.mdb` (con Access o con el motor ACE instalado), exportar tablas a SQL o a CSV y usarlos como referencia para crear el esquema en tu BD web.

### 4.2 Backend

- API REST (ej. .NET, Node.js, Python) que reproduzca:
  - ABM de pacientes, doctores, turnos, órdenes, sesiones, pagos, caja.
  - Consultas que ya se ven en el .exe: sumas de importes por paciente/orden, filtros por fecha, por doctor, por sucursal.

### 4.3 Frontend

- Pantallas equivalentes a: lista de pacientes, agenda de turnos, órdenes, sesiones, pagos, caja, listados por doctor/sucursal.  
- Subida de archivos para certificados, estudios e imágenes (fotos, odontogramas) en carpetas o almacenamiento en BD/blob.

### 4.4 Credenciales en la versión web

- Para **Access** no aplica usuario/contraseña; solo sirve para migrar o leer `Datos.mdb` desde un servidor con Jet/ACE.
- Para la **app web**, las credenciales serán las de **tu** base de datos (SQL Server, PostgreSQL, etc.) y las de **tu** API (usuarios de sistema, JWT, etc.), no las del .mdb.

---

## 5. Resumen

| Pregunta | Respuesta |
|----------|-----------|
| ¿Se puede desglosar el .exe? | Sí en parte: tablas, campos y lógica a partir de textos/SQL; no el código fuente completo sin un decompilador VB6. |
| Credenciales Access | Sin usuario ni contraseña; conexión por ruta a `Datos.mdb` y proveedor Jet/ACE. |
| Credenciales SQL Server | Configuradas en `ruta.dat`/`setup.dat`; usuario `sa` u otro; hay que verlas en la app o en el servidor. |
| Para replicar en web | Usar la estructura de tablas y campos de este documento, crear el esquema en SQL Server/PostgreSQL y una API + frontend que imiten las pantallas y consultas del programa. |

Si más adelante puedes abrir `Datos.mdb` (por ejemplo con Access o con el motor instalado), se puede afinar el esquema con los tipos de datos y restricciones reales y pasarlo a script SQL para tu base web.
