-- Catálogo "Lista Motivos Consulta" (Access) + índice y vista para agenda.
--
-- PRERREQUISITOS (en este orden):
--   1. schema_mysql.sql
--   2. migration_003_doctores_agenda_exe.sql   ← debe existir agenda_turnos.motivo (y paciente_nombre)
--
-- Si CREATE INDEX falla por nombre duplicado, el índice ya existe: podés ignorar o borrarlo y volver a crear.
-- Si CREATE VIEW falla por columna inexistente, no corriste migration_003.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lista_motivos_consulta (
  id INT PRIMARY KEY,
  prioridad SMALLINT NULL,
  nombre VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT 'Access: Lista Motivos Consulta → agenda_turnos.motivo';

CREATE INDEX idx_agenda_motivo ON agenda_turnos (motivo);

DROP VIEW IF EXISTS v_agenda_turnos_detalle;

CREATE VIEW v_agenda_turnos_detalle AS
SELECT
  t.id,
  t.Fecha,
  t.hora,
  t.NroHC,
  t.Doctor,
  t.idorden,
  t.estado,
  t.observaciones,
  t.motivo,
  COALESCE(NULLIF(TRIM(t.paciente_nombre), ''), p.Nombres) AS paciente_mostrar,
  p.Nombres AS paciente_nombres_tabla,
  d.nombre AS doctor_nombre,
  m.nombre AS motivo_nombre,
  t.creado_en,
  t.actualizado_en
FROM agenda_turnos t
LEFT JOIN pacientes p ON p.NroHC = t.NroHC
LEFT JOIN lista_doctores d ON d.id = t.Doctor
LEFT JOIN lista_motivos_consulta m ON m.id = t.motivo;
