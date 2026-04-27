-- Roles de usuario para permisos por módulo.

SET NAMES utf8mb4;

SET @has_rol := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'rol'
);

SET @sql := IF(
  @has_rol = 0,
  'ALTER TABLE usuarios ADD COLUMN rol VARCHAR(30) NOT NULL DEFAULT ''admin_clinica'' AFTER id_clinica',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE usuarios
SET rol = 'admin_clinica'
WHERE rol IS NULL OR TRIM(rol) = '';

UPDATE usuarios
SET rol = 'admin_clinica'
WHERE rol NOT IN ('superadmin', 'admin_clinica', 'doctor');
