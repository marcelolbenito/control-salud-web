-- Multi-clínica: columna id_clinica en tablas operativas + usuarios.
-- Ejecutar una vez sobre una BD ya importada (post schema / backups).
-- Datos existentes quedan en id_clinica = 1 ("Clínica principal").

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS clinicas (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO clinicas (id, nombre, activo) VALUES (1, 'Clínica principal', 1);

-- ---------- usuarios ----------
ALTER TABLE usuarios
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 COMMENT 'Clínica activa del usuario' AFTER activo;
CREATE INDEX idx_usuarios_clinica ON usuarios (id_clinica);

-- ---------- lista_doctores ----------
ALTER TABLE lista_doctores
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_lista_doctores_clinica ON lista_doctores (id_clinica);

-- ---------- pacientes (NroHC único por clínica) ----------
ALTER TABLE pacientes DROP INDEX uk_pacientes_nrohc;
ALTER TABLE pacientes
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE UNIQUE INDEX uk_pacientes_clinica_nrohc ON pacientes (id_clinica, NroHC);
CREATE INDEX idx_pacientes_clinica ON pacientes (id_clinica);

-- ---------- agenda_turnos ----------
ALTER TABLE agenda_turnos
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_agenda_clinica ON agenda_turnos (id_clinica);
CREATE INDEX idx_agenda_clinica_fecha ON agenda_turnos (id_clinica, Fecha);

-- ---------- Pacientes Ordenes ----------
ALTER TABLE `Pacientes Ordenes`
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_pac_ordenes_clinica ON `Pacientes Ordenes` (id_clinica);
CREATE INDEX idx_pac_ordenes_clinica_nropaci ON `Pacientes Ordenes` (id_clinica, NroPaci);

-- ---------- pacientes_sesiones ----------
ALTER TABLE pacientes_sesiones
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_sesiones_clinica ON pacientes_sesiones (id_clinica);

-- ---------- pacientes_pagos ----------
ALTER TABLE pacientes_pagos
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_pagos_clinica ON pacientes_pagos (id_clinica);

-- ---------- caja ----------
ALTER TABLE caja
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_caja_clinica ON caja (id_clinica);
CREATE INDEX idx_caja_clinica_fecha ON caja (id_clinica, fechacaja);

-- ---------- consultas / camas ----------
ALTER TABLE consultas
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_consultas_clinica ON consultas (id_clinica);

ALTER TABLE camas
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_camas_clinica ON camas (id_clinica);

ALTER TABLE camas_pacientes
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_camas_pac_clinica ON camas_pacientes (id_clinica);

-- ---------- odontograma (omití este bloque si no existen las tablas) ----------
ALTER TABLE pacientes_odontograma
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE INDEX idx_odontograma_clinica_nrohc ON pacientes_odontograma (id_clinica, NroHC);

ALTER TABLE pacientes_odontograma_superficies DROP INDEX uk_odontograma_superficie;
ALTER TABLE pacientes_odontograma_superficies
  ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
CREATE UNIQUE INDEX uk_odontograma_sup_clin_nro_pieza_cara
  ON pacientes_odontograma_superficies (id_clinica, NroHC, pieza_fdi, cara);
CREATE INDEX idx_odontograma_sup_clinica ON pacientes_odontograma_superficies (id_clinica, NroHC);

-- ---------- config (clave única por clínica) ----------
ALTER TABLE config ADD COLUMN id_clinica INT NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE config DROP INDEX uk_config_clave;
CREATE UNIQUE INDEX uk_config_clinica_clave ON config (id_clinica, clave);
CREATE INDEX idx_config_clinica ON config (id_clinica);

SET FOREIGN_KEY_CHECKS = 1;
