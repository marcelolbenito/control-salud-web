-- ============================================================================
-- 018_create_lab_lotes_os.sql
-- ============================================================================
-- Sub-proyecto 8: Facturacion por obra social.
--
-- Lote = agrupacion persistente de N pedidos de la misma OS que se cobran
-- en un solo acto. Estados: abierto -> (cobrado | anulado) (terminales).
--
-- Numero L-YYYY-NNNNN se genera con MAX(numero)+1 por anio dentro de
-- transaccion (mismo patron que lab_pedidos.numero).
--
-- Sin FK a obras_sociales: tabla del sistema principal.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_lotes_os (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero VARCHAR(20) NOT NULL COMMENT 'L-YYYY-NNNNN',
    obra_social_id BIGINT UNSIGNED NOT NULL COMMENT 'FK externa a obras_sociales',
    fecha_desde DATE NOT NULL,
    fecha_hasta DATE NOT NULL,
    estado ENUM('abierto','cobrado','anulado') NOT NULL DEFAULT 'abierto',
    monto_total DECIMAL(14,2) NOT NULL DEFAULT 0.00
        COMMENT 'Suma de monto_seguro_snapshot de los pedidos del lote',
    cantidad_pedidos INT UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Denormalizado para listados',
    fecha_generacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cobro DATETIME NULL,
    fecha_anulacion DATETIME NULL,
    motivo_anulacion VARCHAR(500) NULL,
    usuario_generacion_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios',
    usuario_cobro_id BIGINT UNSIGNED NULL,
    usuario_anulacion_id BIGINT UNSIGNED NULL,
    observaciones TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_lotes_os_numero (numero),
    KEY idx_lab_lotes_os_obra_social (obra_social_id),
    KEY idx_lab_lotes_os_estado (estado),
    KEY idx_lab_lotes_os_fechas (fecha_desde, fecha_hasta),
    KEY idx_lab_lotes_os_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Lotes de facturacion a obra social (SP8)';

CREATE TABLE IF NOT EXISTS lab_lote_pedidos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    lote_id BIGINT UNSIGNED NOT NULL,
    pedido_id BIGINT UNSIGNED NOT NULL,
    monto_seguro_snapshot DECIMAL(12,2) NOT NULL
        COMMENT 'monto_seguro del pedido al sumarlo al lote (inmutable)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_lote_pedidos (lote_id, pedido_id),
    KEY idx_lab_lote_pedidos_pedido (pedido_id),
    CONSTRAINT fk_lab_lote_pedidos_lote
        FOREIGN KEY (lote_id) REFERENCES lab_lotes_os (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lab_lote_pedidos_pedido
        FOREIGN KEY (pedido_id) REFERENCES lab_pedidos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Pedidos incluidos en cada lote (pivot, SP8)';
