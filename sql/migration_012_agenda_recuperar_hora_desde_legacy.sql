-- Recupera hora de agenda_turnos desde la tabla legacy `Agenda Turnos`.
-- Útil cuando la sincronización previa cargó `hora` en NULL.

SET NAMES utf8mb4;

UPDATE agenda_turnos t
JOIN `Agenda Turnos` l ON l.id = t.id
SET
  t.Fecha = DATE(l.Fecha),
  t.hora = TIME(l.Fecha)
WHERE l.Fecha IS NOT NULL;
