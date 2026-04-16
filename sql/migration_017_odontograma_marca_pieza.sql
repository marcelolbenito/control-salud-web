-- Marcas de pieza completa en el mapa (ausente, extracción, corona, etc.).
-- Requiere migration_016. Valores mapa_overlay: pieza_diagonal | pieza_x | pieza_circulo | pieza_relleno

SET NAMES utf8mb4;

ALTER TABLE lista_odontograma_codigos
  ADD COLUMN mapa_overlay VARCHAR(24) NULL COMMENT 'Vacío=caras; pieza_diagonal|pieza_x|pieza_circulo|pieza_relleno' AFTER color_hex;

ALTER TABLE pacientes_odontograma_superficies
  MODIFY COLUMN cara CHAR(1) NOT NULL COMMENT 'M O D V L P (P=marca pieza completa)';
