-- Historia clinica inmutable: cada evolucion se registra como nota fechada.
-- No se define UPDATE/DELETE en app para conservar trazabilidad.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS pacientes_hc_notas (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  id_paciente INT NOT NULL,
  id_usuario INT NULL,
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  texto MEDIUMTEXT NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_hc_notas_clin_pac_fecha (id_clinica, id_paciente, fecha_hora),
  KEY idx_hc_notas_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
