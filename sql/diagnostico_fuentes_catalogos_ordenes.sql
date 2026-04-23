-- Diagnóstico de fuentes para catálogos de Órdenes (planes/prácticas)
-- Ejecutar en la base donde importaste el backup.
-- No modifica datos.

SET NAMES utf8mb4;

-- 1) Tablas candidatas por nombre
SELECT
  t.table_name
FROM information_schema.tables t
WHERE t.table_schema = DATABASE()
  AND (
    LOWER(t.table_name) LIKE '%pract%'
    OR LOWER(t.table_name) LIKE '%nomencl%'
    OR LOWER(t.table_name) LIKE '%plan%'
    OR LOWER(t.table_name) LIKE '%manten%'
    OR LOWER(t.table_name) LIKE '%orden%'
  )
ORDER BY t.table_name;

-- 2) Columnas candidatas en toda la BD
SELECT
  c.table_name,
  c.column_name,
  c.data_type
FROM information_schema.columns c
WHERE c.table_schema = DATABASE()
  AND (
    LOWER(c.column_name) LIKE '%pract%'
    OR LOWER(c.column_name) LIKE '%nomencl%'
    OR LOWER(c.column_name) LIKE '%plan%'
    OR LOWER(c.column_name) IN ('id', 'nombre', 'descripcion', 'denominacion', 'codigo', 'prioridad', 'idcobertura', 'id_cobertura')
  )
ORDER BY c.table_name, c.ordinal_position;

-- 3) Candidatas fuertes: tablas que tengan id + (nombre/descripcion/codigo)
SELECT
  c.table_name,
  SUM(CASE WHEN LOWER(c.column_name) = 'id' THEN 1 ELSE 0 END) AS tiene_id,
  SUM(CASE WHEN LOWER(c.column_name) IN ('nombre', 'descripcion', 'denominacion', 'codigo', 'practica') THEN 1 ELSE 0 END) AS tiene_texto,
  SUM(CASE WHEN LOWER(c.column_name) IN ('prioridad') THEN 1 ELSE 0 END) AS tiene_prioridad,
  SUM(CASE WHEN LOWER(c.column_name) IN ('idcobertura', 'id_cobertura', 'idobrasocial', 'idobra_social') THEN 1 ELSE 0 END) AS tiene_id_cobertura
FROM information_schema.columns c
WHERE c.table_schema = DATABASE()
GROUP BY c.table_name
HAVING tiene_id > 0 AND tiene_texto > 0
ORDER BY c.table_name;

-- 4) Verificar tabla de órdenes real y si tiene ids para fallback
SELECT
  t.table_name
FROM information_schema.tables t
WHERE t.table_schema = DATABASE()
  AND LOWER(REPLACE(REPLACE(t.table_name, ' ', ''), '_', '')) = 'pacientesordenes';
