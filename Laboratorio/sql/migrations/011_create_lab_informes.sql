-- ============================================================================
-- 011_create_lab_informes.sql
-- ============================================================================
-- PDF firmado y emitido de un pedido. Se guarda hash SHA-256 para detectar
-- manipulacion (cualquier cambio en el archivo invalida el hash).
-- es_parcial = 1 cuando el informe cubre solo algunos items del pedido
-- (ej: se entrega un parcial con resultados ya validados mientras otros
-- siguen pendientes).
--
-- TODO INTEGRACION: FKs a usuarios pendientes.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_informes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    numero VARCHAR(20) NOT NULL COMMENT 'Numero visible: I-YYYY-NNNNN',
    ruta_pdf VARCHAR(500) NOT NULL COMMENT 'Path relativo a storage/informes/',
    hash_pdf VARCHAR(64) NOT NULL COMMENT 'SHA-256 del PDF',
    es_parcial TINYINT(1) NOT NULL DEFAULT 0,
    usuario_emision_id BIGINT UNSIGNED NOT NULL COMMENT 'FK externa a usuarios',
    fecha_emision DATETIME NOT NULL,
    entregado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_entrega DATETIME NULL,
    usuario_entrega_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios',
    destinatario VARCHAR(150) NULL COMMENT 'A quien se entrego (paciente, familiar, etc.)',
    observaciones TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_informes_numero (numero),
    KEY idx_lab_informes_pedido (pedido_id),
    KEY idx_lab_informes_entregado (entregado),
    KEY idx_lab_informes_fecha_emision (fecha_emision),
    KEY idx_lab_informes_deleted_at (deleted_at),
    CONSTRAINT fk_lab_informes_pedido
        FOREIGN KEY (pedido_id) REFERENCES lab_pedidos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Informes PDF emitidos';
