-- Sincroniza movimientos de caja desde tabla importada del backup SQL Server (`Caja`)
-- hacia la tabla web `caja`.
--
-- Requisito: que exista `Caja` en la misma BD (importación full del backup).
-- Si `Caja` no existe, esta migración fallará y no hará cambios.

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
  COALESCE(`iddoctor`, 0) AS `doctor`,
  DATE(`fechacaja`) AS `fechacaja`,
  COALESCE(`importecaja`, 0) AS `importecaja`,
  `idcoberturacaja`,
  NULLIF(TRIM(COALESCE(`turnocaja`, '')), '') AS `turnocaja`,
  NULL AS `observaciones`
FROM `Caja`
WHERE `iddoctor` IS NOT NULL
  AND `fechacaja` IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

