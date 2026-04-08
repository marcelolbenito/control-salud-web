# Si falla la instalación del Access Database Engine 2016

El instalador del paquete suele **fallar si ya tienes Office instalado** (32 o 64 bits): Microsoft evita instalar la versión “contraria” del motor y el programa de instalación se cierra con error.

---

## Opción 1: Instalar por línea de comandos (probar primero)

Esto suele saltarse la comprobación que bloquea la instalación.

1. **Descargar** el instalador desde:  
   https://www.microsoft.com/en-us/download/details.aspx?id=54920  
   - Para Control Salud (VB6, 32 bits): **AccessDatabaseEngine.exe** (32 bits).  
   - No uses el que dice _X64.

2. **Abrir Símbolo del sistema (CMD) como administrador:**  
   - Buscar “cmd” o “símbolo del sistema”.  
   - Clic derecho → “Ejecutar como administrador”.

3. **Ir a la carpeta de descargas** (cambia `TuUsuario` por tu nombre de usuario):
   ```
   cd C:\Users\TuUsuario\Downloads
   ```

4. **Ejecutar el instalador en modo silencioso:**
   ```
   AccessDatabaseEngine.exe /passive
   ```
   Si no funciona, probar:
   ```
   AccessDatabaseEngine.exe /quiet
   ```

5. Esperar a que termine (puede tardar 1–2 minutos). Reiniciar el PC y probar de nuevo Control Salud.

---

## Opción 2: Microsoft 365 Access Runtime (alternativa oficial)

Incluye el motor de base de datos y a veces instala sin el conflicto del paquete 2016.

1. Ir a:  
   **https://support.microsoft.com/en-us/office/download-and-install-microsoft-365-access-runtime-185c5a32-8ba9-491e-ac76-91cbe3ea09c9**

2. En la página, elegir **32 bits (x86)** para que funcione con Control Salud (VB6).

3. Descargar e instalar siguiendo los pasos de la web.

4. Reiniciar y probar Control Salud.

---

## Opción 3: Access Database Engine 2010 (alternativa antigua)

Si las opciones 1 y 2 siguen fallando, se puede probar el motor 2010 (sigue sirviendo para archivos .mdb).

- Enlace de archivo (Legacy Update):  
  https://legacyupdate.net/download-center/download/13255/microsoft-access-database-engine-2010-redistributable  
- Descargar la versión **32 bits** si usas Control Salud en 32 bits.  
- Instalar; si da conflicto, probar también desde CMD como administrador:  
  `AccessDatabaseEngine.exe /passive`

---

## Resumen

| Orden | Qué hacer |
|-------|-----------|
| 1 | Instalar **AccessDatabaseEngine.exe** (32 bits) con **`/passive`** desde CMD como administrador. |
| 2 | Si sigue fallando: instalar **Microsoft 365 Access Runtime** (32 bits) desde el enlace de Soporte de Microsoft. |
| 3 | Si aún falla: probar **Access Database Engine 2010** (32 bits) desde Legacy Update. |

La causa más común del fallo es tener Office (32 o 64 bits) y que el instalador normal rechace el motor. Usar **/passive** o **/quiet** desde CMD suele solucionarlo.
