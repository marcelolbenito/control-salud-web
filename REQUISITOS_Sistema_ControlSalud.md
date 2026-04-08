# Especificación de requerimientos — Control Salud (réplica web)

Documento vivo: consolida lo inferible del sistema original para que el desarrollo web tenga **criterio claro**. No reemplaza validación con usuarios ni pruebas sobre el ejecutable.

---

## 1. Alcance y fuentes de verdad

| Fuente | Qué aporta al ERS |
|--------|-------------------|
| **Control Salud.exe** (VB6) | Nombres de pantallas y mensajes, fragmentos SQL, flujos implícitos en cadenas, integración conceptual (caja, turnos web, pagos, etc.). |
| **Datos.mdb / Datos_Vacio.mdb** | Esquema real (tablas, tipos, relaciones); reglas de integridad referencial donde existan. |
| **sql/schema_mysql.sql** | Modelo acordado para la web (adaptación MySQL, snake_case donde aplica). |
| **Backup SQL Server (Datos.bak)** | Datos y posible esquema de producción para migración y validación. |
| **Ayuda.chm** | Descripciones funcionales y pasos si están documentados ahí. |
| **Carpeta Imagenes\\** | Tipos de adjunto (certificados, estudios, fotos, odontogramas, gráficos). |
| **Ejecutables satélite** (AgendaWeb, Anunciador, Recordatorios) | Alcance multi-módulo y casos de uso periféricos. |
| **Uso guiado del .exe** (capturas, recorridos) | Comportamiento observable que no aparece en strings. |

**Conclusión:** un ERS **completo en el sentido contractual** (todos los botones, todas las reglas, todos los informes) **no** se obtiene solo extrayendo texto del .exe. Un ERS **suficiente para implementar por fases** sí: combinando **exe + esquema de BD + uso del programa** y dejando explícitos los **vacíos**.

---

## 2. Visión del producto

- **Objetivo:** Sistema clínico/administrativo tipo consultorio: pacientes, profesionales, agenda, órdenes y prácticas, sesiones, pagos, caja, consultas, internación por camas (donde aplique), historia clínica y adjuntos.
- **Contexto:** El producto de referencia es **Control Salud** escritorio (v. ej. 1.84.x); la web en **web/** debe **alinearse** en modelo de datos y flujos, no ser un sistema genérico.
- **Restricción:** El .exe **no** se integra en runtime con la web; solo orienta requisitos.

---

## 3. Actores y stakeholders

| Actor | Rol |
|-------|-----|
| Personal administrativo | Altas, turnos, órdenes, pagos, caja. |
| Profesional / médico | Consultas, agenda propia, órdenes/sesiones asociadas. |
| Sistema (módulos) | Anunciador, recordatorios, posible AgendaWeb (alcance a definir en web). |

*Nota:* perfiles y permisos granulares del .exe deben **inferirse con uso del programa** o pedirse al cliente; no suelen estar completos en strings del exe.

---

## 4. Requerimientos funcionales por dominio

Códigos: `RF-xxx` para trazabilidad. Estado: **C** confirmado por BD/exe, **I** inferido solo texto exe, **P** pendiente validación con usuario o ayuda.

### 4.1 Pacientes e historia clínica

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-PAC-01 | ABM de pacientes; identificación por **NroHC** como clave de negocio principal. | C |
| RF-PAC-02 | Datos personales y clínicos según tabla **Pacientes** (y extensiones en esquema). | C |
| RF-PAC-03 | Adjuntos: fotos, estudios, certificados, gráficos, odontogramas (rutas/carpetas como en **Imagenes\\**). | I |
| RF-PAC-04 | Bloques específicos (ej. enfermedades/operaciones ginecológicas) si existen tablas homónimas en BD. | C |
| RF-PAC-05 | **Búsqueda de pacientes** (listado web): criterios por texto libre (nombre/apellidos/DNI/contactos según columnas), **Nro. HC**, **Nº ID** (clave interna), filtro **activo / todos / inactivos**; enlace a ficha respetando query de retorno cuando aplique. | C (web) |

### 4.2 Profesionales (Lista Doctores)

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-DOC-01 | ABM de profesionales; flags **medicoconvenio**, **bloquearmisconsultas**, sucursales **sucursal1…10**. | C |
| RF-DOC-02 | Filtrado y uso en agenda, órdenes, sesiones y consultas por **iddoctor** / **Doctor**. | C |

### 4.3 Agenda y turnos

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-AGE-01 | Gestión de turnos en **Agenda Turnos** (fecha, NroHC, doctor, vínculo **idorden** donde corresponda). | C |
| RF-AGE-02 | Conceptos mencionados en material del producto: sobreturnos, plantilla de turnos, agenda telefónica, turnos web. | I / P |

### 4.4 Órdenes, sesiones y pagos

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-ORD-01 | Órdenes en **Pacientes Ordenes** (NroPaci, iddoctor, fechas, flags autorizada/entregada, montos, estados OS, prácticas/cobertura según campos del esquema). | C |
| RF-ORD-02 | Pantalla tipo **«Órdenes de los Pacientes»**: filtros avanzados (sucursal, rango id/fecha, médico, cobertura/plan/derivador/práctica), estados de facturación paciente/cobertura (A/F/P), radios Si/No/Todas (pagos paciente y cobertura, entregada, autorizada, IVA), honorarios liquidados con rango de fechas, bloque de filtros por **sesiones**, acciones masivas de liquidación y totales en pie. Ver **Anexo A**. | C (UI exe) / P (-paridad web-) |
| RF-SES-01 | Sesiones en **Pacientes Sesiones** ligadas a **idorden**. | C |
| RF-PAG-01 | Pagos en **Pacientes Pagos** (origen **quien**, importes, fechas, relación orden/paciente). | C |
| RF-PAG-02 | Integraciones / medios citados en investigación (tarjeta, MercadoPago, PayPal): detalle de flujo **P**. | I / P |

### 4.5 Consultas médicas

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-CON-01 | Registro de consultas (**Consultas**, **Consultas Items** u equivalentes en esquema). | C |
| RF-CON-02 | Reglas de ítems, plantillas y cierre de consulta. | P |

### 4.6 Caja y finanzas

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-CAJ-01 | Movimientos de **Caja** (doctor, fechas, importes, cobertura asociada donde aplique). | C |
| RF-CAJ-02 | Informes y arqueos según el .exe. | P |

### 4.7 Internación — camas

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-CAM-01 | **Camas**, **CamasPacientes**, **CamasGastos**, **CamasInsumos** según modelo de datos. | C |
| RF-CAM-02 | Flujos de ingreso/egreso y carga de gastos/insumos. | P |

### 4.8 Catálogos y soporte

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-CAT-01 | Coberturas, prácticas, derivadores, planes — según tablas/listas en **Datos.mdb** y referencias en **Pacientes Ordenes**. | C |
| RF-AUD-01 | Usuarios y login del **sistema web** (tabla **usuarios**); no equivale al login del .exe si existiera. | C (web) |

### 4.9 Módulos satélite (fuera o fase posterior en web)

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-SAT-01 | **Anunciador**: cola visual/sonido de turnos. | P |
| RF-SAT-02 | **Recordatorios** de citas o seguimiento. | P |
| RF-SAT-03 | **AgendaWeb.exe**: alcance de integración o sustitución. | P |

---

## Anexo A — «Órdenes de los Pacientes» (exe, captura 2026-04-08)

*Nota:* la primera captura en el hilo mostraba **órdenes**; otra captura posterior documenta la ficha **«Información del Paciente»** (Anexo B).

### A.1 Filtros — bloque «orden»

| Elemento (exe) | Notas / mapeo tentativo a BD |
|----------------|------------------------------|
| Sucursal | `Pacientes Ordenes.sucursal` |
| Desde / Hasta Nº ID | Rango sobre `Pacientes Ordenes.id` |
| Orden Desde / Hasta Fecha | `Pacientes Ordenes.fecha` |
| Médico | `iddoctor` → `Lista Doctores` |
| Cob. Méd. | `idobrasocial` (+ catálogo coberturas) |
| Plan | `idplan` |
| Derivadores | `idderivado` |
| Práctica | `idpractica` |

### A.2 Estados y flags (facturación / cobertura / radios)

| Elemento (exe) | Notas |
|------------------|--------|
| Estado según facturación: A Facturar (A), Facturadas (F), Pagadas (P) | Prob. `estado` (1 carácter por orden en BD). En web hoy hay filtro por un solo código; en exe parecen **checklist** (combinar varios — validar con uso). |
| Estado según Cob. Médica: A / F / P | Prob. `estado_os`. |
| Órdenes pagadas por el paciente / por la cobertura médica | Puede enlazar con **Pacientes Pagos** o flags/campos no expuestos aún en formulario web; **confirmar** contra `.mdb`. |
| Entregadas / Autorizadas | `entregada`, `autorizada`. |
| Pagan IVA | `pagaiva`. |
| Honorarios liquidados (Si/No/Todas) + fechas Desde/Hasta | `liquidada`, `honorariofecha`. |

### A.3 Filtros — bloque «sesiones»

| Elemento (exe) | Notas |
|----------------|--------|
| Médico de la sesión | Filtrar órdenes que tengan sesiones con ese doctor en **Pacientes Sesiones** (`JOIN` / subconsulta). |
| Sesiones con Nº autorización: Ingresado / No ingresado / Todas | Relacionado con `numeautorizacion` u autorización en **Sesiones** — **validar** esquema Access. |
| Sesiones con honor. liquidados (Si/No/Todas) | Lógica en tablas de sesiones u orden; **validar**. |

### A.4 Acciones (laterales / liquidación)

- Conmutador **Órdenes / Sesiones**; botones **Cambiar médico**, **Liquidar honor.**, **Anular honor.**; opción **No sumar honorarios de órdenes con cero sesiones**.
- Botones de impresión/exportación (**Orden**, **Honorarios**, **Todo**, **Cober.**, **Rec. H**, **Cob. H**, **Cobro/S**, **Exp 1–3**), flujo **A Facturar → Facturar → Pagar**.
- **RF:** la web puede priorizar solo **listado + filtros + totales**; el resto es fase posterior salvo requerimiento explícito.

### A.5 Columnas de la grilla (mapeo a `Pacientes Ordenes` y joins)

| Columna (exe) | Mapeo principal (MySQL / esquema web) |
|----------------|--------------------------------------|
| Fecha Orden | `fecha` |
| Nº Orden | `numero` |
| Práctica | `idpractica` + nombre desde catálogo si existe |
| Nº ID | `id` |
| Paciente | `NroPaci` + `Pacientes.Nombres` (y apellido si hay) |
| E.P | `estado` (estado facturación paciente) |
| $ Costo Paci | `costo` |
| $ Pagó Paci | `pago` |
| $ Debe Paci | Calculado: `costo - pago` (si aplica; revisar redondeo y convención del exe) |
| E.C | `estado_os` |
| $ Costo Cob | `costo_os` |
| $ Pagó Cob / $ Debe Cob | Pueden venir de **Pacientes Pagos** filtrados por orden/quien; **confirmar** con datos reales |
| Sesiones | `sesiones` o `sesionesreali` |
| $ Honorarios | `honorarioextra` y/o sumatoria desde sesiones — **confirmar** |
| Hon. Liq. | `liquidada` (+ `honorariofecha` si aplica) |

### A.6 Pie de pantalla

- **Cantidad de órdenes** y **sumas** de columnas monetarias (costo/pago/debe paciente y cob., total sesiones, total honorarios), alineadas al exe.

### A.7 Brecha respecto a la web actual (`web/`)

- Faltan en UI/API muchos filtros del Anexo (IVA, pagos paciente/cobertura, multi-estado A/F/P como en exe, bloque sesiones, fechas de liquidación, totales).
- La grilla web debe aproximar las columnas del Anexo (incl. **debe** calculado y nombre de **práctica** si hay lista).

---

## Anexo B — «Información del Paciente» y búsqueda (exe + web)

### B.1 Ficha «Información del Paciente» (captura 2026-04-08)

Ventana central del exe con **barra de acciones**: Nuevo, Guardar, Imprimir, Ordenes, Archivo, Certificados, Presupuestos.

**Cabecera / identificación**

| Elemento (exe) | Notas |
|----------------|--------|
| Nº ID | Clave interna del registro paciente (`pacientes.id` en web). |
| Nº His. Cli. | **NroHC** (historia clínica). |
| Fecha de Alta | Campo de alta (en Access puede ser distinto de `creado_en` web). |
| Última Consulta | P. ej. `ultima_cons` / última consulta registrada. |
| Referido por | `referente` u otro (validar en `.mdb`). |
| Paciente cautivo | Flag (ubicar columna Access). |
| Motivo | Texto motivo / derivación. |
| Próximo Turno | Calculado desde **Agenda** o campo persistido. |
| Foto | Adjunto / webcam; carpeta **Imagenes** en exe. |

**Pestañas:** Paciente (activa), Demográfico, Familia, Contacto, Notas Importantes.

**Pestaña Paciente (campos visibles):** Apellido, 2º Apellido, Nombre; estado civil; fecha nacimiento; edad; sexo; cobertura médica (tiene cob., paga IVA, cobertura, plan, Nº afiliado); identidad de género; orientación sexual; tipo y Nº documento; dirección; e-mail; ocupación; derivado por; teléfono celular, particular, laboral; pie «Tiene Convenio»; contraseña módulo web informes.

**Columna lateral «PRINCIPAL»:** accesos por especialidad (General, Pediatría, etc.) y módulos clínicos (Consultas, Antecedentes, Signos Vitales, Estudios, Vacunas, Cirugías, Historia Clínica).

**RF-B.1:** La web debe **aproximar** esta ficha ampliando `paciente_form.php` y vistas relacionadas según `migration_002_pacientes_campos_exe.sql` / columnas reales importadas; la lateral se traduce en **enlaces** a mismos módulos cuando existan en web.

### B.2 Listado / búsqueda en exe vs web

| Aspecto | Exe (referencia) | Web (`pacientes.php`) |
|---------|------------------|------------------------|
| Criterios | Depende de ventana de búsqueda / listado del exe (no capturada aquí). | Texto **q**, **nrohc**, **id**, **activo**. |
| Columnas | — | Nº ID, Nro HC, nombre, DNI, cobertura (si hay lista), tel, email, activo. |

---

## 5. Reglas de datos y coherencia con el original

- Los **nombres de tablas y campos** del modelo Access/SQL Server son referencia; en MySQL web se documentan en **sql/schema_mysql.sql** y migraciones.
- Claves de enlace frecuentes: **NroHC**, **iddoctor**, **idorden**, IDs de cobertura/práctica/plan según columnas de **Pacientes Ordenes**.
- Cualquier pantalla o campo nuevo debe **contrastarse** con exe + esquema antes de darse por cerrado.

---

## 6. Requerimientos no funcionales (borrador)

| ID | Descripción |
|----|-------------|
| RNF-01 | **Trazabilidad:** cada RF prioritario debe enlazarse a tabla/pantalla del exe o a script SQL de referencia. |
| RNF-02 | **Seguridad web:** autenticación, sesión, autorización por rol (detalle pendiente según negocio). |
| RNF-03 | **Rendimiento:** listados con filtros (como órdenes/agenda) acotados o paginados para no degradar uso clínico. |
| RNF-04 | **Respaldo y auditoría:** política de backup de BD y, si aplica, log de cambios críticos (definir con cliente). |

---

## 7. Supuestos y riesgos

| Tipo | Texto |
|------|--------|
| Supuesto | El cliente puede ejecutar el .exe o proveer capturas para cerrar ítems **P**. |
| Supuesto | **Datos.mdb** o backup SQL está disponible para contrastar tipos y campos no visibles en strings. |
| Riesgo | Decompilar VB6 daría más lógica pero introduce incertidumbre legal/técnica; no es requisito para el ERS si hay BD + uso. |
| Riesgo | Funcionalidades ocultas tras flags de configuración (**ruta.dat** / **setup.dat**) pueden no aparecer en una instalación de prueba. |

---

## 8. Plan para “completar” el ERS

1. **Inventario de pantallas:** recorrido del .exe con lista de ventanas → tabla (nombre, módulo, tablas tocadas).
2. **Cruce con BD:** por cada pantalla, campos leídos/escritos (desde Access o desde trazas).
3. **Ayuda .chm:** exportar índice y temas a checklist de RF.
4. **Priorización MoSCoW** con el cliente para la primera release web.
5. **Casos de prueba** aceptados derivados del comportamiento observable del .exe.

---

## 9. Referencias en este repositorio

- `INVESTIGACION_ControlSalud.md` — entorno, módulos, dependencias.
- `ESTRUCTURA_y_CREDENCIALES_para_WEB.md` — tablas/campos desde cadenas del exe.
- `sql/schema_mysql.sql` — modelo implementado para la web.
- `web/` — implementación actual (no todo el RF de este documento está cubierto aún).
- `.cursor/rules/control-salud-referencia-exe.mdc` — reglas de trabajo para el asistente.

---

## 10. Control de versiones del documento

| Versión | Fecha | Cambio |
|---------|-------|--------|
| 0.1 | 2026-04-08 | Creación inicial: estructura ERS + RFs por dominio + fuentes y vacíos. |
| 0.2 | 2026-04-08 | Anexo A: pantalla «Órdenes de los Pacientes» (captura); RF-ORD-02; mapeo columnas/filtros. |
| 0.3 | 2026-04-08 | Anexo B: ficha «Información del Paciente»; RF-PAC-05 búsqueda listado web. |
