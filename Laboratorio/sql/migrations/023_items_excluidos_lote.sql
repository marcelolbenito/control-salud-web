-- Migration 023: exclusion de items por lote (items que no cubre la OS)
--
-- Caso de uso: al armar el lote de facturacion a OS, el operador marca
-- analisis especificos que la OS no cubre. Esos items:
--   - Se descuentan del monto_seguro del pedido (y del snapshot del lote).
--   - Se suman al monto_paciente (lo paga el paciente).
-- La exclusion vive en el contexto del lote; si despues el pedido entra
-- a otro lote, no arrastra estas marcas.

CREATE TABLE IF NOT EXISTS lab_lote_pedido_item_excluido (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lote_id BIGINT UNSIGNED NOT NULL,
    pedido_item_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NULL,
    motivo VARCHAR(200) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lote_item (lote_id, pedido_item_id),
    KEY idx_pedido_item (pedido_item_id),
    CONSTRAINT fk_excl_lote FOREIGN KEY (lote_id) REFERENCES lab_lotes_os (id),
    CONSTRAINT fk_excl_item FOREIGN KEY (pedido_item_id) REFERENCES lab_pedido_items (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Items del pedido excluidos de un lote OS (no cubiertos). Pasan a monto_paciente.';
