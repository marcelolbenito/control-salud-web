-- Auditoría de sobreturnos — Control Salud
-- Ejecutar en MySQL (local Docker: puerto 3307, prod: según entorno).
-- Uso: mysql -u... -p control_salud < sql/audit_sobreturnos.sql

SELECT '=== Resumen conteos ===' AS seccion;
SELECT
  (SELECT COUNT(*) FROM `Agenda Turnos Horarios`) AS planillas_horarios,
  (SELECT COUNT(*) FROM `Agenda Turnos Horarios`
     WHERE COALESCE(cantidadsobreturnos,0) > 0 OR COALESCE(cantidadsobreturnoshora,0) > 0
        OR COALESCE(sobreturnoshoraresaltar,0) = 1) AS planillas_con_cupo_o_resaltado,
  (SELECT COUNT(*) FROM agenda_turnos) AS turnos_total,
  (SELECT COUNT(*) FROM (
      SELECT 1 FROM agenda_turnos WHERE hora IS NOT NULL
      GROUP BY Fecha, Doctor, DATE_FORMAT(hora,'%H:%i') HAVING COUNT(*)>1
   ) x) AS slots_dobles_historico,
  (SELECT COUNT(*) FROM (
      SELECT 1 FROM agenda_turnos WHERE hora IS NOT NULL AND Fecha >= '2026-01-01'
      GROUP BY Fecha, Doctor, DATE_FORMAT(hora,'%H:%i') HAVING COUNT(*)>1
   ) y) AS slots_dobles_2026;

SELECT '=== Planillas con cupo / resaltado sobreturno (muestra) ===' AS seccion;
SELECT h.id, h.iddoctor, d.nombre, h.fechadesde, h.fechahasta,
       h.cantidadsobreturnos, h.cantidadsobreturnoshora,
       h.sobreturnoshoraresaltar, h.solosobreturnoshoraplanilla
FROM `Agenda Turnos Horarios` h
LEFT JOIN lista_doctores d ON d.id = h.iddoctor
WHERE COALESCE(h.cantidadsobreturnos, 0) <> 0
   OR COALESCE(h.cantidadsobreturnoshora, 0) <> 0
   OR COALESCE(h.sobreturnoshoraresaltar, 0) = 1
ORDER BY h.fechahasta DESC
LIMIT 25;

SELECT '=== Usuarios con permiso asignar sobreturnos ===' AS seccion;
SELECT id, NomDoc AS nombre, accesoasignarsobreturnos
FROM `Lista Doctores`
WHERE COALESCE(accesoasignarsobreturnos, 0) = 1
ORDER BY NomDoc
LIMIT 40;

SELECT '=== Doctores con mas horas dobles (2024+) ===' AS seccion;
SELECT t.Doctor, d.nombre, COUNT(*) AS veces_slot_doble
FROM (
  SELECT Fecha, Doctor, DATE_FORMAT(hora,'%H:%i') h, COUNT(*) c
  FROM agenda_turnos
  WHERE hora IS NOT NULL AND Fecha >= '2024-01-01'
  GROUP BY Fecha, Doctor, DATE_FORMAT(hora,'%H:%i')
  HAVING c > 1
) t
LEFT JOIN lista_doctores d ON d.id = t.Doctor
GROUP BY t.Doctor, d.nombre
ORDER BY veces_slot_doble DESC
LIMIT 15;

SELECT '=== Slots dobles recientes (ultimos 25) ===' AS seccion;
SELECT t.Fecha, t.Doctor, d.nombre AS doctor_nombre,
       DATE_FORMAT(t.hora, '%H:%i') AS hora, COUNT(*) AS cantidad
FROM agenda_turnos t
LEFT JOIN lista_doctores d ON d.id = t.Doctor
WHERE t.hora IS NOT NULL AND t.Fecha >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
GROUP BY t.Fecha, t.Doctor, d.nombre, DATE_FORMAT(t.hora, '%H:%i')
HAVING COUNT(*) > 1
ORDER BY t.Fecha DESC, cantidad DESC
LIMIT 25;
