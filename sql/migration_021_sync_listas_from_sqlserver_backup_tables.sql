-- Copia catálogos desde tablas con nombre Access/SQL Server (espacios) hacia las tablas
-- `lista_*` que usa la web. Útil cuando ya importaste `migration_from_sqlserver_full.sql`
-- (sqlserver_backup_to_mysql_sql.py) y ves pocas obras sociales / prácticas en PHP porque
-- los datos están en `Lista Coberturas` pero no en `lista_coberturas`.
--
-- Requisitos: existan en MySQL las tablas origen (mismo nombre que en SQL Server, con backticks).
-- Si alguna tabla no existe en tu backup, comentá o borrá el bloque correspondiente y volvé a ejecutar.
--
-- Orden: planes antes que coberturas no hace falta invertir si no hay FK; igual vaciamos planes primero
-- por si `id_cobertura` apunta a coberturas.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- Lista Pais ----------
TRUNCATE TABLE `lista_pais`;
INSERT INTO `lista_pais` (`id`, `prioridad`, `nombre`)
SELECT `id`, `prioridad`, `nombre` FROM `Lista Pais`;

-- ---------- Lista Provincia ----------
TRUNCATE TABLE `lista_provincia`;
INSERT INTO `lista_provincia` (`id`, `prioridad`, `nombre`)
SELECT `id`, `prioridad`, `nombre` FROM `Lista Provincia`;

-- ---------- Lista Ciudad ----------
TRUNCATE TABLE `lista_ciudad`;
INSERT INTO `lista_ciudad` (`id`, `prioridad`, `nombre`)
SELECT `id`, `prioridad`, `nombre` FROM `Lista Ciudad`;

-- ---------- Lista Coberturas (obras sociales) ----------
TRUNCATE TABLE `lista_coberturas`;
INSERT INTO `lista_coberturas` (`id`, `prioridad`, `nombre`, `porcentaje_cobertura`, `plancober`)
SELECT `id`, `prioridad`, `nombre`, `Porcentaje_Cobertura`, `plancober` FROM `Lista Coberturas`;

-- ---------- Lista Tipo de documento ----------
TRUNCATE TABLE `lista_tipo_documento`;
INSERT INTO `lista_tipo_documento` (`id`, `prioridad`, `nombre`)
SELECT `id`, `prioridad`, `nombre` FROM `Lista Tipo de documento`;

-- ---------- Lista Ocupacion ----------
TRUNCATE TABLE `lista_ocupacion`;
INSERT INTO `lista_ocupacion` (`id`, `prioridad`, `nombre`)
SELECT `id`, `prioridad`, `nombre` FROM `Lista Ocupacion`;

-- ---------- Lista Estado civil ----------
TRUNCATE TABLE `lista_estado_civil`;
INSERT INTO `lista_estado_civil` (`id`, `prioridad`, `nombre`)
SELECT `id`, `prioridad`, `nombre` FROM `Lista Estado civil`;

-- ---------- Lista Etnia ----------
TRUNCATE TABLE `lista_etnia`;
INSERT INTO `lista_etnia` (`id`, `prioridad`, `nombre`)
SELECT `id`, `prioridad`, `nombre` FROM `Lista Etnia`;

-- ---------- Lista Relacion con el paciente ----------
TRUNCATE TABLE `lista_relacion_paciente`;
INSERT INTO `lista_relacion_paciente` (`id`, `prioridad`, `nombre`)
SELECT `id`, `prioridad`, `nombre` FROM `Lista Relacion con el paciente`;

-- ---------- Lista Estatus en el pais ----------
TRUNCATE TABLE `lista_estatus_pais`;
INSERT INTO `lista_estatus_pais` (`id`, `prioridad`, `nombre`)
SELECT `id`, `prioridad`, `nombre` FROM `Lista Estatus en el pais`;

-- ---------- Lista Practicas / Derivaciones / Sucursales (órdenes) ----------
-- En algunos volcados desde SQL Server estas tablas no existen o tienen otro nombre
-- (1146 si faltan). Se copian solo si la tabla origen está en la base.
-- Prácticas: en varios backups la tabla equivalente se llama `Nomenclador` (no `Lista Practicas`).
-- Para ver qué tenés: SHOW TABLES; o: SHOW TABLES WHERE Tables_in_control_salud LIKE '%Practica%';

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

DROP PROCEDURE IF EXISTS `_cs_sync_planes_with_fallback`$$
CREATE PROCEDURE `_cs_sync_planes_with_fallback`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'lista_planes'
  ) THEN
    BEGIN END;
  ELSE
    SET @src_planes = NULL;
    SELECT TABLE_NAME INTO @src_planes
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND LOWER(REPLACE(table_name, '_', ' ')) = 'lista planes'
    LIMIT 1;

    IF @src_planes IS NOT NULL THEN
      SET @idcob_col = NULL;
      SET @nom_col = NULL;
      SELECT COLUMN_NAME INTO @idcob_col
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = @src_planes
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
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = @src_planes
        AND LOWER(COLUMN_NAME) IN ('nombre', 'descripcion', 'denominacion', 'descrip', 'texto', 'codigo')
      ORDER BY
        CASE LOWER(COLUMN_NAME)
          WHEN 'nombre' THEN 1
          WHEN 'descripcion' THEN 2
          WHEN 'denominacion' THEN 3
          WHEN 'descrip' THEN 4
          WHEN 'texto' THEN 5
          WHEN 'codigo' THEN 6
          ELSE 9
        END
      LIMIT 1;

      TRUNCATE TABLE `lista_planes`;
      IF @idcob_col IS NULL AND @nom_col IS NULL THEN
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT `id`, NULL, CAST(`id` AS CHAR) FROM `', REPLACE(@src_planes, '`', ''), '`'
        );
      ELSEIF @idcob_col IS NULL THEN
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT `id`, NULL, CAST(`', REPLACE(@nom_col, '`', ''), '` AS CHAR(255)) FROM `', REPLACE(@src_planes, '`', ''), '`'
        );
      ELSEIF @nom_col IS NULL THEN
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT `id`, `', REPLACE(@idcob_col, '`', ''), '`, CAST(`id` AS CHAR) FROM `', REPLACE(@src_planes, '`', ''), '`'
        );
      ELSE
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT `id`, `', REPLACE(@idcob_col, '`', ''), '`, CAST(`', REPLACE(@nom_col, '`', ''), '` AS CHAR(255)) FROM `', REPLACE(@src_planes, '`', ''), '`'
        );
      END IF;
      PREPARE ps FROM @q;
      EXECUTE ps;
      DEALLOCATE PREPARE ps;
    ELSE
      SET @ordtbl = NULL;
      SELECT TABLE_NAME INTO @ordtbl
      FROM information_schema.tables
      WHERE table_schema = DATABASE()
        AND LOWER(REPLACE(REPLACE(table_name, ' ', ''), '_', '')) = 'pacientesordenes'
      LIMIT 1;
      IF @ordtbl IS NOT NULL THEN
        TRUNCATE TABLE `lista_planes`;
        SET @q = CONCAT(
          'INSERT INTO `lista_planes` (`id`, `id_cobertura`, `nombre`) ',
          'SELECT DISTINCT ',
          'o.idplan AS id, ',
          'CASE WHEN o.idobrasocial IS NULL OR o.idobrasocial <= 0 THEN NULL ELSE o.idobrasocial END AS id_cobertura, ',
          'CONCAT(''Plan '', o.idplan) AS nombre ',
          'FROM `', REPLACE(@ordtbl, '`', ''), '` o ',
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

DROP PROCEDURE IF EXISTS `_cs_sync_practicas_lista_o_nomenclador`$$
CREATE PROCEDURE `_cs_sync_practicas_lista_o_nomenclador`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'lista_practicas'
  ) THEN
    -- Sin destino: migration_023 u otro esquema
    BEGIN END;
  ELSEIF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND LOWER(REPLACE(table_name, '_', ' ')) = 'lista practicas'
  ) THEN
    SELECT TABLE_NAME INTO @prtbl FROM information_schema.tables
    WHERE table_schema = DATABASE() AND LOWER(REPLACE(table_name, '_', ' ')) = 'lista practicas'
    LIMIT 1;
    TRUNCATE TABLE `lista_practicas`;
    SET @q = CONCAT(
      'INSERT INTO `lista_practicas` (`id`, `prioridad`, `nombre`) ',
      'SELECT `id`, `prioridad`, `nombre` FROM `', REPLACE(@prtbl, '`', ''), '`'
    );
    PREPARE ps FROM @q;
    EXECUTE ps;
    DEALLOCATE PREPARE ps;
  ELSEIF EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND LOWER(REPLACE(table_name, ' ', '')) IN (
        'nomenclador',
        'nomencladores',
        'listanomenclador',
        'listanomencladores'
      )
  ) THEN
    SELECT TABLE_NAME INTO @nomtbl FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND LOWER(REPLACE(table_name, ' ', '')) IN (
        'nomenclador',
        'nomencladores',
        'listanomenclador',
        'listanomencladores'
      )
    LIMIT 1;
    TRUNCATE TABLE `lista_practicas`;
    SET @nomcol = NULL;
    SELECT COLUMN_NAME INTO @nomcol FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE() AND c.TABLE_NAME = @nomtbl
      AND LOWER(c.COLUMN_NAME) IN ('nombre', 'descripcion', 'denominacion', 'descrip', 'texto', 'codigo')
    ORDER BY
      CASE LOWER(c.COLUMN_NAME)
        WHEN 'nombre' THEN 1
        WHEN 'descripcion' THEN 2
        WHEN 'denominacion' THEN 3
        WHEN 'descrip' THEN 4
        WHEN 'texto' THEN 5
        WHEN 'codigo' THEN 6
        ELSE 9
      END
    LIMIT 1;
    SELECT COUNT(*) INTO @hp FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE() AND c.TABLE_NAME = @nomtbl
      AND LOWER(c.COLUMN_NAME) = 'prioridad';
    IF @nomcol IS NULL THEN
      SET @q = CONCAT(
        'INSERT INTO `lista_practicas` (`id`, `prioridad`, `nombre`) ',
        'SELECT `id`, NULL, CAST(`id` AS CHAR) FROM `', REPLACE(@nomtbl, '`', ''), '`'
      );
    ELSEIF @hp > 0 THEN
      SET @q = CONCAT(
        'INSERT INTO `lista_practicas` (`id`, `prioridad`, `nombre`) ',
        'SELECT `id`, `prioridad`, CAST(`', REPLACE(@nomcol, '`', ''), '` AS CHAR(255)) FROM `', REPLACE(@nomtbl, '`', ''), '`'
      );
    ELSE
      SET @q = CONCAT(
        'INSERT INTO `lista_practicas` (`id`, `prioridad`, `nombre`) ',
        'SELECT `id`, NULL, CAST(`', REPLACE(@nomcol, '`', ''), '` AS CHAR(255)) FROM `', REPLACE(@nomtbl, '`', ''), '`'
      );
    END IF;
    PREPARE ps FROM @q;
    EXECUTE ps;
    DEALLOCATE PREPARE ps;
  ELSE
    SET @ordtbl = NULL;
    SELECT TABLE_NAME INTO @ordtbl
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND LOWER(REPLACE(REPLACE(table_name, ' ', ''), '_', '')) = 'pacientesordenes'
    LIMIT 1;
    IF @ordtbl IS NOT NULL THEN
      TRUNCATE TABLE `lista_practicas`;
      SET @q = CONCAT(
        'INSERT INTO `lista_practicas` (`id`, `prioridad`, `nombre`) ',
        'SELECT DISTINCT ',
        'o.idpractica AS id, ',
        'NULL AS prioridad, ',
        'CONCAT(''Práctica '', o.idpractica) AS nombre ',
        'FROM `', REPLACE(@ordtbl, '`', ''), '` o ',
        'WHERE o.idpractica IS NOT NULL AND o.idpractica > 0 ',
        'ORDER BY o.idpractica'
      );
      PREPARE ps FROM @q;
      EXECUTE ps;
      DEALLOCATE PREPARE ps;
    END IF;
  END IF;
END$$

DELIMITER ;

CALL `_cs_sync_planes_with_fallback`();
CALL `_cs_sync_practicas_lista_o_nomenclador`();
CALL `_cs_sync_lista_3_if_exists`('Lista Derivaciones', 'lista_derivaciones');
CALL `_cs_sync_lista_3_if_exists`('Lista Derivadores', 'lista_derivaciones');
CALL `_cs_sync_lista_3_if_exists`('Lista Sucursales', 'lista_sucursales');

DROP PROCEDURE IF EXISTS `_cs_sync_planes_with_fallback`;
DROP PROCEDURE IF EXISTS `_cs_sync_practicas_lista_o_nomenclador`;
DROP PROCEDURE IF EXISTS `_cs_sync_lista_3_if_exists`;

SET FOREIGN_KEY_CHECKS = 1;
