# Error 3704 – Conexión con la base de datos

## Qué significa el error 3704

En VB6/ADO el **error 3704** es: *"Operation is not allowed when the object is closed"* (la operación no está permitida cuando el objeto está cerrado).

En la práctica suele indicar que **la conexión a la base de datos no se abrió bien** y la aplicación intenta usarla como si estuviera abierta. La causa real suele ser una de las siguientes.

---

## Checklist de soluciones (en orden)

### 1. Instalar el motor de Access (32 bits)

Control Salud es VB6 (32 bits). Para usar archivos `.mdb` hace falta el **Microsoft Access Database Engine en versión 32 bits (x86)**.

- Si solo tienes instalado el motor en 64 bits, la app 32 bits **no** lo ve y falla al conectar → aparece 3704.
- **Qué hacer:** Instalar el redistribuible **x86** (32 bits):
  - https://www.microsoft.com/en-us/download/details.aspx?id=54920
  - Descargar e instalar **AccessDatabaseEngine.exe** (no el _X64).

Si ya tienes Office 64 bits, a veces el instalador x86 se queja. En ese caso:
- Instalar en modo “solo para el usuario actual”, o
- Usar la instalación pasiva:  
  `AccessDatabaseEngine.exe /passive`

---

### 2. Ruta correcta al archivo de base de datos

La aplicación usa la ruta que tiene guardada en `ruta.dat` o `setup.dat`.

- El archivo `Datos.mdb` debe estar en: **`C:\Control Salud\Datos.mdb`** (o en la ruta que la aplicación espere).
- Si moviste la carpeta del programa, la ruta guardada puede ser antigua y ya no válida.
- **Qué hacer:** Asegurarse de que la aplicación está en una ruta fija (por ejemplo `C:\Control Salud`) y que `Datos.mdb` está en esa misma carpeta. Si la instalación está en otra unidad o carpeta, puede que haya que reconfigurar (o reinstalar) para que la ruta en los .dat sea la correcta.

---

### 3. Ningún otro programa con la base abierta

Si otro proceso tiene abierto `Datos.mdb`, la conexión puede fallar y luego dar 3704.

- **Qué hacer:** Cerrar:
  - Otras ventanas de Control Salud.
  - Microsoft Access si está abierto con ese .mdb.
  - Cualquier otra herramienta que use ese archivo.
- Reiniciar el PC si no estás seguro y volver a abrir solo Control Salud.

---

### 4. Permisos y solo lectura

- **Qué hacer:** Comprobar que la carpeta `C:\Control Salud` (y `Datos.mdb`) no estén en “solo lectura” y que tu usuario tenga permisos de lectura y escritura.
- Clic derecho en la carpeta → Propiedades → desmarcar “Solo lectura” si está marcado (y aplicar a subcarpetas si aplica).

---

### 5. Probar con la base vacía (opcional)

Para descartar que el .mdb esté dañado:

- Hacer una **copia de seguridad** de `Datos.mdb`.
- Copiar `Datos_Vacio.mdb` y renombrar la copia a `Datos.mdb` (o renombrar el actual y dejar solo la copia vacía con el nombre `Datos.mdb`).
- Abrir Control Salud de nuevo.

Si con la base vacía **no** da 3704, el problema puede ser el archivo de datos original (corrupto o formato distinto). Si **sí** sigue dando 3704, el problema es de instalación del motor o de ruta (puntos 1 y 2).

---

## Resumen rápido

| Causa más frecuente | Solución |
|---------------------|----------|
| Motor de Access no instalado o solo 64 bits | Instalar **Access Database Engine 2016 (32 bits)** desde el enlace de arriba. |
| Ruta incorrecta al .mdb | Dejar la app y `Datos.mdb` en la misma carpeta (ej. `C:\Control Salud`) y no moverla. |
| Archivo en uso o bloqueado | Cerrar Access y otras instancias de Control Salud; reiniciar si hace falta. |
| Permisos / solo lectura | Dar permisos de lectura/escritura a la carpeta y desmarcar “Solo lectura”. |

Después de instalar el motor **32 bits** y comprobar la ruta y los permisos, prueba de nuevo. Si el 3704 continúa, indica en qué momento exacto aparece (al abrir la aplicación, al entrar a un módulo concreto, etc.) para afinar el diagnóstico.
