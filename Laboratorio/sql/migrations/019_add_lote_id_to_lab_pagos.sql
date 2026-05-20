-- ============================================================================
-- 019_add_lote_id_to_lab_pagos.sql
-- ============================================================================
-- Sub-proyecto 8: vincula pagos a su lote de facturacion (cuando aplica).
--
-- lote_id es NULL para pagos sueltos (los que ya existen y los que se siguen
-- creando desde la pantalla de pagos individual). Solo se popula cuando el
-- pago se genera al cobrar un lote.
-- ============================================================================

ALTER TABLE lab_pagos
    ADD COLUMN lote_id BIGINT UNSIGNED NULL
        COMMENT 'Lote de OS al que pertenece este pago (NULL = pago suelto, no por lote)'
        AFTER pedido_id,
    ADD KEY idx_lab_pagos_lote (lote_id),
    ADD CONSTRAINT fk_lab_pagos_lote
        FOREIGN KEY (lote_id) REFERENCES lab_lotes_os (id)
        ON UPDATE CASCADE ON DELETE RESTRICT;
