-- ============================================================================
-- 007_create_lab_pedido_items.sql
-- ============================================================================
-- Items individuales del pedido. Cada determinacion solicitada genera un item.
-- Si el item vino por un perfil, perfil_id se preserva como referencia
-- historica (aunque el perfil cambie despues, este registro no se altera).
-- El UNIQUE (pedido_id, determinacion_id) impide pedir la misma determinacion
-- dos veces en el mismo pedido.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_pedido_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    determinacion_id BIGINT UNSIGNED NOT NULL,
    perfil_id BIGINT UNSIGNED NULL COMMENT 'Perfil que origino el item',
    estado ENUM('pendiente','en_proceso','cargado','validado','rectificado','anulado') NOT NULL DEFAULT 'pendiente',
    precio DECIMAL(10,2) NULL COMMENT 'Snapshot del precio al momento del pedido',
    observaciones VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_pedido_items (pedido_id, determinacion_id),
    KEY idx_lab_pedido_items_estado (estado),
    KEY idx_lab_pedido_items_determinacion (determinacion_id),
    KEY idx_lab_pedido_items_perfil (perfil_id),
    KEY idx_lab_pedido_items_pedido_estado (pedido_id, estado),
    KEY idx_lab_pedido_items_deleted_at (deleted_at),
    CONSTRAINT fk_lab_pedido_items_pedido
        FOREIGN KEY (pedido_id) REFERENCES lab_pedidos (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lab_pedido_items_determinacion
        FOREIGN KEY (determinacion_id) REFERENCES lab_determinaciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lab_pedido_items_perfil
        FOREIGN KEY (perfil_id) REFERENCES lab_perfiles (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Items (determinaciones) de cada pedido';
