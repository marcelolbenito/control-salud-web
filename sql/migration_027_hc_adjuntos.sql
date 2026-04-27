-- Adjuntos para historia clinica: archivos y links asociados a cada nota.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS pacientes_hc_adjuntos (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  id_nota_hc INT NOT NULL,
  id_usuario INT NULL,
  tipo VARCHAR(20) NOT NULL COMMENT 'archivo|link',
  nombre VARCHAR(255) NOT NULL,
  url VARCHAR(1024) NULL,
  ruta_archivo VARCHAR(1024) NULL,
  mime VARCHAR(100) NULL,
  tamano_bytes INT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_hc_adj_clin_nota (id_clinica, id_nota_hc),
  KEY idx_hc_adj_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
