-- Versión MANUAL de migration_038 (sin PREPARE) — útil en phpMyAdmin.
-- 1) Asegura columnas en pacientes_hc_notas
-- 2) Importa desde `Consultas` si existe (idempotente por id_consulta_exe)
--
-- Si ya exportás `pacientes_hc_notas` desde local (con notas del exe),
-- alcanza con la parte A (columnas) o con el dump que ya trae la estructura.

SET NAMES utf8mb4;

-- ========== A) Columnas (omitir las que ya existan si falla "Duplicate column") ==========
ALTER TABLE `pacientes_hc_notas`
  ADD COLUMN `origen` VARCHAR(30) NOT NULL DEFAULT 'web' AFTER `texto`;

ALTER TABLE `pacientes_hc_notas`
  ADD COLUMN `id_consulta_exe` INT NULL AFTER `origen`;

ALTER TABLE `pacientes_hc_notas`
  ADD COLUMN `medico_nombre` VARCHAR(120) NULL AFTER `id_consulta_exe`;

ALTER TABLE `pacientes_hc_notas`
  ADD UNIQUE KEY `uniq_hc_consulta_exe` (`id_consulta_exe`);

-- ========== B) Import Consultas → HC (requiere tabla `Consultas`) ==========
INSERT INTO `pacientes_hc_notas` (
  `id_clinica`,
  `id_paciente`,
  `id_usuario`,
  `fecha_hora`,
  `texto`,
  `origen`,
  `id_consulta_exe`,
  `medico_nombre`,
  `creado_en`
)
SELECT
  COALESCE(p.id_clinica, 1) AS id_clinica,
  p.id AS id_paciente,
  NULL AS id_usuario,
  COALESCE(c.`Fecha`, NOW()) AS fecha_hora,
  TRIM(BOTH CHAR(10) FROM CONCAT_WS(CHAR(10),
    NULLIF(CONCAT('Motivo: ', NULLIF(TRIM(CAST(c.`detallemotivo` AS CHAR)), '')), 'Motivo: '),
    NULLIF(CONCAT('Diagnóstico: ', NULLIF(TRIM(CAST(c.`detallediagnos` AS CHAR)), '')), 'Diagnóstico: '),
    NULLIF(CONCAT('Tratamiento: ', NULLIF(TRIM(CAST(c.`detalletrata` AS CHAR)), '')), 'Tratamiento: '),
    NULLIF(CONCAT('Evolución: ', NULLIF(TRIM(CAST(c.`histoenferactu` AS CHAR)), '')), 'Evolución: '),
    NULLIF(CONCAT('Estudios solicitados: ', NULLIF(TRIM(CAST(c.`detalleestusoli` AS CHAR)), '')), 'Estudios solicitados: '),
    NULLIF(CONCAT('Estudios realizados: ', NULLIF(TRIM(CAST(c.`detalleestureali` AS CHAR)), '')), 'Estudios realizados: '),
    NULLIF(CONCAT('Observaciones: ', NULLIF(TRIM(CAST(c.`observaciones` AS CHAR)), '')), 'Observaciones: '),
    CONCAT('— Consulta del sistema anterior #', c.`id`)
  )) AS texto,
  'consulta_exe' AS origen,
  c.`id` AS id_consulta_exe,
  NULLIF(TRIM(COALESCE(d.`NomDoc`, ld.`nombre`, '')), '') AS medico_nombre,
  NOW() AS creado_en
FROM `Consultas` c
INNER JOIN `pacientes` p ON p.`NroHC` = c.`NroPaci`
LEFT JOIN `Lista Doctores` d ON d.`id` = c.`iddoctor`
LEFT JOIN `lista_doctores` ld ON ld.`id` = c.`iddoctor`
WHERE (c.`anulada` IS NULL OR c.`anulada` = 0)
  AND (
    NULLIF(TRIM(CAST(c.`detallemotivo` AS CHAR)), '') IS NOT NULL
    OR NULLIF(TRIM(CAST(c.`detallediagnos` AS CHAR)), '') IS NOT NULL
    OR NULLIF(TRIM(CAST(c.`detalletrata` AS CHAR)), '') IS NOT NULL
    OR NULLIF(TRIM(CAST(c.`histoenferactu` AS CHAR)), '') IS NOT NULL
    OR NULLIF(TRIM(CAST(c.`detalleestusoli` AS CHAR)), '') IS NOT NULL
    OR NULLIF(TRIM(CAST(c.`detalleestureali` AS CHAR)), '') IS NOT NULL
    OR NULLIF(TRIM(CAST(c.`observaciones` AS CHAR)), '') IS NOT NULL
  )
  AND NOT EXISTS (
    SELECT 1 FROM `pacientes_hc_notas` n
    WHERE n.`id_consulta_exe` = c.`id`
  );

SELECT
  (SELECT COUNT(*) FROM `pacientes_hc_notas` WHERE origen = 'consulta_exe') AS notas_desde_consultas,
  (SELECT COUNT(*) FROM `pacientes_hc_notas` WHERE origen = 'web' OR origen = '') AS notas_web;
