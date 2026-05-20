-- ============================================================================
-- 008_create_lab_muestras.sql
-- ============================================================================
-- Tubos / muestras fisicas asociadas a un pedido.
-- Decision Fase 1: una muestra cubre todo el pedido (no se vincula a items
-- individuales). En la practica un tubo de suero sirve para varias
-- determinaciones del mismo pedido. Si en Fase 2 se necesita el vinculo
-- item <-> muestra, se agrega un pivote.
--
-- TODO INTEGRACION: FKs a usuarios pendientes.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_muestras (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    codigo_barras VARCHAR(50) NOT NULL,
    tipo_muestra ENUM('suero','plasma','sangre_entera','orina','heces','otro') NOT NULL,
    estado ENUM('pendiente_extraccion','recibida','en_procesamiento','procesada','derivada','rechazada','anulada') NOT NULL DEFAULT 'pendiente_extraccion',
    motivo_rechazo VARCHAR(500) NULL COMMENT 'Hemolizada, insuficiente, etc.',
    fecha_extraccion DATETIME NULL,
    fecha_recepcion DATETIME NULL,
    usuario_extraccion_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios',
    usuario_recepcion_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios',
    observaciones VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_muestras_codigo_barras (codigo_barras),
    KEY idx_lab_muestras_pedido (pedido_id),
    KEY idx_lab_muestras_estado (estado),
    KEY idx_lab_muestras_deleted_at (deleted_at),
    CONSTRAINT fk_lab_muestras_pedido
        FOREIGN KEY (pedido_id) REFERENCES lab_pedidos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Muestras fisicas (tubos) recibidas';
