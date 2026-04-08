# Contraseña de la base de datos Datos.mdb

Al abrir **Datos.mdb** con Access (o con cualquier herramienta que pida la contraseña de la base de datos), probá en este orden:

---

## Contraseñas a probar

1. **control333**  
   Aparece dentro del ejecutable **Control Salud.exe** como cadena de texto. Es muy habitual que en aplicaciones VB6 la contraseña de la base Access esté guardada así en el .exe.

2. **123456**  
   En el archivo **ruta.dat** hay referencia a usuario `sa` y contraseña `123456` para SQL Server. A veces se reutiliza la misma contraseña para el .mdb.

3. **control**  
   Por si solo usaron la parte corta del nombre.

4. **Vacía**  
   Dejar el campo en blanco y aceptar (algunos .mdb no tienen contraseña y el cuadro aparece igual).

---

## Cómo probar en Microsoft Access

1. Abrí Microsoft Access.
2. Archivo → Abrir → elegí `C:\Control Salud\Datos.mdb`.
3. Cuando pida **contraseña de la base de datos**, ingresá primero **control333**.
4. Si no funciona, cerrá y probá **123456**, **control** o vacío.

---

## Si ninguna funciona

- La contraseña puede haber sido cambiada por quien instaló el sistema.
- Podés preguntar al administrador o al que usaba Control Salud en ese equipo.
- Como último recurso existen herramientas de recuperación de contraseña para .mdb (por ejemplo “Access Password Recovery” o similares), que prueban muchas combinaciones; usarlas depende de que tengas derecho sobre los datos.

**Recomendación:** probar primero **control333**.
