# Camino a seguir: versión web con PHP (basada en Control Salud)

Plan para replicar la funcionalidad del .exe en una aplicación web con **PHP**, usando la estructura que ya tenemos (tablas y campos del MDB + lo extraído del ejecutable).

---

## 1. Qué nos dio el .exe (sin descompilar)

Del análisis del **Control Salud.exe** sabemos:

- **Tablas y relaciones:** Pacientes, Agenda Turnos, Lista Doctores, Pacientes Ordenes, Pacientes Sesiones, Pacientes Pagos, Caja, Consultas, Camas, etc.
- **Campos clave:** NroHC, idorden, iddoctor, importe, fecha, autorizada, entregada, convenio, sucursal1..sucursal10.
- **Flujos:** turnos por doctor/fecha, pagos por paciente/orden, caja por doctor, órdenes autorizadas/entregadas, sesiones, múltiples sucursales.
- **Permisos/lógica:** accesocaja, accesoordenes, accesoagenda, bloquearmisconsultas, medicoconvenio, etc. (ideas para roles en la web).
- **Extras:** certificados, estudios (PDF), fotos, odontogramas, honorarios, tarjeta débito/crédito.

Con eso alcanza para definir **módulos** y **esquema de BD** sin tocar más el .exe.

---

## 2. Stack sugerido (PHP)

| Capa      | Tecnología recomendada |
|-----------|-------------------------|
| Backend   | **PHP 8.x** (o 7.4+)    |
| Base de datos | **MySQL 8** o **MariaDB** (o PostgreSQL si preferís) |
| Conexión BD | **PDO** (evitar mysql_*) |
| Frontend  | HTML + CSS + **JavaScript** (vanilla o Alpine.js / Vue ligero); opcional: Bootstrap o Tailwind |
| Autenticación | Sesiones PHP + tabla `usuarios`; opcional: JWT para API |
| Servidor  | Apache o Nginx + PHP; en local: XAMPP, Laragon, o PHP built-in server |

Alternativa: **Laravel** (PHP) si querés framework (auth, ORM, rutas ya resueltas).

---

## 3. Módulos a desarrollar (orden sugerido)

El “camino” que marca el .exe y la estructura del MDB:

| Orden | Módulo | Descripción | Tablas principales |
|-------|--------|-------------|--------------------|
| 1 | **Config / Usuarios** | Login, roles, configuración básica | usuarios, roles, config |
| 2 | **Lista Doctores** | ABM de médicos, sucursales donde atienden | lista_doctores |
| 3 | **Pacientes** | Alta, edición, búsqueda por NroHC/DNI/nombre | pacientes |
| 4 | **Agenda / Turnos** | Turnos por doctor y fecha, sobreturnos, “no se atiende” | agenda_turnos |
| 5 | **Órdenes** | Órdenes por paciente (estudios/prácticas), autorizada, entregada | pacientes_ordenes |
| 6 | **Sesiones** | Sesiones por orden (kinesio, etc.) | pacientes_sesiones |
| 7 | **Pagos** | Pagos de pacientes/coberturas, importe, fecha | pacientes_pagos |
| 8 | **Caja** | Caja por doctor/fecha, honorarios, resumen | caja |
| 9 | **Consultas** | Historial de consultas por paciente/doctor | consultas, consultas_items |
| 10 | **Extras** | Camas (si aplica), certificados, estudios, fotos | camas, camas_pacientes, archivos |

Podés acortar el alcance: por ejemplo **Pacientes + Doctores + Agenda Turnos + Pagos** ya dan un núcleo muy parecido al exe.

---

## 4. Estructura de carpetas PHP (ejemplo)

```
control-salud-web/
├── config/
│   └── database.php      # Conexión PDO
├── public/
│   ├── index.php         # Entrada (login o dashboard)
│   ├── css/
│   └── js/
├── src/
│   ├── Auth.php
│   ├── Pacientes.php
│   ├── Doctores.php
│   ├── Turnos.php
│   ├── Ordenes.php
│   ├── Pagos.php
│   └── Caja.php
├── templates/             # O views/
│   ├── layout.php
│   ├── pacientes/
│   ├── turnos/
│   └── ...
├── sql/
│   └── schema.sql        # Creación de tablas (incluido en este proyecto)
└── .env                  # DB_HOST, DB_NAME, DB_USER, DB_PASS
```

---

## 5. Base de datos: esquema inicial

En la carpeta del proyecto hay un archivo **`sql/schema_mysql.sql`** con:

- Tablas equivalentes a: Pacientes, Lista Doctores, Agenda Turnos, Pacientes Ordenes, Pacientes Sesiones, Pacientes Pagos, Caja, Consultas, etc.
- Nombres en **snake_case** para PHP/MySQL (ej. `agenda_turnos`, `lista_doctores`).
- Campos con tipos razonables (INT, VARCHAR, DECIMAL, DATE, DATETIME).
- Claves primarias y algunas claves foráneas para no perder las relaciones que vimos en el .exe.

Podés importarlo en MySQL/MariaDB y ajustar después (índices, más campos) según lo que veas en el visor MDB.

---

## 6. Próximos pasos concretos

1. **Crear la base en MySQL** e importar `sql/schema_mysql.sql`.
2. **Configurar PHP** (PDO) en `config/database.php` con tu usuario/contraseña de MySQL.
3. **Implementar login** y una pantalla de inicio (dashboard) aunque sea simple.
4. **Desarrollar en este orden:** Doctores → Pacientes → Turnos → Pagos (y después Órdenes, Sesiones, Caja).
5. **Revisar en el visor MDB** los nombres exactos de columnas en las tablas que ya abriste, y alinear el esquema SQL si hace falta.

El .exe ya no hace falta para seguir: la “guía” es esta estructura de módulos + el esquema SQL + las relaciones que sacamos de las consultas del ejecutable. Si más adelante querés afinar tipos o campos, podés comparar con las tablas en el MDB (Datos o Datos_Vacio).
