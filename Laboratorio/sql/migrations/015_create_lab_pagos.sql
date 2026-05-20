-- ============================================================================
-- 015_create_lab_pagos.sql
-- ============================================================================
-- Registro de pagos recibidos (sub-proyecto 5: pagos/caja).
--
-- Un pago se asocia a un pedido (no es libre). Puede ser:
--   - Pago del PACIENTE (paga lo que no cubre el seguro)
--   - Pago del SEGURO   (la obra social paga su parte)
--
-- Al crear un pago, el lab_pedido debe actualizar su estado_paciente o
-- estado_seguro a 'P' (logica en PagoService).
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_pagos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    bioquimico_id BIGINT UNSIGNED NULL COMMENT 'Bioquimico que recibio el pago',
    quien_pago ENUM('paciente','seguro') NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    medio_pago ENUM('efectivo','tarjeta','transferencia','cheque','otro') NOT NULL DEFAULT 'efectivo',
    referencia VARCHAR(100) NULL COMMENT 'Nro de comprobante / autorizacion / cheque',
    observaciones TEXT NULL,

    usuario_carga_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios (quien registra)',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    KEY idx_lab_pagos_pedido (pedido_id),
    KEY idx_lab_pagos_bioquimico (bioquimico_id),
    KEY idx_lab_pagos_fecha (fecha_pago),
    KEY idx_lab_pagos_quien (quien_pago),
    KEY idx_lab_pagos_deleted (deleted_at),
    CONSTRAINT fk_lab_pagos_pedido
        FOREIGN KEY (pedido_id) REFERENCES lab_pedidos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lab_pagos_bioquimico
        FOREIGN KEY (bioquimico_id) REFERENCES lab_bioquimicos(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Pagos recibidos del paciente o del seguro por una orden de lab';
