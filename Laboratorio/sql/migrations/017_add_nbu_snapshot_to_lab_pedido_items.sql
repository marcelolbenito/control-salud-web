-- ============================================================================
-- 017_add_nbu_snapshot_to_lab_pedido_items.sql
-- ============================================================================
-- Sub-proyecto 7: snapshot del arancel al crear el pedido.
--
-- Las dos columnas *_snapshot quedan NULL para items de pedido particular
-- (no aplica NBU). monto_item siempre tiene valor (puede ser 0 si no hay
-- arancel configurado).
--
-- Pedidos previos a esta migracion mantienen *_snapshot = NULL y
-- monto_item = 0.00. No hay backfill (decision spec).
-- ============================================================================

ALTER TABLE lab_pedido_items
    ADD COLUMN nbu_unidades_snapshot   DECIMAL(8,2)  NULL
        COMMENT 'Snapshot de unidades NBU del analisis al momento del pedido. NULL si particular.'
        AFTER precio,
    ADD COLUMN nbu_valor_unit_snapshot DECIMAL(10,2) NULL
        COMMENT 'Snapshot de $ por unidad NBU de la OS al momento del pedido. NULL si particular.'
        AFTER nbu_unidades_snapshot,
    ADD COLUMN monto_item              DECIMAL(12,2) NOT NULL DEFAULT 0.00
        COMMENT 'NBU * valor (OS) o lab_determinaciones.precio (particular). Snapshot al crear.'
        AFTER nbu_valor_unit_snapshot;
