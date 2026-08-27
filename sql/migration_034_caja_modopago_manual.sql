-- Caja v2 — versión MANUAL (si migration_034_caja_modopago.sql da errores en phpMyAdmin).
-- Ejecutar en la base control_salud, paso por paso.

USE control_salud;

-- Paso 1: ¿ya existe la columna?
-- SHOW COLUMNS FROM caja LIKE 'modopago';

-- Paso 2: si NO existe, ejecutar UNA de estas dos (la que no falle):

-- 2a) Con columna de referencia (lo habitual):
ALTER TABLE `caja`
  ADD COLUMN `modopago` SMALLINT NULL DEFAULT NULL
  COMMENT '0=efectivo,1=otro,3=debito,4=credito,5=electronico'
  AFTER `idcoberturacaja`;

-- 2b) Si 2a falla (sin idcoberturacaja), usar esta y comentar 2a:
-- ALTER TABLE `caja`
--   ADD COLUMN `modopago` SMALLINT NULL DEFAULT NULL
--   COMMENT '0=efectivo,1=otro,3=debito,4=credito,5=electronico';

-- Paso 3 (opcional): copiar modopago desde backup legacy, solo si existe esa tabla:
-- UPDATE `caja` c
-- INNER JOIN `backup_legacy_Caja_20260409_131540` b ON b.id = c.id
-- SET c.modopago = b.modopago
-- WHERE c.modopago IS NULL AND b.modopago IS NOT NULL;

-- Paso 4: default efectivo donde quedó NULL:
UPDATE `caja`
SET modopago = 0
WHERE modopago IS NULL;

-- Verificar:
-- SHOW COLUMNS FROM caja LIKE 'modopago';
-- SELECT modopago, COUNT(*) FROM caja GROUP BY modopago;
