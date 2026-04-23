-- Sincroniza SOLO catálogos de Órdenes desde tablas backup con nombres variables.
-- Objetivo: poblar lista_planes / lista_practicas aunque falten otras "Lista *" legacy.
--
-- Uso recomendado:
--   1) Ejecutar sql/migration_023_listas_ordenes_catalogos.sql (estructura destino)
--   2) Ejecutar este script
--   3) Verificar conteos al final

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELIMITER $$

DROP PROCEDURE IF EXISTS `_cs_sync_lista_3_if_exists`$$
CREATE PROCEDURE `_cs_sync_lista_3_if_exists`(IN p_src VARCHAR(128), IN p_dst VARCHAR(64))
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = p_src
  ) THEN
    SET @q = CONCAT('TRUNCATE TABLE `', p_dst, '`');
    PREPARE ps FROM @q;
    EXECUTE ps;
    DEALLOCATE PREPARE ps;
    SET @q = CONCAT(
      'INSERT INTO `', p_dst, '` (`id`, `prioridad`, `nombre`) ',
      'SELECT `id`, `prioridad`, `nombre` FROM `', p_src, '`'
    );
    PREPARE ps FROM @q;
    EXECUTE ps;
    DEALLOCATE PREPARE ps;
  END IF;
END$$

DROP PROCEDURE IF EXISTS `_cs_sync_planes_resiliente`$$
CREATE PROCEDURE `_cs_sync_planes_resiliente`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'lista_planes'
  ) THEN
    BEGIN END;
  ELSE
    SET @src = NULL;
    SELECT TABLE_NAME INTO @src
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND LOWER(REPLACE(REPLACE(table_name, ' ', ''), '_', '')) IN (
        'listaplanes', 'listaplan', 'planes', 'planesobra'
      )
    ORDER BY
      CASE LOWER(REPLACE(REPLACE(table_name, ' ', ''), '_', ''))
        WHEN 'listaplanes' THEN 1
        WHEN 'listaplan' THEN 2
        WHEN 'planes' THEN 3
        ELSE 9
      END
    LIMIT 1;

    IF @src IS NOT NULL THEN
      SET @idcob_col = NULL;
      SET @nom_col = NULL;
      SELECT COLUMN_NAME INTO @idcob_col
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @src
        AND LOWER(COLUMN_NAME) IN ('idcobertura', 'id_cobertura', 'idobrasocial', 'idobra_social')
      ORDER BY
        CASE LOWER(COLUMN_NAME)
          WHEN 'idcobertura' THEN 1
          WHEN 'id_cobertura' THEN 2
          WHEN 'idobrasocial' THEN 3
          WHEN 'idobra_social' THEN 4
          ELSE 9
        END
      LIMIT 1;
      SELECT COLUMN_NAME INTO @nom_col
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @src
        AND LOWER(COLUMN_NAME) IN ('nombre', 'descripcion', 'denominacion', 'descrip', 'texto', 'codigo', 'plan')
      ORDER BY
        CASE LOWER(COLUMN_NAME)
          WHEN 'nombre' THEN 1
          WHEN 'descripcion' THEN 2
          WHEN 'denominacion' THEN 3
          WHEN 'descrip' THEN 4
          WHEN 'texto' THEN 5
          WHEN 'codigo' THEN 6
          WHEN 'plan' THEN 7
          ELSE 9
        END
      LIMIT 1;

      TRUNCATE TABLE `lista_planes`;
      IF @idcob_col IS NULL AND @nom_col IS NULL THEN
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT `id`, NULL, CAST(`id` AS CHAR) FROM `', REPLACE(@src, '`', ''), '`'
        );
      ELSEIF @idcob_col IS NULL THEN
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT `id`, NULL, CAST(`', REPLACE(@nom_col, '`', ''), '` AS CHAR(255)) FROM `', REPLACE(@src, '`', ''), '`'
        );
      ELSEIF @nom_col IS NULL THEN
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT `id`, `', REPLACE(@idcob_col, '`', ''), '`, CAST(`id` AS CHAR) FROM `', REPLACE(@src, '`', ''), '`'
        );
      ELSE
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT `id`, `', REPLACE(@idcob_col, '`', ''), '`, CAST(`', REPLACE(@nom_col, '`', ''), '` AS CHAR(255)) FROM `', REPLACE(@src, '`', ''), '`'
        );
      END IF;
      PREPARE ps FROM @q;
      EXECUTE ps;
      DEALLOCATE PREPARE ps;
    ELSE
      SET @ord = NULL;
      SELECT TABLE_NAME INTO @ord
      FROM information_schema.tables
      WHERE table_schema = DATABASE()
        AND LOWER(REPLACE(REPLACE(table_name, ' ', ''), '_', '')) = 'pacientesordenes'
      LIMIT 1;
      IF @ord IS NOT NULL THEN
        TRUNCATE TABLE `lista_planes`;
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT DISTINCT o.idplan, ',
          'CASE WHEN o.idobrasocial IS NULL OR o.idobrasocial <= 0 THEN NULL ELSE o.idobrasocial END, ',
          'CONCAT(''Plan '', o.idplan) ',
          'FROM `', REPLACE(@ord, '`', ''), '` o ',
          'WHERE o.idplan IS NOT NULL AND o.idplan > 0 ',
          'ORDER BY o.idplan'
        );
        PREPARE ps FROM @q;
        EXECUTE ps;
        DEALLOCATE PREPARE ps;
      END IF;
    END IF;
  END IF;
END$$

DROP PROCEDURE IF EXISTS `_cs_sync_practicas_resiliente`$$
CREATE PROCEDURE `_cs_sync_practicas_resiliente`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'lista_practicas'
  ) THEN
    BEGIN END;
  ELSE
    SET @src = NULL;
    SELECT TABLE_NAME INTO @src
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND LOWER(REPLACE(REPLACE(table_name, ' ', ''), '_', '')) IN (
        'listapracticas', 'listapractica', 'practicas', 'practica',
        'nomenclador', 'nomencladores', 'listanomenclador', 'listanomencladores'
      )
      AND LOWER(table_name) <> 'lista_practicas'
    ORDER BY
      CASE LOWER(REPLACE(REPLACE(table_name, ' ', ''), '_', ''))
        WHEN 'listapracticas' THEN 1
        WHEN 'listapractica' THEN 2
        WHEN 'nomenclador' THEN 3
        WHEN 'nomencladores' THEN 4
        WHEN 'listanomenclador' THEN 5
        WHEN 'listanomencladores' THEN 6
        WHEN 'practicas' THEN 7
        WHEN 'practica' THEN 8
        ELSE 9
      END
    LIMIT 1;

    IF @src IS NOT NULL THEN
      SET @nom_col = NULL;
      SELECT COLUMN_NAME INTO @nom_col
      FROM information_schema.COLUMNS c
      WHERE c.TABLE_SCHEMA = DATABASE() AND c.TABLE_NAME = @src
        AND LOWER(c.COLUMN_NAME) IN ('nombre', 'descripcion', 'denominacion', 'descrip', 'texto', 'codigo', 'practica')
      ORDER BY
        CASE LOWER(c.COLUMN_NAME)
          WHEN 'nombre' THEN 1
          WHEN 'descripcion' THEN 2
          WHEN 'denominacion' THEN 3
          WHEN 'descrip' THEN 4
          WHEN 'texto' THEN 5
          WHEN 'codigo' THEN 6
          WHEN 'practica' THEN 7
          ELSE 9
        END
      LIMIT 1;
      SELECT COUNT(*) INTO @hp
      FROM information_schema.COLUMNS c
      WHERE c.TABLE_SCHEMA = DATABASE() AND c.TABLE_NAME = @src
        AND LOWER(c.COLUMN_NAME) = 'prioridad';

      TRUNCATE TABLE `lista_practicas`;
      IF @nom_col IS NULL THEN
        SET @q = CONCAT(
          'INSERT INTO `lista_practicas` (`id`, `prioridad`, `nombre`) ',
          'SELECT `id`, NULL, CAST(`id` AS CHAR) FROM `', REPLACE(@src, '`', ''), '`'
        );
      ELSEIF @hp > 0 THEN
        SET @q = CONCAT(
          'INSERT INTO `lista_practicas` (`id`, `prioridad`, `nombre`) ',
          'SELECT `id`, `prioridad`, CAST(`', REPLACE(@nom_col, '`', ''), '` AS CHAR(255)) FROM `', REPLACE(@src, '`', ''), '`'
        );
      ELSE
        SET @q = CONCAT(
          'INSERT INTO `lista_practicas` (`id`, `prioridad`, `nombre`) ',
          'SELECT `id`, NULL, CAST(`', REPLACE(@nom_col, '`', ''), '` AS CHAR(255)) FROM `', REPLACE(@src, '`', ''), '`'
        );
      END IF;
      PREPARE ps FROM @q;
      EXECUTE ps;
      DEALLOCATE PREPARE ps;
    ELSE
      SET @ord = NULL;
      SELECT TABLE_NAME INTO @ord
      FROM information_schema.tables
      WHERE table_schema = DATABASE()
        AND LOWER(REPLACE(REPLACE(table_name, ' ', ''), '_', '')) = 'pacientesordenes'
      LIMIT 1;
      IF @ord IS NOT NULL THEN
        TRUNCATE TABLE `lista_practicas`;
        SET @q = CONCAT(
          'INSERT INTO `lista_practicas` (`id`, `prioridad`, `nombre`) ',
          'SELECT DISTINCT o.idpractica, NULL, CONCAT(''Práctica '', o.idpractica) ',
          'FROM `', REPLACE(@ord, '`', ''), '` o ',
          'WHERE o.idpractica IS NOT NULL AND o.idpractica > 0 ',
          'ORDER BY o.idpractica'
        );
        PREPARE ps FROM @q;
        EXECUTE ps;
        DEALLOCATE PREPARE ps;
      END IF;
    END IF;
  END IF;
END$$

DROP PROCEDURE IF EXISTS `_cs_sync_derivaciones_resiliente`$$
CREATE PROCEDURE `_cs_sync_derivaciones_resiliente`()
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'Lista Derivaciones'
  ) THEN
    TRUNCATE TABLE `lista_derivaciones`;
    INSERT INTO `lista_derivaciones` (`id`, `prioridad`, `nombre`)
    SELECT `id`, `prioridad`, `nombre`
    FROM `Lista Derivaciones`;
  ELSEIF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'Lista Derivadores'
  ) THEN
    TRUNCATE TABLE `lista_derivaciones`;
    INSERT INTO `lista_derivaciones` (`id`, `prioridad`, `nombre`)
    SELECT `id`, NULL, `nombre`
    FROM `Lista Derivadores`;
  END IF;
END$$

DELIMITER ;

CALL `_cs_sync_planes_resiliente`();
CALL `_cs_sync_practicas_resiliente`();
CALL `_cs_sync_derivaciones_resiliente`();
CALL `_cs_sync_lista_3_if_exists`('Lista Sucursales', 'lista_sucursales');

DROP PROCEDURE IF EXISTS `_cs_sync_planes_resiliente`;
DROP PROCEDURE IF EXISTS `_cs_sync_practicas_resiliente`;
DROP PROCEDURE IF EXISTS `_cs_sync_derivaciones_resiliente`;
DROP PROCEDURE IF EXISTS `_cs_sync_lista_3_if_exists`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'lista_planes' AS tabla, COUNT(*) AS filas FROM lista_planes
UNION ALL
SELECT 'lista_practicas', COUNT(*) FROM lista_practicas
UNION ALL
SELECT 'lista_derivaciones', COUNT(*) FROM lista_derivaciones
UNION ALL
SELECT 'lista_sucursales', COUNT(*) FROM lista_sucursales;
