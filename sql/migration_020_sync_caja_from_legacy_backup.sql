-- Sincroniza movimientos de caja desde backup legacy importado
-- (`backup_legacy_Caja_20260409_131540`) hacia la tabla web `caja`.
--
-- Usa mapeo por columnas equivalentes del backup:
-- doctor -> doctor
-- fecha -> fechacaja
-- importe -> importecaja
-- idcoberturacaja -> idcoberturacaja
-- turnocaja -> turnocaja
-- detalle -> observaciones
--
-- IMPORTANTE:
-- - Esta migración reemplaza el contenido actual de `caja`.
-- - Ejecutar solo si verificaste que la tabla fuente es la correcta.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `caja`;

INSERT INTO `caja` (
  `doctor`,
  `fechacaja`,
  `importecaja`,
  `idcoberturacaja`,
  `turnocaja`,
  `observaciones`
)
SELECT
  COALESCE(`doctor`, 0) AS `doctor`,
  DATE(`fecha`) AS `fechacaja`,
  COALESCE(`importe`, 0) AS `importecaja`,
  `idcoberturacaja`,
  NULLIF(TRIM(COALESCE(`turnocaja`, '')), '') AS `turnocaja`,
  NULLIF(TRIM(COALESCE(`detalle`, '')), '') AS `observaciones`
FROM `backup_legacy_Caja_20260409_131540`
WHERE `doctor` IS NOT NULL
  AND `fecha` IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

