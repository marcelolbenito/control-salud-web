-- Normaliza `Lista Precios` del sistema original en `lista_precios`.
-- Permite buscar arancel por obra social + practica + plan desde Ordenes.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lista_precios (
  id INT NOT NULL PRIMARY KEY,
  idobrasocial INT NULL,
  idpractica INT NULL,
  costopaciente DECIMAL(12,2) NULL,
  costocobertura DECIMAL(12,2) NULL,
  usarporcentaje TINYINT(1) NULL,
  costoporcentaje DECIMAL(8,4) NULL,
  cobradr DECIMAL(12,2) NULL,
  idplan INT NULL,
  KEY idx_lista_precios_busqueda (idobrasocial, idpractica, idplan),
  KEY idx_lista_precios_practica (idpractica)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER $$

DROP PROCEDURE IF EXISTS `_cs_sync_lista_precios`$$
CREATE PROCEDURE `_cs_sync_lista_precios`()
BEGIN
  IF EXISTS (
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'Lista Precios'
  ) THEN
    INSERT INTO lista_precios (
      id,
      idobrasocial,
      idpractica,
      costopaciente,
      costocobertura,
      usarporcentaje,
      costoporcentaje,
      cobradr,
      idplan
    )
    SELECT
      id,
      idobrasocial,
      idpractica,
      costopaciente,
      costocobertura,
      usarporcentaje,
      costoporcentaje,
      cobradr,
      idplan
    FROM `Lista Precios`
    ON DUPLICATE KEY UPDATE
      idobrasocial = VALUES(idobrasocial),
      idpractica = VALUES(idpractica),
      costopaciente = VALUES(costopaciente),
      costocobertura = VALUES(costocobertura),
      usarporcentaje = VALUES(usarporcentaje),
      costoporcentaje = VALUES(costoporcentaje),
      cobradr = VALUES(cobradr),
      idplan = VALUES(idplan);
  END IF;
END$$

CALL `_cs_sync_lista_precios`()$$
DROP PROCEDURE IF EXISTS `_cs_sync_lista_precios`$$

DELIMITER ;
