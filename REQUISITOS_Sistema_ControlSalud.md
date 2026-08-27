# Especificación de requerimientos — Control Salud (réplica web)

Documento vivo: consolida lo inferible del sistema original para que el desarrollo web tenga **criterio claro**. No reemplaza validación con usuarios ni pruebas sobre el ejecutable.

**Fuente unica de seguimiento:** este archivo es la hoja de ruta maestra del proyecto (paridad con el exe + orden tecnico de implementacion). Los checklists de otros documentos se consideran referencia historica.

## Como usar este documento semanalmente

1. Revisar `6.1 Checklist maestro unificado` y actualizar cada item como `[x]`, `[~]` o `[ ]`.
2. Priorizar primero pendientes de seguridad/estabilidad (seccion B), luego paridad funcional (seccion A).
3. Ejecutar una validacion corta del flujo minimo: login -> paciente -> turno -> orden -> pago -> caja.
4. Registrar cambios de alcance o decisiones de negocio en el RF correspondiente (seccion 4 y anexos).
5. Cerrar la semana actualizando `Control de versiones del documento` con fecha y resumen breve.

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

## 3.1 Procesos operativos reales del centro (levantamiento usuario)

Esta seccion traduce el circuito operativo informado por el usuario a procesos del sistema. Sirve para ordenar lo ya implementado y detectar brechas.

### P-REC-01 — Recepcion de paciente particular

**Flujo esperado:**

1. El paciente llega y se anuncia en secretaria.
2. Si es particular, se cobra la consulta/practica.
3. Se registra el pago en el sistema con medio de pago.
4. Se marca el turno como `llego`.
5. El profesional ve al paciente en sala desde su agenda.
6. El profesional llama al paciente por pantalla/anunciador.
7. Luego se marca como `atendido`.

**Estado actual web:** **Alto / operativo inicial**.

- Implementado: agenda diaria, marca `llego`, anunciador, cierre `atendido`, pagos, recibo, caja y acceso `Cobro / Orden` desde el turno.
- Implementado: recepcion guiada permite registrar pago particular y marcar `llego` solo si corresponde.
- Pendiente: validar si toda consulta particular debe generar orden/practica o si algunos casos quedan como pago directo.

### P-REC-02 — Recepcion de paciente con obra social

**Flujo esperado:**

1. El paciente llega y se anuncia en secretaria.
2. Secretaria gestiona autorizacion.
3. Se carga la orden/practica en el sistema.
4. La orden queda disponible para facturacion posterior.
5. Se marca el turno como `llego`.
6. El profesional ve al paciente y lo llama por pantalla/anunciador.

**Estado actual web:** **Alto / operativo inicial**.

- Implementado: ordenes, estados, cobertura/plan/practica, autorizada, entregada, filtros avanzados, agenda y anunciador.
- Implementado: `Cobro / Orden` abre la orden completa prellenada desde el turno y evita duplicar orden si ya existe una vinculada.
- Implementado: la orden muestra datos propios del paciente (obra social, plan, afiliado) y puede buscar arancel por obra social + practica.
- Pendiente: definir obligatoriedad de campos de autorizacion segun obra social.

### P-TUR-01 — Solicitud y confirmacion de turno

**Flujo esperado:**

1. El paciente solicita turno por WhatsApp, presencialmente o portal web.
2. Secretaria o el portal agenda el turno.
3. Al confirmarse el turno, el paciente recibe confirmacion con fecha, hora y profesional.

**Estado actual web:** **Parcial / MVP web**.

- Implementado: agenda interna, alta/edicion de turnos y Agenda Web MVP con ingreso por documento para reservar turnos disponibles.
- Documentado: recordatorios WhatsApp (RF-SAT-02) y Agenda web (RF-SAT-03).
- Falta implementar envio automatico de confirmacion al crear turno.
- Falta definir politicas de cancelacion/reprogramacion y confirmaciones al paciente.

### P-REC-03 — Recordatorio de turno

**Flujo esperado:**

1. El dia anterior al turno se envia recordatorio.
2. El paciente puede confirmar o cancelar.
3. La respuesta actualiza el estado operativo del turno.

**Estado actual web:** **Documentado / pendiente implementacion**.

- Documentado: checklist Twilio, diccionario `agenda_recordatorios` y borrador SQL.
- Falta implementar proveedor, scheduler, webhook, bandeja y reflejo en agenda.

### P-FAC-01 — Facturacion a obra social

**Flujo esperado:**

1. Dia a dia se cargan consultas/ordenes al recepcionar.
2. Al momento de facturar, se emite reporte por obra social y rango de fechas.
3. El reporte se imprime y se anexa a ordenes/autorizaciones.
4. Las ordenes incluidas se marcan como `FACTURADAS` para no mezclarlas en futuras facturaciones.

**Reporte requerido:**

- Afiliado.
- Nombre del paciente.
- Codigo de practica.
- Nombre de practica.
- Fecha.
- Cantidad.
- Costo de practica.

**Estado actual web:** **Parcial / base ampliada**.

- Implementado: ordenes, filtros por cobertura/obra social/plan/practica/fecha/doctor, estados `A/F/P` en orden y cobertura, totales.
- Implementado: listado operativo con fecha, practica, obra social, paciente, nro afiliado, profesional y monto obra social.
- Implementado: arancel inicial desde `lista_precios` / `Lista Precios` por obra social + practica + plan.
- Falta pantalla/proceso especifico de "facturar lote" por obra social.
- Falta reporte con columnas exactas del usuario y accion masiva "marcar facturadas".
- Falta definir si `estado_os = F` sera el campo oficial para `FACTURADA` por obra social.

### P-CAJ-01 — Caja diaria

**Flujo esperado:**

1. La caja toma todos los pagos del dia.
2. Se pueden cargar ingresos y egresos manuales.
3. El sistema suma/resta automaticamente.
4. Al final del dia se controla y se cierra la caja.
5. Al registrar pago se indica medio: efectivo, debito, transferencia o electronico.
6. Se diferencia turno manana / turno tarde.

**Estado actual web:** **Alto / cierre inicial**.

- Implementado: pagos con `forma_pago`, caja con ingresos/egresos manuales, importes y fechas.
- Implementado: cierre de caja por fecha/turno, detalle de movimientos y movimientos inmutables con correccion por contra movimiento.
- Implementado parcialmente: `turnocaja` existe como texto operativo.
- Falta normalizar "turno manana/tarde" como campo controlado para pagos/caja.
- Falta reporte diario de cierre con totales por medio de pago y turno.
- Falta definir bloqueo operativo de nuevos movimientos cuando caja/turno ya fue cerrado.

### Priorizacion sugerida desde estos procesos

1. **Facturacion obra social:** reporte por obra social + marcar ordenes facturadas.
2. **Caja diaria v2:** turno manana/tarde controlado, totales por medio de pago y bloqueo tras cierre.
3. **Confirmacion/recordatorio WhatsApp:** confirmacion al crear turno + recordatorio dia anterior.
4. **Agenda Web v2:** cancelacion/reprogramacion y reglas de disponibilidad mas finas.
5. **Modulos clinicos mayores:** consultas medicas e internacion/camas.

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

**Checklist de validación con el centro:** ver `CHECKLIST_AGENDA_PARIDAD.md` (marcar qué funciones del exe siguen en uso y priorizar pendientes web).

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
| RF-INT-01 | **Chat interno** entre usuarios del sistema (secretarías, médicos, laboratorio): mensajes 1:1, broadcast «Todos los usuarios» y destinos especiales; permiso `accesochat` en `Lista Doctores`; historial legacy en campo `anuncio`; sonido `chat.wav`. Integrado en el **exe principal** (no es satélite). | C (exe/BD) / P (web) |

### 4.9 Módulos satélite (fuera o fase posterior en web)

| ID | Descripción | Estado |
|----|-------------|--------|
| RF-SAT-01 | **Anunciador**: cola visual/sonido de turnos. | C (v1 web) |
| RF-SAT-02 | **Recordatorios** de citas o seguimiento. | P |
| RF-SAT-03 | **AgendaWeb.exe**: alcance de integración o sustitución. | P |

---

### 4.10 Analisis funcional inicial de modulos satelite (documentacion)

Objetivo de esta seccion: capturar comportamiento esperado para luego incorporarlo como modulos internos de `web/` (sin depender de ejecutables separados).

#### RF-SAT-01 — Anunciador (sala de espera)

**Hipotesis funcional inicial:**

- Mostrar cola de turnos del dia por profesional/sucursal.
- Permitir cambiar estado operativo de turno (ej. en espera -> llamando -> atendido/anulado, nombres a validar).
- Emitir aviso visual y opcional de sonido al llamar.
- Exponer una pantalla de alto contraste para TV/monitor de sala.

**Datos y entidades candidatas:**

- `agenda_turnos` (fecha, doctor, estado, paciente/HC).
- `lista_doctores`, `lista_sucursales` (si aplica filtro por sede).
- Configuracion visual/sonora en `config` (claves nuevas a definir).

**Brechas a validar con uso real/exe:**

- Reglas exactas de orden de llamado (hora, prioridad, sobreturno).
- Estados exactos y transiciones permitidas.
- Si requiere distinguir "llamado actual" vs "historial de llamados".

**Definicion operativa acordada (v1):**

- El profesional, desde su usuario, opera sobre sus turnos del dia.
- La accion de llamado se habilita para turnos marcados como `llego`.
- Cada llamado genera/actualiza un item visible en una URL de sala (anunciador).
- La pantalla de sala muestra paciente + consultorio destino + profesional (formato final a definir por privacidad).

**Estados propuestos para v1 (anunciador):**

1. `llego` (paciente presente en recepcion; listo para ser llamado).
2. `llamando` (publicado en pantalla de sala).
3. `en_consultorio` (retirado de cola principal, opcionalmente visible en historial corto).
4. `finalizado` (atendido/cerrado, fuera de anunciador).

**Transiciones minimas:**

- `llego` -> `llamando` (accion del profesional o recepcion habilitada).
- `llamando` -> `en_consultorio` (cuando ingresa al consultorio).
- `en_consultorio` -> `finalizado` (cierre de atencion).
- `llamando` -> `finalizado` (atajo permitido para resolver casos sin paso intermedio).

**Acciones por rol (v1):**

- `doctor`: llamar paciente propio (`llego` -> `llamando`) y avanzar estado de sus llamados.
- `admin_clinica` / recepcion: marcar llegada (`llego`) y asistir gestion de cola.
- `superadmin`: mismas acciones + soporte/auditoria.

**Vistas/URLs previstas (v1):**

- Vista operativa (profesional): integrada en agenda del dia.
- Vista publica de sala: URL dedicada de solo lectura (pantalla fullscreen).
- Actualizacion de la vista de sala: polling corto (3-5 segundos) en v1; websocket como mejora posterior.

**Privacidad sugerida para pantalla de sala (v1):**

- Evitar exponer DNI, telefono u otros datos sensibles.
- Preferir nombre acotado (ej. "Juan P.") o identificador interno acordado por la clinica.

**Decision de infraestructura (monitor):**

- No requiere ejecutable separado.
- Requiere un dispositivo que mantenga abierta la URL de sala: PC secundaria, smart TV/box o segundo monitor.
- Puede ser la misma PC con doble pantalla, si operativamente es viable.

**Datos minimos a persistir (si no alcanzan campos actuales):**

- Tabla sugerida: `agenda_llamados` (id_turno, id_doctor, consultorio, estado_llamado, llamado_en, actualizado_en, id_usuario_accion).
- Alternativa: extender `agenda_turnos` con campos de llamado si se prioriza simplicidad inicial.

**Diccionario propuesto — tabla `agenda_llamados` (v1):**

| Campo | Tipo sugerido | Requerido | Descripcion |
|------|----------------|-----------|-------------|
| `id` | BIGINT PK AI | Si | Identificador tecnico del evento de llamado. |
| `id_clinica` | INT | Si | Scope multi-clinica (alineado al resto del sistema). |
| `id_turno` | BIGINT | Si | Referencia al turno en `agenda_turnos`. |
| `id_doctor` | INT | Si | Profesional asociado al llamado. |
| `nro_hc` | INT | Si | HC del paciente para lookup rapido y trazabilidad. |
| `paciente_display` | VARCHAR(120) | Si | Texto mostrado en sala (ej. "Juan P."). |
| `consultorio` | VARCHAR(40) | Si | Destino visible en monitor (ej. "Consultorio 3"). |
| `estado_llamado` | VARCHAR(24) | Si | `llego`, `llamando`, `en_consultorio`, `finalizado`. |
| `prioridad` | TINYINT | No | Prioridad para cola (default 0). |
| `origen_accion` | VARCHAR(24) | Si | `doctor`, `recepcion`, `sistema`. |
| `id_usuario_accion` | INT | No | Usuario web que ejecuta la ultima accion. |
| `llamado_en` | DATETIME | No | Fecha/hora del paso a `llamando`. |
| `en_consultorio_en` | DATETIME | No | Fecha/hora del paso a `en_consultorio`. |
| `finalizado_en` | DATETIME | No | Fecha/hora del cierre de llamado. |
| `observaciones` | VARCHAR(255) | No | Nota corta operativa (opcional). |
| `creado_en` | DATETIME | Si | Alta del registro. |
| `actualizado_en` | DATETIME | Si | Ultima actualizacion del registro. |

**Indices minimos sugeridos (v1):**

- `(id_clinica, estado_llamado, actualizado_en DESC)` para pantalla de sala.
- `(id_turno)` para buscar por turno.
- `(id_doctor, estado_llamado, creado_en DESC)` para cola del profesional.
- `(nro_hc, creado_en DESC)` para auditoria por paciente.

**Reglas de integridad y operacion (v1):**

1. Un turno activo no debe tener mas de un llamado abierto en estado `llamando` o `en_consultorio`.
2. `llamado_en` se completa al pasar a `llamando`; `en_consultorio_en` y `finalizado_en` en sus transiciones respectivas.
3. Si se re-llama un paciente, se permite nuevo registro o reuso controlado del mismo (decision final de implementacion).
4. `paciente_display` se guarda materializado para preservar historial aun si cambia el nombre en ficha.
5. Toda accion de cambio de estado debe registrar `id_usuario_accion` cuando exista usuario autenticado.

**Consulta base esperada para monitor de sala (v1):**

- Mostrar llamados de la clinica en estado `llamando`, ordenados por `prioridad` DESC y `llamado_en` DESC.
- Opcional: segundo bloque "En consultorio" con ultimos N ingresos.

**Borrador de migracion SQL (revision funcional, no ejecutado):**

```sql
-- Propuesta: sql/migration_030_agenda_llamados.sql
CREATE TABLE IF NOT EXISTS agenda_llamados (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_clinica INT NOT NULL,
    id_turno BIGINT UNSIGNED NOT NULL,
    id_doctor INT NOT NULL,
    nro_hc INT NOT NULL,
    paciente_display VARCHAR(120) NOT NULL,
    consultorio VARCHAR(40) NOT NULL,
    estado_llamado VARCHAR(24) NOT NULL DEFAULT 'llego',
    prioridad TINYINT NOT NULL DEFAULT 0,
    origen_accion VARCHAR(24) NOT NULL DEFAULT 'doctor',
    id_usuario_accion INT NULL,
    llamado_en DATETIME NULL,
    en_consultorio_en DATETIME NULL,
    finalizado_en DATETIME NULL,
    observaciones VARCHAR(255) NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_llamados_clinica_estado_actualizado (id_clinica, estado_llamado, actualizado_en),
    KEY idx_llamados_turno (id_turno),
    KEY idx_llamados_doctor_estado_creado (id_doctor, estado_llamado, creado_en),
    KEY idx_llamados_hc_creado (nro_hc, creado_en),
    CONSTRAINT chk_estado_llamado
        CHECK (estado_llamado IN ('llego', 'llamando', 'en_consultorio', 'finalizado'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notas de revision del borrador SQL:**

- Ajustar tipos (`INT`/`BIGINT`) segun PK real de `agenda_turnos` en el esquema vigente.
- Si el motor MySQL objetivo no aplica `CHECK` de forma estricta, reforzar validacion en aplicacion.
- Definir luego FKs segun estado real del esquema (`agenda_turnos`, `lista_doctores`, `usuarios`), priorizando compatibilidad con datos legacy.

**Criterio de cerrado (MVP anunciador):**

1. Profesional puede llamar desde agenda a paciente en estado `llego`.
2. La pantalla de sala refleja el llamado en <= 5 segundos.
3. Se visualiza consultorio destino.
4. Se puede avanzar a `en_consultorio` y `finalizado`.
5. Se registra quien realizo la accion y cuando (trazabilidad minima).

**Estado actual implementado (2026-04-30):**

- `agenda_llamados` creado por migracion `sql/migration_030_agenda_llamados.sql`.
- Integracion en agenda: `llamar` solo para turnos en `llego`; `atendido` disponible tras llamado.
- Sincronizacion operativa: al marcar `atendido` en agenda se finaliza llamado activo en anunciador.
- Vista monitor pura disponible en `/anunciador.php` (sin menu ni botones; solo paciente/consultorio/profesional).
- Vista operador en `/anunciador.php?modo=operador` para gestionar estados de llamado.
- Flujo validado en servidor web con carga por SSH de archivos y migracion aplicada.

**Pendientes para v2 (no bloqueantes de v1):**

- Notificacion sonora configurable por clinica/consultorio.
- Actualizacion en tiempo real por websocket (hoy polling con refresh).
- Reglas avanzadas de prioridad/cola por especialidad o sobreturno.
- Auditoria ampliada de eventos de llamado (reportes operativos).

#### RF-SAT-02 — Recordatorios

**Hipotesis funcional inicial:**

- Generar recordatorios para turnos futuros segun ventana configurable (ej. 24/48 hs).
- Mantener bandeja de recordatorios con estados (pendiente, enviado, confirmado, cancelado).
- Permitir confirmacion/anulacion desde gestion interna (canal externo pendiente).

**Datos y entidades candidatas:**

- `agenda_turnos` + datos de contacto del paciente.
- Tabla nueva sugerida: `agenda_recordatorios` (id_turno, fecha_programada, canal, estado, intentos, observaciones, timestamps).
- Parametros globales en `config` (anticipacion, horario de envio, politicas de reintento).

**Brechas a validar con uso real/exe:**

- Si en el exe habia canales concretos (SMS/WhatsApp/email/llamada) o solo listado interno.
- Reglas de "no recordar" (paciente bloqueado, turno ya atendido/anulado, etc.).
- Necesidad de auditoria legal de contacto.

**Checklist de implementacion futura — Recordatorios por WhatsApp (Twilio)**

Objetivo: dejar una guia ejecutable para implementar mas adelante sin perder decisiones tomadas.

### A) Definicion funcional y legal

- [ ] Confirmar canal inicial unico: WhatsApp.
- [ ] Confirmar ventana de envio v1 (ej. 24h antes; opcional 2h antes en v1.1).
- [ ] Definir politica de opt-in/consentimiento del paciente para mensajeria.
- [ ] Definir comportamiento ante respuesta del paciente (`SI`, `NO`, `REPROGRAMAR`).
- [ ] Definir texto final del recordatorio (tono clinica, datos minimos, privacidad).

### B) Alta en proveedor (Twilio + WhatsApp)

- [ ] Crear cuenta Twilio productiva con billing habilitado.
- [ ] Registrar sender WhatsApp (numero Twilio o numero propio compatible).
- [ ] Configurar perfil de negocio y display name.
- [ ] Crear y aprobar template de recordatorio (categoria Utility recomendada).
- [ ] Verificar costos por pais objetivo (Twilio fee + Meta fee) y estimacion mensual inicial.

### C) Modelo de datos en Control Salud Web

- [ ] Crear tabla `agenda_recordatorios` (id_turno, nro_hc, telefono_e164, estado, intentos, ids externos, timestamps).
- [ ] Definir indice por `estado + programado_en` para cola de envio.
- [ ] Definir indice por `id_turno` para trazabilidad por turno.
- [ ] Agregar flags/config en `config` (enabled, ventana_24h, proveedor, template, reintentos maximos).
- [ ] Definir politica de retencion de historico de recordatorios.

### D) Integracion tecnica (backend)

- [ ] Implementar servicio proveedor-agnostico (`WhatsAppProvider`) para no acoplarse fuerte a Twilio.
- [ ] Implementar adaptador Twilio (`TwilioWhatsAppProvider`) con envio de template.
- [ ] Implementar scheduler/cron que genere cola de recordatorios pendientes.
- [ ] Implementar proceso de envio con control de reintentos y backoff.
- [ ] Persistir `message_id` externo y respuesta de API por cada intento.

### E) Webhook y estados

- [ ] Exponer endpoint webhook seguro para estados de Twilio (sent/delivered/read/failed).
- [ ] Validar firma del webhook (seguridad).
- [ ] Actualizar estado en `agenda_recordatorios` por evento recibido.
- [ ] Procesar mensajes entrantes del paciente y mapear a accion (`confirmado`, `cancelado`, `reprogramar`).
- [ ] Reflejar confirmacion en agenda (ej. `confirmado=1` en turno).

### F) UI minima operativa

- [ ] Vista de bandeja de recordatorios (pendientes, enviados, fallidos, respondidos).
- [ ] Accion manual: reenviar recordatorio desde agenda/turno.
- [ ] Filtro por profesional/fecha/estado para seguimiento diario.
- [ ] Indicador visual en agenda de turno confirmado por WhatsApp.
- [ ] Registro de auditoria basico (quien reenvio/manual y cuando).

### G) Pruebas y salida a produccion

- [ ] Probar sandbox/staging con numeros de prueba.
- [ ] Ejecutar piloto controlado (ej. 1 profesional o 1 sucursal por 1 semana).
- [ ] Medir tasa de entrega/lectura/respuesta y ajustar texto/template.
- [ ] Verificar manejo de errores reales (numero invalido, bloqueo, timeout API).
- [ ] Activar progresivamente al resto de profesionales/sucursales.

### H) Criterio de cerrado v1 Recordatorios

- [ ] Se generan recordatorios automaticamente en ventana definida.
- [ ] Se envian por WhatsApp y quedan trazados con estado final.
- [ ] Las respuestas del paciente actualizan estado operativo del turno.
- [ ] Existe bandeja operativa para seguimiento y reintento manual.
- [ ] Costos y metricas iniciales quedan documentados por mes.

**Diccionario propuesto — tabla `agenda_recordatorios` (v1, documentacion):**

| Campo | Tipo sugerido | Requerido | Descripcion |
|------|----------------|-----------|-------------|
| `id` | BIGINT PK AI | Si | Identificador tecnico del recordatorio. |
| `id_clinica` | INT | Si | Scope multi-clinica. |
| `id_turno` | INT | Si | Turno asociado en `agenda_turnos`. |
| `id_doctor` | INT | No | Profesional asociado al turno (denormalizado para reportes). |
| `nro_hc` | INT | Si | HC del paciente. |
| `telefono_e164` | VARCHAR(25) | Si | Telefono normalizado (ej. `+549...`). |
| `canal` | VARCHAR(20) | Si | Canal de envio (`whatsapp` en v1). |
| `proveedor` | VARCHAR(30) | Si | Proveedor de envio (`twilio` en v1). |
| `template_codigo` | VARCHAR(80) | No | Nombre/codigo de template aprobado. |
| `mensaje_render` | TEXT | No | Copia del mensaje final enviado (auditoria). |
| `estado` | VARCHAR(24) | Si | `pendiente`, `enviado`, `entregado`, `leido`, `confirmado`, `cancelado`, `reprogramar`, `error`. |
| `programado_en` | DATETIME | Si | Fecha/hora objetivo de envio. |
| `enviado_en` | DATETIME | No | Fecha/hora efectiva de envio. |
| `id_mensaje_externo` | VARCHAR(120) | No | ID de mensaje devuelto por proveedor. |
| `intentos` | SMALLINT | Si | Cantidad de intentos realizados (default 0). |
| `ultimo_error` | VARCHAR(255) | No | Ultimo error tecnico/funcional de envio. |
| `respuesta_texto` | TEXT | No | Mensaje entrante del paciente (si responde). |
| `respuesta_codigo` | VARCHAR(24) | No | Mapeo normalizado (`SI`, `NO`, `REPROGRAMAR`, etc.). |
| `respuesta_en` | DATETIME | No | Fecha/hora de respuesta del paciente. |
| `payload_webhook` | MEDIUMTEXT | No | JSON crudo de webhook (auditoria tecnica). |
| `creado_en` | DATETIME | Si | Alta de registro. |
| `actualizado_en` | DATETIME | Si | Ultima actualizacion. |

**Indices minimos sugeridos (v1):**

- `(id_clinica, estado, programado_en)` para cola de envios.
- `(id_turno)` para trazabilidad por turno.
- `(id_mensaje_externo)` para correlacion webhook -> registro.
- `(telefono_e164, creado_en DESC)` para historial por paciente/telefono.

**Borrador de migracion SQL (referencia, no ejecutar aun):**

```sql
-- Propuesta: sql/migration_031_agenda_recordatorios.sql
CREATE TABLE IF NOT EXISTS agenda_recordatorios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_clinica INT NOT NULL DEFAULT 1,
    id_turno INT NOT NULL,
    id_doctor INT NULL,
    nro_hc INT NOT NULL,
    telefono_e164 VARCHAR(25) NOT NULL,
    canal VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
    proveedor VARCHAR(30) NOT NULL DEFAULT 'twilio',
    template_codigo VARCHAR(80) NULL,
    mensaje_render TEXT NULL,
    estado VARCHAR(24) NOT NULL DEFAULT 'pendiente',
    programado_en DATETIME NOT NULL,
    enviado_en DATETIME NULL,
    id_mensaje_externo VARCHAR(120) NULL,
    intentos SMALLINT NOT NULL DEFAULT 0,
    ultimo_error VARCHAR(255) NULL,
    respuesta_texto TEXT NULL,
    respuesta_codigo VARCHAR(24) NULL,
    respuesta_en DATETIME NULL,
    payload_webhook MEDIUMTEXT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_recordatorios_clinica_estado_prog (id_clinica, estado, programado_en),
    KEY idx_recordatorios_turno (id_turno),
    KEY idx_recordatorios_msg_ext (id_mensaje_externo),
    KEY idx_recordatorios_tel_creado (telefono_e164, creado_en),
    CONSTRAINT chk_recordatorios_estado CHECK (
        estado IN ('pendiente', 'enviado', 'entregado', 'leido', 'confirmado', 'cancelado', 'reprogramar', 'error')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Nota:** este script es solo guia de analisis. Antes de implementarlo, validar tipos/FKs exactos contra el esquema vigente y definir politica de retencion de `payload_webhook`.

#### RF-SAT-03 — Agenda web (autogestion/control externo)

**Hipotesis funcional inicial:**

- Publicar disponibilidad acotada para solicitud de turnos.
- Integrarse con reglas de `agenda_turnos` y `agenda_bloqueos`.
- Requerir validaciones anti-duplicado (por paciente/documento/telefono y ventana de tiempo).

**Datos y entidades candidatas:**

- `agenda_turnos`, `agenda_bloqueos`, `lista_doctores`.
- Tabla nueva sugerida: `agenda_solicitudes_web` para trazabilidad de origen externo.
- Configuracion de cupos/ventanas por profesional o especialidad.

**Brechas a validar con uso real/exe:**

- Si `AgendaWeb.exe` era publicacion externa, sincronizacion o modulo interno separado.
- Criterios de aprobacion manual vs confirmacion automatica.
- Politica de seguridad para exponer agenda a internet.

#### Criterio de arquitectura acordado para satelites

- No replicar ejecutables independientes.
- Integrar en la app `web/` con mismas capas (entrypoint + controller + repository + view).
- Mantener trazabilidad contra RF-SAT-01/02/03 y contra tablas de referencia.

#### Entregables de analisis pendientes (antes de implementar)

1. Capturas/recorrido guiado de cada ejecutable satelite con casos reales.
2. Matriz de eventos y estados por modulo (anunciador, recordatorios, agenda web).
3. Definicion de MVP por satelite (alcance minimo para primera version web).
4. Confirmacion de datos obligatorios y tablas nuevas a crear.

### 4.11 Chat interno entre usuarios (exe integrado, no satélite)

**Evidencia en sistema original:**

- Permiso por usuario: `Lista Doctores.accesochat` (junto a `accesored`, `accesocaja`, etc.).
- Historial de mensajes acumulado en `Lista Doctores.anuncio` con formato:
  `Enviado por ''REMITE'' a ''DESTINO'' (dd/mm/yyyy hh:mm:ss)` + texto.
- Destinos observados en datos reales: usuario concreto (`SECRETARIAS MAÑANA`, `AYMAR GRACIELA`, …), **`<Todos los Usuarios>`** (broadcast) y alias de área (**`LABORATORIO`**).
- Notificación sonora: `chat.wav` (ver `INVESTIGACION_ControlSalud.md`).

**Uso operativo típico (centro de referencia):**

- Coordinación recepción ↔ consultorios: paciente en sala, actualizar agenda, sobreturnos, autorizaciones OS.
- Consultas rápidas entre personal (precios, turnos, materiales, WiFi).
- No es mensajería a pacientes (eso es RF-SAT-02 Recordatorios / WhatsApp).

**Hipótesis funcional para web (MVP):**

- Bandeja o panel lateral accesible desde layout autenticado.
- Enviar a: un usuario (`lista_doctores` / sesión), «Todos los usuarios conectados» o rol/área acordada (ej. laboratorio).
- Respetar `accesochat` (o equivalente en matriz de permisos web).
- Lista de conversaciones + mensajes con marca de tiempo; indicador de no leídos.
- Sonido o badge opcional al recibir mensaje (sustituto de `chat.wav`).
- Polling corto o WebSocket según complejidad de despliegue.

**Datos y entidades candidatas:**

| Campo | Tipo | Obl. | Notas |
|-------|------|------|-------|
| `id` | BIGINT PK | Sí | |
| `id_clinica` | INT | Sí | Multi-clínica |
| `de_id_usuario` | INT | Sí | FK usuario/doctor remitente |
| `para_id_usuario` | INT | No | NULL = broadcast o destino grupal |
| `para_tipo` | VARCHAR(24) | Sí | `usuario`, `todos`, `area` |
| `para_clave` | VARCHAR(80) | No | Ej. `LABORATORIO` cuando `para_tipo=area` |
| `texto` | TEXT | Sí | |
| `creado_en` | DATETIME | Sí | |
| `leido_en` | DATETIME | No | Por destinatario (tabla de lecturas si hay broadcast) |

Tabla sugerida: `mensajes_internos` (+ opcional `mensajes_internos_lecturas` para broadcast).

**Migración legacy (opcional):**

- Parsear bloques de `Lista Doctores.anuncio` e importar a `mensajes_internos` (solo auditoría; no bloqueante para MVP).

**Brechas / decisiones antes de implementar:**

- [ ] Confirmar con el cliente si el chat sigue en uso diario en el exe actual.
- [ ] Mapear usuarios web ↔ `lista_doctores` para remitente/destinatario.
- [ ] Definir si «LABORATORIO» es rol, usuario ficticio o integración con módulo Laboratorio.
- [ ] Tiempo real: polling 5–10 s vs WebSocket (segundo plano si el hosting lo permite).

**Priorización sugerida:** media-baja (después de caja, órdenes avanzadas y recordatorios MVP).

**Esfuerzo orientativo:**

| Alcance | Días dev. aprox. |
|---------|------------------|
| MVP (enviar/recibir, lista usuarios, permiso, polling, sin migrar historial) | 3–5 |
| + notificaciones sonido/badge, lecturas broadcast, UI móvil usable | +2–3 |
| + migración historial `anuncio` + WebSocket | +2–4 |

**Criterio de cierre MVP:**

1. Usuario con `accesochat` puede enviar y recibir mensajes 1:1 y broadcast.
2. Mensajes persisten y se listan por conversación con fecha/hora.
3. Usuario sin permiso no ve el módulo.
4. Al menos un caso de prueba recepción → médico documentado.

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

- Implementado parcialmente: filtros por obra social/cobertura, plan, practica, fechas, profesional, estados A/F/P, sesiones, IVA, autorizacion, liquidacion y totales.
- Implementado parcialmente: grilla aproximada con fecha, practica, obra social, paciente, nro afiliado, profesional, monto obra social, debe paciente y nombre de practica si hay lista.
- Faltan acciones avanzadas del exe: facturar lote, marcar facturadas, pagar cobertura, impresiones/exportaciones especificas y liquidacion masiva de honorarios.

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

## 6.1 Checklist maestro unificado (hoja de ruta)

Estados sugeridos para gestion diaria: **[x] listo**, **[~] parcial**, **[ ] pendiente**.

### A) Paridad funcional con Control Salud.exe

- [x] **Pacientes (nucleo):** listado con filtros (`q`, `nrohc`, `id`, `activo`) + ABM.
- [~] **Ficha de paciente extendida:** existe base web, falta cerrar todos los campos/pestanas del exe (Anexo B).
- [x] **Doctores:** ABM + uso transversal en agenda/ordenes/sesiones.
- [x] **Agenda y turnos:** agenda diaria, alta/edicion de turnos, bloqueos.
- [~] **Ordenes:** ABM, busqueda de paciente por nombre/DNI, arancel por obra social/practica y listado operativo; falta facturacion por lote y acciones avanzadas del exe (RF-ORD-02, Anexo A).
- [~] **Sesiones:** ABM y vinculacion con ordenes; falta cierre de reportes y filtros avanzados por sesiones.
- [~] **Pagos y Caja:** flujo operativo inicial activo con cierre de caja y contra movimientos; falta totales por medio/turno y reglas finas del exe.
- [ ] **Consultas medicas:** tablas/modelo presentes, falta modulo web completo.
- [ ] **Internacion/camas:** modelo presente, falta modulo web completo.
- [~] **Satelites (Anunciador/Recordatorios/AgendaWeb):** Anunciador v1 y Agenda Web MVP operativos; pendiente Recordatorios y v2 de Agenda Web.
- [ ] **Chat interno (RF-INT-01):** integrado en exe (`accesochat`, historial en `Lista Doctores.anuncio`, `chat.wav`); sin modulo web; ver seccion 4.11.

### B) Calidad tecnica minima (para avanzar sin deuda peligrosa)

- [ ] Cerrar P0 de seguridad: proteger setup, csrf en endpoint JSON, estrategia segura de adjuntos HC.
- [ ] Cerrar P1 de seguridad/performance: rate limit login, mejoras de cache de esquema, `session_write_close()` en endpoints AJAX.
- [ ] Definir y aplicar politica de permisos por rol para modulos clinicos/administrativos.
- [ ] Establecer set minimo de pruebas de humo (login, paciente, turno, orden, pago, HC).
- [ ] Mantener SQL en repositorios/controladores (evitar crecimiento de SQL directo en `public/`).

### C) Operacion y despliegue (checklist rapido por entorno)

- [ ] Base creada e importada (`sql/schema_mysql.sql` + migraciones pendientes por numero).
- [~] Catalogos de ordenes validados parcialmente con datos reales (cobertura/plan/practica/precios); falta completar derivacion/sucursal y prueba funcional de aranceles.
- [ ] Multi-clinica validada (`id_clinica` coherente en usuarios y datos operativos).
- [ ] Flujo funcional minimo verificado: login -> paciente -> turno -> orden -> pago -> caja.
- [ ] Configuracion de `base_path` validada segun URL real de despliegue.

### D) Definicion de "cerrado" por item

Un item se considera cerrado cuando cumple todo lo siguiente:

1. Pantalla/flujo visible en web y utilizable.
2. Persistencia correcta en tablas esperadas.
3. Filtros operativos minimos (si aplica listado).
4. Validacion funcional con al menos un caso real o dataset de referencia.
5. Trazabilidad actualizada en este mismo documento (RF asociado + estado).

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
- `web/ARQUITECTURA_PROPUESTA.md` — referencia historica (su checklist queda absorbido por este documento).
- `README_DESPLIEGUE.md` — guia operativa de despliegue (sus checks clave quedan reflejados en 6.1.C).

---

## 10. Control de versiones del documento

| Versión | Fecha | Cambio |
|---------|-------|--------|
| 0.1 | 2026-04-08 | Creación inicial: estructura ERS + RFs por dominio + fuentes y vacíos. |
| 0.2 | 2026-04-08 | Anexo A: pantalla «Órdenes de los Pacientes» (captura); RF-ORD-02; mapeo columnas/filtros. |
| 0.3 | 2026-04-08 | Anexo B: ficha «Información del Paciente»; RF-PAC-05 búsqueda listado web. |
| 0.4 | 2026-04-30 | Se incorpora checklist maestro unificado (paridad + calidad tecnica + operacion) y se declara este archivo como hoja de ruta unica. |
| 0.5 | 2026-04-30 | Se agrega analisis funcional inicial de modulos satelite (Anunciador, Recordatorios, Agenda web) para documentar comportamiento previo a implementacion. |
| 0.6 | 2026-04-30 | Se define RF-SAT-01 Anunciador v1: estados, transiciones, acciones por rol, vista de sala y criterio de cerrado MVP. |
| 0.7 | 2026-04-30 | Se agrega diccionario de datos propuesto para `agenda_llamados`, indices minimos y reglas de integridad/operacion para el Anunciador v1. |
| 0.8 | 2026-04-30 | Se incorpora borrador de migracion SQL para `agenda_llamados` (revision funcional previa a implementacion). |
| 0.9 | 2026-04-30 | Se actualiza estado real: RF-SAT-01 marcado como C (v1 web), checklist satelites en parcial y pendientes v2 documentados. |
| 1.0 | 2026-04-30 | Se agrega checklist de implementacion futura para RF-SAT-02 Recordatorios por WhatsApp (Twilio): funcional, tecnico, datos, webhook, UI, pruebas y criterio de cierre. |
| 1.1 | 2026-04-30 | Se agrega para RF-SAT-02 el diccionario propuesto de `agenda_recordatorios`, indices minimos y borrador documental de `migration_031_agenda_recordatorios.sql` (sin implementacion aun). |
| 1.2 | 2026-05-13 | Se incorporan procesos operativos reales del centro: recepcion particular/obra social, turnos, recordatorios, facturacion por obra social y caja diaria, con estado actual y priorizacion. |
| 1.3 | 2026-05-14 | Se actualiza hoja de ruta tras recepcion guiada, Agenda Web MVP, cierre de caja inicial y mejoras de ordenes/aranceles/listado para facturacion. |
| 1.4 | 2026-05-18 | Se documenta RF-INT-01 Chat interno (exe integrado): evidencia en BD, alcance MVP, tablas candidatas, priorizacion y esfuerzo orientativo. |
| 1.5 | 2026-05-18 | Se agrega `CHECKLIST_AGENDA_PARIDAD.md` para validar con el centro que funciones de agenda del exe siguen en uso y priorizar brechas web. |
