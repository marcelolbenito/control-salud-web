-- Vincula usuarios con profesional de lista_doctores (útil para rol doctor).

SET NAMES utf8mb4;

SET @has_id_doctor := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'id_doctor'
);

SET @sql := IF(
  @has_id_doctor = 0,
  'ALTER TABLE usuarios ADD COLUMN id_doctor INT NULL AFTER id_clinica',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
