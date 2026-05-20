-- Migration 021: eliminar muestras y bioquimicos
--
-- Cambios:
--   1) Migrar el firmante (responsable principal) de lab_bioquimicos a lab_config.
--   2) Drop columna bioquimico_id de lab_pedidos (con su FK) y lab_pagos (con su FK).
--   3) Drop tabla lab_muestras.
--   4) Drop tabla lab_bioquimicos.
--
-- El paso 1 toma al unico bioquimico con es_responsable_principal=1 y lo persiste
-- en lab_config bajo las claves firmante_apellido, firmante_nombres,
-- firmante_matricula, firmante_titulo, firmante_firma_path. Si no hay ninguno,
-- inserta claves vacias (el admin completa desde /configuracion).

START TRANSACTION;

-- 1) Volcar firmante a lab_config.
INSERT INTO lab_config (clave, valor, descripcion)
SELECT 'firmante_apellido', COALESCE(b.apellido, ''), 'Apellido del firmante (responsable de informes)'
FROM (SELECT apellido FROM lab_bioquimicos WHERE es_responsable_principal = 1 AND deleted_at IS NULL LIMIT 1) b
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO lab_config (clave, valor, descripcion)
SELECT 'firmante_nombres', COALESCE(b.nombres, ''), 'Nombres del firmante'
FROM (SELECT nombres FROM lab_bioquimicos WHERE es_responsable_principal = 1 AND deleted_at IS NULL LIMIT 1) b
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO lab_config (clave, valor, descripcion)
SELECT 'firmante_matricula', COALESCE(b.matricula, ''), 'Matricula profesional del firmante'
FROM (SELECT matricula FROM lab_bioquimicos WHERE es_responsable_principal = 1 AND deleted_at IS NULL LIMIT 1) b
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO lab_config (clave, valor, descripcion)
SELECT 'firmante_titulo', COALESCE(b.titulo, 'Bioquimica'), 'Titulo del firmante (Bioquimica, Doctor en Bioquimica, etc.)'
FROM (SELECT titulo FROM lab_bioquimicos WHERE es_responsable_principal = 1 AND deleted_at IS NULL LIMIT 1) b
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO lab_config (clave, valor, descripcion)
SELECT 'firmante_firma_path', b.firma_path, 'Path relativo a la imagen de firma (en storage/)'
FROM (SELECT firma_path FROM lab_bioquimicos WHERE es_responsable_principal = 1 AND deleted_at IS NULL LIMIT 1) b
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- Si no habia ningun principal, asegurar que las claves existan vacias.
INSERT IGNORE INTO lab_config (clave, valor, descripcion) VALUES
    ('firmante_apellido',   '', 'Apellido del firmante (responsable de informes)'),
    ('firmante_nombres',    '', 'Nombres del firmante'),
    ('firmante_matricula',  '', 'Matricula profesional del firmante'),
    ('firmante_titulo',     'Bioquimica', 'Titulo del firmante'),
    ('firmante_firma_path', NULL, 'Path relativo a la imagen de firma');

-- 2) Drop FK + columna bioquimico_id de lab_pedidos.
SET @fk := (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'lab_pedidos'
      AND COLUMN_NAME = 'bioquimico_id'
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);
SET @sql := IF(@fk IS NULL, 'SELECT 1', CONCAT('ALTER TABLE lab_pedidos DROP FOREIGN KEY `', @fk, '`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @hascol := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lab_pedidos' AND COLUMN_NAME = 'bioquimico_id'
);
SET @sql := IF(@hascol = 0, 'SELECT 1', 'ALTER TABLE lab_pedidos DROP COLUMN bioquimico_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Drop FK + columna bioquimico_id de lab_pagos.
SET @fk := (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'lab_pagos'
      AND COLUMN_NAME = 'bioquimico_id'
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);
SET @sql := IF(@fk IS NULL, 'SELECT 1', CONCAT('ALTER TABLE lab_pagos DROP FOREIGN KEY `', @fk, '`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @hascol := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lab_pagos' AND COLUMN_NAME = 'bioquimico_id'
);
SET @sql := IF(@hascol = 0, 'SELECT 1', 'ALTER TABLE lab_pagos DROP COLUMN bioquimico_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Drop tablas.
DROP TABLE IF EXISTS lab_muestras;
DROP TABLE IF EXISTS lab_bioquimicos;

COMMIT;
