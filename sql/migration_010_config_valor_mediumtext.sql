-- Plantillas RTF de Config.exe pueden superar 64 KB; amplía valor para el sembrado desde backup.
SET NAMES utf8mb4;

ALTER TABLE `config` MODIFY COLUMN `valor` MEDIUMTEXT NULL;
