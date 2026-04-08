-- Campos extra alineados con Access: Lista Doctores y Agenda Turnos.
-- Ejecutar una vez después de schema_mysql.sql (y migraciones previas).

SET NAMES utf8mb4;

-- ---------- Lista Doctores (Access: NomDoc → ya mapeado a `nombre` en imports) ----------
ALTER TABLE lista_doctores
  ADD COLUMN especialidad VARCHAR(100) NULL COMMENT 'Access: Especialidad',
  ADD COLUMN matricula VARCHAR(20) NULL COMMENT 'Access: Matricula',
  ADD COLUMN telefono VARCHAR(30) NULL COMMENT 'Access: Tel',
  ADD COLUMN domicilio VARCHAR(100) NULL COMMENT 'Access: Domi',
  ADD COLUMN localidad VARCHAR(50) NULL COMMENT 'Access: Locali',
  ADD COLUMN consultorio VARCHAR(30) NULL COMMENT 'Access: consultorio';

-- sucursal1..10 en el esquema base ya existen como TINYINT; el exe usa SMALLINT por sucursal — no duplicar.

-- ---------- Agenda Turnos ----------
ALTER TABLE agenda_turnos
  ADD COLUMN paciente_nombre VARCHAR(60) NULL COMMENT 'Access: Paciente (texto denormalizado)',
  ADD COLUMN motivo INT NULL COMMENT 'Access: motivo → Lista Motivos Consulta',
  ADD COLUMN atendido TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Access: Atendido',
  ADD COLUMN pagado TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Access: pagado',
  ADD COLUMN llegado TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Access: llegado',
  ADD COLUMN llegado_hora VARCHAR(5) NULL COMMENT 'Access: llegadohora',
  ADD COLUMN confirmado TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Access: confirmado',
  ADD COLUMN falta_turno TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Access: faltoturno',
  ADD COLUMN reingresar TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Access: reingresar',
  ADD COLUMN primera_vez SMALLINT NULL COMMENT 'Access: primeravez',
  ADD COLUMN num_sesion INT NULL COMMENT 'Access: numesesion',
  ADD COLUMN id_sesion INT NULL COMMENT 'Access: idsesion',
  ADD COLUMN id_caja INT NULL COMMENT 'Access: idcaja',
  ADD COLUMN usuario_asignado VARCHAR(50) NULL COMMENT 'Access: usuarioasigtur',
  ADD COLUMN alta_paci_web SMALLINT NULL COMMENT 'Access: altapaciweb',
  ADD COLUMN fechahora_asignado DATETIME NULL COMMENT 'Access: fechahoraasignado';
