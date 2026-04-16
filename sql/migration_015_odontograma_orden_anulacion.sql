-- Odontograma: vínculo opcional a orden (Pacientes Ordenes) y anulación lógica con trazabilidad.
-- Ejecutar tras migration_014_odontograma.sql

SET NAMES utf8mb4;

ALTER TABLE pacientes_odontograma
  ADD COLUMN id_orden INT NULL COMMENT 'id Pacientes Ordenes' AFTER idusuario_web,
  ADD COLUMN anulado TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN anulado_motivo VARCHAR(255) NULL,
  ADD COLUMN anulado_en DATETIME NULL,
  ADD COLUMN anulado_por_usuario INT NULL,
  ADD KEY idx_odontograma_orden (id_orden);
