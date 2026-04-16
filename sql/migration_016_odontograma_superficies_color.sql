-- Mapa por superficies: color por código clínico + estado actual por cara (M,O,D,V,L,I).
-- Requiere migration_014 (lista_odontograma_codigos).

SET NAMES utf8mb4;

ALTER TABLE lista_odontograma_codigos
  ADD COLUMN color_hex VARCHAR(7) NULL COMMENT 'Color en mapa (#RRGGBB)' AFTER nombre;

-- Tonos más saturados para que se distingan bien en el mapa (fondo claro).
UPDATE lista_odontograma_codigos SET color_hex = CASE id
  WHEN 1 THEN '#dc2626'
  WHEN 2 THEN '#2563eb'
  WHEN 3 THEN '#991b1b'
  WHEN 4 THEN '#ca8a04'
  WHEN 5 THEN '#7c3aed'
  WHEN 6 THEN '#047857'
  WHEN 7 THEN '#ea580c'
  WHEN 8 THEN '#be185d'
  WHEN 9 THEN '#4338ca'
  WHEN 10 THEN '#404040'
  ELSE '#64748b'
END
WHERE color_hex IS NULL;

CREATE TABLE IF NOT EXISTS pacientes_odontograma_superficies (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  NroHC INT NOT NULL,
  pieza_fdi SMALLINT NOT NULL,
  cara CHAR(1) NOT NULL COMMENT 'M O D V L I',
  id_codigo INT NOT NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  idusuario_web INT NULL,
  UNIQUE KEY uk_odontograma_superficie (NroHC, pieza_fdi, cara),
  KEY idx_odontograma_sup_nrohc (NroHC),
  CONSTRAINT fk_odontograma_sup_codigo FOREIGN KEY (id_codigo) REFERENCES lista_odontograma_codigos (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
