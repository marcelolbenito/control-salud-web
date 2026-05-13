-- Control Salud Web
-- Migration 030: Anunciador de sala (llamados desde agenda)

CREATE TABLE IF NOT EXISTS agenda_llamados (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  id_turno INT NOT NULL,
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
  KEY idx_llamados_clinica_estado_actualizado (id_clinica, estado_llamado, actualizado_en),
  KEY idx_llamados_turno (id_turno),
  KEY idx_llamados_doctor_estado_creado (id_doctor, estado_llamado, creado_en),
  KEY idx_llamados_hc_creado (nro_hc, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Compatibilidad de datos: corregir default textual previo si existiera.
UPDATE agenda_llamados SET estado_llamado = 'llego' WHERE estado_llamado = 'llegando';

