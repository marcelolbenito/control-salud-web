-- ============================================================================
-- 014_add_billing_to_lab_pedidos.sql
-- ============================================================================
-- Agrega campos de facturacion al pedido (sub-proyecto 5: pagos/caja).
--
-- Cada pedido tiene dos estados de facturacion independientes:
--   - estado_paciente: estado de cobro al paciente (lo que paga de su bolsillo).
--   - estado_seguro:   estado de cobro a la obra social.
--
-- Estados:
--   A = A facturar  (pedido recibido, falta emitir factura)
--   F = Facturada   (factura emitida, pendiente de cobro)
--   P = Pagada      (cobrada)
--   N = No aplica   (ej: paciente sin obra social -> estado_seguro = N)
--
-- Montos en DECIMAL(nunca FLOAT en valores clinicos/dinero).
-- ============================================================================

ALTER TABLE lab_pedidos
    ADD COLUMN estado_paciente CHAR(1) NOT NULL DEFAULT 'A'
        COMMENT 'A=A facturar, F=Facturada, P=Pagada, N=No aplica',
    ADD COLUMN estado_seguro   CHAR(1) NOT NULL DEFAULT 'N'
        COMMENT 'A=A facturar, F=Facturada, P=Pagada, N=No aplica',
    ADD COLUMN monto_paciente   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN monto_seguro     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN monto_honorarios DECIMAL(12,2) NOT NULL DEFAULT 0.00
        COMMENT 'Honorarios del bioquimico responsable',
    ADD COLUMN bioquimico_id    BIGINT UNSIGNED NULL
        COMMENT 'FK a lab_bioquimicos: bioquimico responsable de cobro',
    ADD KEY idx_lab_pedidos_estado_paciente (estado_paciente),
    ADD KEY idx_lab_pedidos_estado_seguro (estado_seguro),
    ADD KEY idx_lab_pedidos_bioquimico (bioquimico_id),
    ADD CONSTRAINT fk_lab_pedidos_bioquimico
        FOREIGN KEY (bioquimico_id) REFERENCES lab_bioquimicos(id)
        ON UPDATE CASCADE ON DELETE SET NULL;
