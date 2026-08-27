# Checklist agenda — paridad exe vs web

**Objetivo:** en una reunión corta con secretaría / administración, marcar **qué funciones de agenda del exe siguen en uso hoy** y priorizar lo que falta en web.

**Referencia:** `REQUISITOS_Sistema_ControlSalud.md` (RF-AGE-01, RF-AGE-02, RF-SAT-02, RF-SAT-03).

**Cómo usarlo**

1. Recorrer la tabla de abajo con quien opera la agenda a diario.
2. En **¿Lo usan hoy?** marcar Sí / No / No sé.
3. Si es **Sí** y web está **Falta** o **Parcial**, subir prioridad.
4. Al cerrar la reunión, ordenar la columna **Prioridad si Sí** (1 = primero).

---

## A) Núcleo operativo (día a día en secretaría)

| # | Función (exe / operación) | Web hoy | ¿Lo usan hoy? | Prioridad si Sí | Notas |
|---|---------------------------|---------|---------------|-----------------|-------|
| A1 | Agenda diaria por fecha y profesional | **Listo** | ☐ Sí ☐ No ☐ ? | — | `/agenda.php` |
| A2 | Alta / edición / baja de turno | **Listo** | ☐ Sí ☐ No ☐ ? | — | Grilla de horarios + bloqueos |
| A3 | Planilla semanal del doctor (mañana/tarde, duración) | **Listo** | ☐ Sí ☐ No ☐ ? | — | Ficha doctor → “Agenda semanal” |
| A4 | Bloqueos (día u horario sin atención) | **Listo** | ☐ Sí ☐ No ☐ ? | — | `/agenda_bloqueos.php` |
| A5 | Marcar **llegó** / **atendido** / **no asistió** | **Listo** | ☐ Sí ☐ No ☐ ? | — | Acciones rápidas en agenda |
| A6 | Vínculo turno ↔ orden / **Cobro·Orden** | **Listo** | ☐ Sí ☐ No ☐ ? | — | Desde agenda o recepción |
| A7 | **Recepción guiada** (particular / OS) | **Listo** | ☐ Sí ☐ No ☐ ? | — | `/recepcion_turno.php` |
| A8 | **Control diario** (atendidos sin orden/pago) | **Listo** | ☐ Sí ☐ No ☐ ? | — | `/control_administrativo.php` |
| A9 | **Anunciador** (llamar paciente en sala) | **Listo** (v1) | ☐ Sí ☐ No ☐ ? | — | Monitor + llamar desde agenda |
| A10 | Búsqueda **próximos horarios libres** | **Listo** | ☐ Sí ☐ No ☐ ? | — | Al cargar turno |

**Conclusión A:** si marcaron Sí en casi todo, el circuito interno ya está cubierto. Lo urgente pasa a ser comunicación con paciente (sección C) o funciones exe específicas (sección B).

---

## B) Funciones del exe que la web aún no replica

| # | Función (exe) | Web hoy | ¿Lo usan hoy? | Prioridad si Sí | Esfuerzo orientativo |
|---|---------------|---------|---------------|-----------------|----------------------|
| B1 | **Sobreturnos** (límite por día/hora, resaltado, permiso `accesoasignarsobreturnos`) | **Falta** | ☐ Sí ☐ No ☐ ? | ___ | 3–5 días |
| B2 | **Plantilla de turnos** (`plantillaagenda` — generar semana desde plantilla) | **Falta** | ☐ Sí ☐ No ☐ ? | ___ | 4–6 días |
| B3 | **Agenda telefónica** (directorio `Agenda Telefonica`, permiso `accesoagendatelefonica`) | **Falta** | ☐ Sí ☐ No ☐ ? | ___ | 2–3 días |
| B4 | **Días “no se atiende”** (`Agenda Turnos No Se Atiende` — fechas puntuales) | **Parcial** | ☐ Sí ☐ No ☐ ? | ___ | 1–2 días* |
| B5 | **Permisos finos** por usuario (`accesoagenda`, `accesoasignarsobreturnos`, etc.) | **Falta** | ☐ Sí ☐ No ☐ ? | ___ | 3–5 días (matriz global) |
| B6 | Vista **semanal / mensual** (si la usaban en exe) | **Falta** | ☐ Sí ☐ No ☐ ? | ___ | 2–4 días |
| B7 | **Informes** de agenda (ausentismo, productividad, etc.) | **Falta** | ☐ Sí ☐ No ☐ ? | ___ | según informe |
| B8 | **Chat interno** (“actualice agenda”, paciente en sala) | **Falta** | ☐ Sí ☐ No ☐ ? | ___ | 3–5 días MVP |

\* B4: hoy los **bloqueos** cubren rangos de fechas/horas; “no se atiende” legacy es un día concreto por doctor. Si solo usan bloqueos, B4 puede quedar **No**.

**Preguntas guía para B1–B3**

- **Sobreturnos:** ¿Cargan turnos fuera de horario con un botón o menú especial? ¿Hay tope por hora? ¿Se pintan en rojo en la planilla?
- **Plantilla:** ¿Arman la semana entera de una vez o día por día?
- **Agenda telefónica:** ¿Es un listado de contactos aparte de pacientes (familiares, médicos, laboratorios)?

---

## C) Paciente / canales externos (alrededor de la agenda)

| # | Función | Web hoy | ¿Lo usan / lo quieren? | Prioridad si Sí | Notas |
|---|---------|---------|------------------------|-----------------|-------|
| C1 | **Agenda Web** — paciente reserva con DNI | **Listo** (MVP) | ☐ Sí ☐ No ☐ ? | ___ | `/agenda_web.php` |
| C2 | **Agenda Web v2** — cancelar / reprogramar turno | **Falta** | ☐ Sí ☐ No ☐ ? | ___ | 3–4 días |
| C3 | **WhatsApp confirmación** al crear turno | **Código listo** / despliegue pendiente | ☐ Sí ☐ No ☐ ? | ___ | Ver `web/deploy/DESPLIEGUE_RECORDATORIOS_GESIS.md` |
| C4 | **WhatsApp recordatorio** día anterior | **Código listo** / despliegue pendiente | ☐ Sí ☐ No ☐ ? | ___ | Cron + bandeja |
| C5 | Respuesta **SI / NO** del paciente → actualiza turno | **Etapa 2** / despliegue pendiente | ☐ Sí ☐ No ☐ ? | ___ | Webhook Gesis |
| C6 | Aviso WhatsApp al **anular** turno desde agenda | **Etapa 2** / despliegue pendiente | ☐ Sí ☐ No ☐ ? | ___ | `agenda_turno_anular.php` |

**Preguntas guía**

- ¿Hoy confirman turnos por WhatsApp manualmente o no lo hacen?
- ¿Quieren que el paciente cancele solo desde el celular o solo por teléfono a secretaría?
- ¿La Agenda Web la usan pacientes o solo la secretaría carga todo?

---

## D) Mejoras anunciador (no bloquean operación)

| # | Función | Web hoy | ¿Importa al centro? | Prioridad |
|---|---------|---------|---------------------|-----------|
| D1 | Sonido configurable por clínica/consultorio | **Falta** | ☐ Sí ☐ No | ___ |
| D2 | Actualización en tiempo real (WebSocket) | **Falta** (polling) | ☐ Sí ☐ No | ___ |
| D3 | Prioridad de cola / sobreturnos en llamado | **Falta** | ☐ Sí ☐ No | ___ |

---

## E) Consultas SQL (opcional — si tenés la BD legacy o MySQL migrada)

Ejecutar en la base con datos reales del centro para **inferir uso** antes de la reunión:

```sql
-- ¿Hay contactos en agenda telefónica?
SELECT COUNT(*) AS filas FROM `Agenda Telefonica`;

-- ¿Hay días "no se atiende" cargados?
SELECT COUNT(*) AS filas FROM `Agenda Turnos No Se Atiende`;

-- ¿Algún doctor tiene sobreturnos configurados en planilla?
SELECT id, iddoctor, cantidadsobreturnos, cantidadsobreturnoshora,
       sobreturnoshoraresaltar, solosobreturnoshoraplanilla
FROM `Agenda Turnos Horarios`
WHERE COALESCE(cantidadsobreturnos, 0) > 0
   OR COALESCE(cantidadsobreturnoshora, 0) > 0
   OR COALESCE(sobreturnoshoraresaltar, 0) = 1;

-- ¿Usuarios con permiso de sobreturnos o agenda telefónica?
SELECT nombre, accesoagendas, accesoagendatelefonica, accesoasignarsobreturnos
FROM `Lista Doctores`
WHERE COALESCE(accesoasignarsobreturnos, 0) = 1
   OR COALESCE(accesoagendatelefonica, 0) = 1;

-- ¿Plantilla de agenda activa en config?
SELECT plantillaagenda FROM config LIMIT 5;
```

Si `filas = 0` y permisos en 0, es probable que **no usen** esa función (validar igual con el operador).

---

## F) Orden sugerido de implementación (después de validar)

Ajustar según lo marcado en la reunión. Orden **típico** si el centro es activo en WhatsApp y recepción:

| Orden | Ítem | Condición |
|-------|------|-----------|
| 1 | C3–C6 Desplegar recordatorios Gesis (MVP + etapa 2) | Si quieren confirmación/recordatorio automático |
| 2 | C2 Agenda Web cancelar/reprogramar | Si C1 en uso y llaman mucho para cancelar |
| 3 | B1 Sobreturnos | Si marcaron Sí en B1 |
| 4 | B8 Chat interno | Si coordinan mucho por mensajes en exe |
| 5 | B3 Agenda telefónica | Si el directorio sigue en uso |
| 6 | B2 Plantilla de turnos | Si arman semanas completas en bloque |
| 7 | B5 Permisos finos | Cuando cierre matriz de roles global |
| 8 | D1–D3 Anunciador v2 | Mejora, no bloqueante |

---

## G) Acta rápida (completar al cerrar reunión)

| Campo | Valor |
|-------|--------|
| Fecha | |
| Participantes | |
| ¿Circuito A cubierto para operación diaria? | ☐ Sí ☐ Con gaps: __________ |
| Top 3 pendientes acordados | 1. _____ 2. _____ 3. _____ |
| ¿Desplegar WhatsApp Gesis? | ☐ Sí ☐ No ☐ Después de: _____ |
| Observaciones | |

---

*Última actualización: 2026-05-18. Tras la reunión, actualizar prioridades en `REQUISITOS_Sistema_ControlSalud.md` §6.1 si cambia el orden del proyecto.*
