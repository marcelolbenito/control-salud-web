-- Código de nomenclador en lista_practicas — versión MANUAL
-- Usar si migration_037_practicas_codigo_nomenclador.sql falla en phpMyAdmin
-- (PREPARE/EXECUTE o AFTER prioridad).
--
-- Ejecutar en la base de producción, paso por paso.
-- Antes: SHOW COLUMNS FROM lista_practicas;

-- ============================================================================
-- Paso 1: agregar columna codigo (si YA existe, salta al Paso 2)
-- ============================================================================
-- Preferida (si existe columna prioridad):
ALTER TABLE `lista_practicas`
  ADD COLUMN `codigo` VARCHAR(15) NULL AFTER `prioridad`;

-- Si el Paso 1 falla con "Unknown column 'prioridad'", usar esta en su lugar:
-- ALTER TABLE `lista_practicas`
--   ADD COLUMN `codigo` VARCHAR(15) NULL;

-- Si dice "Duplicate column name 'codigo'", la columna ya está: seguí al Paso 2.

-- ============================================================================
-- Paso 2: copiar códigos desde el nomenclador legacy (si existe esa tabla)
-- ============================================================================
-- Verificar primero:
-- SHOW TABLES LIKE '%omenclador%';
-- SHOW TABLES LIKE '%Nomenclador%';

-- Si la tabla se llama exactamente así (con espacios y mayúsculas):
UPDATE `lista_practicas` p
INNER JOIN `Lista Nomenclador` n ON n.id = p.id
SET p.codigo = NULLIF(TRIM(n.codigo), '')
WHERE p.codigo IS NULL OR p.codigo = '';

-- Si el nombre en el servidor es otro (ej. lista_nomenclador), adaptá:
-- UPDATE `lista_practicas` p
-- INNER JOIN `lista_nomenclador` n ON n.id = p.id
-- SET p.codigo = NULLIF(TRIM(n.codigo), '')
-- WHERE p.codigo IS NULL OR p.codigo = '';

-- Si NO hay tabla de nomenclador, podés cargar códigos a mano o dejar NULL;
-- la web igual busca por nombre; el código ayuda a mostrar/filtrar.

-- ============================================================================
-- Paso 3: índice (si ya existe, ignore el error Duplicate key name)
-- ============================================================================
CREATE INDEX `idx_lista_practicas_codigo` ON `lista_practicas` (`codigo`);

-- ============================================================================
-- Verificar
-- ============================================================================
-- SHOW COLUMNS FROM lista_practicas LIKE 'codigo';
-- SELECT COUNT(*) AS con_codigo FROM lista_practicas WHERE codigo IS NOT NULL AND codigo <> '';
-- SELECT id, codigo, nombre FROM lista_practicas WHERE codigo IS NOT NULL ORDER BY id LIMIT 20;
