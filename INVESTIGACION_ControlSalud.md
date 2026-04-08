# Investigación: Control Salud (entorno local)

## 1. Lenguaje y tecnología

| Aspecto | Detalle |
|--------|---------|
| **Lenguaje** | **Visual Basic 6** (VB6), aplicación de escritorio Windows |
| **Ejecutable principal** | `Control Salud.exe` — versión **1.84.0006** |
| **Base de datos** | **Microsoft Access** — archivos `Datos.mdb` y `Datos_Vacio.mdb` (formato Jet/ACE) |
| **Ayuda** | `Ayuda.chm` (HTML Help compilado) |
| **Código fuente** | No está en esta carpeta; solo hay ejecutables (.exe) y recursos |

La aplicación **no es .NET**; está compilada con VB6 y depende del runtime de Visual Basic y del motor de base de datos de Access.

---

## 2. Funcionalidades (inferidas por archivos y estructura)

### Módulos / ejecutables
- **Control Salud.exe** — Aplicación principal (gestión de pacientes, historias clínicas, consultorio).
- **AgendaWeb.exe** — Módulo de agenda (turnos/citas); el nombre sugiere integración o exportación “web”.
- **Anunciador.exe** — Pantalla/anunciador de turnos (salas de espera), con sonidos (.wav).
- **Recordatorios.exe** — Recordatorios de citas o seguimientos.

### Carpetas de contenido (`Imagenes\`)
- **Certificados** — Certificados médicos u otros documentos.
- **Estudios** — Estudios (ej. PDFs como prescripción electrónica, APROSS).
- **Fotos** — Fotos de pacientes (carpetas tipo `FotoXXXXX`).
- **Graficos** — Gráficos (evolución, datos clínicos).
- **Odontogramas** — Odontogramas (uso odontológico).
- **fondoanunciador** / **fondoaplicacion** — Fondos de pantalla para anunciador y app.

### Recursos
- **Audio**: `anunciador.wav`, `anunciador25.wav`, `anunciador50.wav`, `anunciador75.wav`, `chat.wav` (anunciador y notificaciones).
- **Configuración**: `ruta.dat`, `setup.dat` (binarios; probablemente rutas y opciones del programa).
- **Base de datos**: `Datos.mdb` (datos actuales), `Datos_Vacio.mdb` (plantilla vacía).

---

## 3. Qué hace falta para que funcione en local

### 3.1 Dependencias de software (Windows)

1. **Runtime de Visual Basic 6**
   - **MSVBVM60.DLL** (Visual Basic 6.0 Runtime).
   - Si no está instalado: instalar [VB6 Runtime](https://www.microsoft.com/es-es/download/details.aspx?id=24417) o paquete redistribuible equivalente.

2. **Acceso a la base de datos Access (.mdb)**
   - **Microsoft Access Database Engine** (Jet 4.0 / ACE).
   - En Windows 64 bits suele hacer falta la versión correcta (32/64 bit según cómo esté compilado el .exe).
   - Descarga: [Microsoft Access Database Engine 2016 Redistributable](https://www.microsoft.com/es-es/download/details.aspx?id=54920) (elegir 32 o 64 bits según la aplicación).

3. **Sistema operativo**
   - Windows (7, 10, 11); en equipos muy nuevos puede haber problemas de compatibilidad con VB6.

4. **Permisos y rutas**
   - La aplicación debe poder leer/escribir en la carpeta de instalación (sobre todo `Datos.mdb`, `Imagenes\`, `ruta.dat`, `setup.dat`).
   - Si “no hay acceso a la BD”: verificar que `Datos.mdb` exista, no esté en solo lectura y que el motor de Access esté instalado (y la versión 32/64 bit coincida).

### 3.2 Comprobaciones si no funciona en local

| Problema | Qué revisar |
|----------|-------------|
| No arranca el .exe | Faltan VB6 Runtime o DLLs (MSVBVM60, etc.). |
| Error al abrir la base de datos | Motor de Access (Jet/ACE) no instalado o versión 32/64 bit incorrecta. |
| “No hay acceso a la BD” | Que `Datos.mdb` exista, no esté bloqueado por otro proceso y que la ruta en `ruta.dat`/`setup.dat` sea la correcta. |
| Anunciador sin sonido | Que los .wav estén en la ruta esperada y los controladores de audio funcionen. |
| Ayuda no se abre | Instalar o reparar soporte para .chm en Windows si está deshabilitado. |

### 3.3 Resumen mínimo para uso local

- **Windows** con **VB6 Runtime** y **Access Database Engine** (Jet/ACE) instalados.
- Archivos del programa en una ruta con permisos de lectura/escritura.
- **Datos.mdb** (o copia de `Datos_Vacio.mdb` renombrada) en la ubicación que use la aplicación (según `ruta.dat`/`setup.dat`).
- Sin código fuente no se puede cambiar la lógica ni la conexión a la BD desde aquí; solo ajustar entorno y datos.

---

## 4. Siguiente paso (réplica web)

Para plantear algo similar en web haría falta:

1. **Esquema de la base de datos**: listar tablas y campos de `Datos.mdb` (con Access o con alguna herramienta que lea .mdb) para replicar el modelo de datos.
2. **Flujos de uso**: definir qué hace cada módulo (principal, agenda, anunciador, recordatorios) en detalle para priorizar en la versión web.
3. **Tecnología web**: backend (ej. .NET, Node, Python) + base de datos (SQL Server, PostgreSQL, etc.) + frontend (React, Vue, etc.) o stack que prefieras.

Si quieres, el siguiente documento puede ser un **listado de tablas/campos de Datos.mdb** (si se puede extraer con las herramientas disponibles) o un **borrador de requisitos para la versión web** basado en esta investigación.
