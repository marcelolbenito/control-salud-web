-- Expone en la web el código real de práctica usado por el nomenclador del EXE.
-- La relación de órdenes y aranceles continúa usando lista_practicas.id.

SET @has_codigo := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'lista_practicas'
    AND COLUMN_NAME = 'codigo'
);
SET @sql := IF(
  @has_codigo = 0,
  'ALTER TABLE `lista_practicas` ADD COLUMN `codigo` VARCHAR(15) NULL AFTER `prioridad`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_nomenclador := (
  SELECT COUNT(*)
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Lista Nomenclador'
);
SET @sql := IF(
  @has_nomenclador > 0,
  'UPDATE `lista_practicas` p INNER JOIN `Lista Nomenclador` n ON n.id = p.id SET p.codigo = NULLIF(TRIM(n.codigo), '''')',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_idx := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'lista_practicas'
    AND INDEX_NAME = 'idx_lista_practicas_codigo'
);
SET @sql := IF(
  @has_idx = 0,
  'CREATE INDEX `idx_lista_practicas_codigo` ON `lista_practicas` (`codigo`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
