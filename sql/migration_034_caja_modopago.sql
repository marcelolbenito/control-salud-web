-- Caja v2: columna modopago (medio de pago, como en el exe legacy).
-- Idempotente: se puede ejecutar más de una vez.
--
-- IMPORTANTE en producción:
-- 1) Seleccioná la base correcta antes de ejecutar (USE control_salud;)
-- 2) Ejecutá el archivo COMPLETO de una vez (no línea por línea en phpMyAdmin).
-- 3) Si el cliente SQL no soporta DELIMITER, usá migration_034_caja_modopago_manual.sql

SET NAMES utf8mb4;

DELIMITER $$

DROP PROCEDURE IF EXISTS `_cs_migration_034_modopago`$$
CREATE PROCEDURE `_cs_migration_034_modopago`()
BEGIN
  DECLARE v_has_modopago INT DEFAULT 0;
  DECLARE v_has_idcobertura INT DEFAULT 0;
  DECLARE v_has_backup INT DEFAULT 0;
  DECLARE v_backup_modopago INT DEFAULT 0;

  SELECT COUNT(*) INTO v_has_modopago
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'caja'
    AND COLUMN_NAME = 'modopago';

  IF v_has_modopago = 0 THEN
    SELECT COUNT(*) INTO v_has_idcobertura
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'caja'
      AND COLUMN_NAME = 'idcoberturacaja';

    IF v_has_idcobertura > 0 THEN
      ALTER TABLE `caja`
        ADD COLUMN `modopago` SMALLINT NULL DEFAULT NULL
        COMMENT '0=efectivo,1=otro,3=debito,4=credito,5=electronico'
        AFTER `idcoberturacaja`;
    ELSE
      ALTER TABLE `caja`
        ADD COLUMN `modopago` SMALLINT NULL DEFAULT NULL
        COMMENT '0=efectivo,1=otro,3=debito,4=credito,5=electronico';
    END IF;
  END IF;

  SELECT COUNT(*) INTO v_has_backup
  FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'backup_legacy_Caja_20260409_131540';

  IF v_has_backup > 0 THEN
    SELECT COUNT(*) INTO v_backup_modopago
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'backup_legacy_Caja_20260409_131540'
      AND COLUMN_NAME = 'modopago';

    IF v_backup_modopago > 0 THEN
      UPDATE `caja` c
      INNER JOIN `backup_legacy_Caja_20260409_131540` b ON b.id = c.id
      SET c.modopago = b.modopago
      WHERE c.modopago IS NULL
        AND b.modopago IS NOT NULL;
    END IF;
  END IF;

  UPDATE `caja`
  SET modopago = 0
  WHERE modopago IS NULL;
END$$

DELIMITER ;

CALL `_cs_migration_034_modopago`();
DROP PROCEDURE IF EXISTS `_cs_migration_034_modopago`;
