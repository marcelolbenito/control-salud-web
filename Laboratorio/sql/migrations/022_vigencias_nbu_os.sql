-- Migration 022: vigencias en lab_nbu_valores_os + drop snapshots de pedido_items
--
-- Cambios:
--   1) lab_nbu_valores_os: agregar fecha_desde y fecha_hasta. Eliminar UNIQUE por OS.
--      Los registros existentes quedan con fecha_desde='2000-01-01' y fecha_hasta=NULL (vigentes).
--   2) lab_pedido_items: eliminar columnas snapshot (nbu_unidades_snapshot,
--      nbu_valor_unit_snapshot, monto_item). Ya no se guarda monto por item;
--      se recalcula al armar el lote con la vigencia del dia del lote.

START TRANSACTION;

-- =========================================================================
-- 1) Vigencias en lab_nbu_valores_os
-- =========================================================================

ALTER TABLE lab_nbu_valores_os
    ADD COLUMN fecha_desde DATE NOT NULL DEFAULT '2000-01-01' AFTER valor_unitario,
    ADD COLUMN fecha_hasta DATE NULL AFTER fecha_desde;

ALTER TABLE lab_nbu_valores_os
    DROP INDEX uq_nbu_valores_os_obra_social;

ALTER TABLE lab_nbu_valores_os
    ADD KEY idx_nbu_vigencia (obra_social_id, fecha_desde, fecha_hasta);

ALTER TABLE lab_nbu_valores_os
    MODIFY COLUMN fecha_desde DATE NOT NULL;

-- =========================================================================
-- 2) Drop snapshots de lab_pedido_items
-- =========================================================================

SET @hascol := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lab_pedido_items' AND COLUMN_NAME = 'monto_item'
);
SET @sql := IF(@hascol = 0, 'SELECT 1', 'ALTER TABLE lab_pedido_items DROP COLUMN monto_item');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @hascol := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lab_pedido_items' AND COLUMN_NAME = 'nbu_unidades_snapshot'
);
SET @sql := IF(@hascol = 0, 'SELECT 1', 'ALTER TABLE lab_pedido_items DROP COLUMN nbu_unidades_snapshot');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @hascol := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lab_pedido_items' AND COLUMN_NAME = 'nbu_valor_unit_snapshot'
);
SET @sql := IF(@hascol = 0, 'SELECT 1', 'ALTER TABLE lab_pedido_items DROP COLUMN nbu_valor_unit_snapshot');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
