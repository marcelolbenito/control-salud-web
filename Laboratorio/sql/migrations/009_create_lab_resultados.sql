-- ============================================================================
-- 009_create_lab_resultados.sql
-- ============================================================================
-- Resultado actual (vivo) de cada item del pedido.
-- Solo un resultado activo por item (UNIQUE pedido_item_id).
--
-- Reglas que aplica el Service (no la BD):
--   1) Estado cargado -> validado: solo rol bioquimico puede validar.
--      (regla 1)
--   2) Una vez validado, el registro NO se puede UPDATE: rectificar implica
--      copiar el registro a lab_resultados_historico y crear una nueva
--      version con incremento de version. (regla 2)
--   3) es_critico se calcula contra valor_critico_min/max de la
--      determinacion. Si dispara, el pedido se marca como critico.
--   4) valor_referencia_min/max guardan el rango efectivamente aplicado
--      al momento de la carga (snapshot, para que cambios futuros en
--      lab_valores_referencia no alteren el historico).
--
-- TODO INTEGRACION: FKs a usuarios pendientes.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_resultados (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_item_id BIGINT UNSIGNED NOT NULL,

    valor_numerico DECIMAL(15,4) NULL,
    valor_texto VARCHAR(500) NULL COMMENT 'Para resultados cualitativos',
    unidad VARCHAR(20) NULL COMMENT 'Snapshot de la unidad',

    es_anormal TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Fuera de rango de referencia',
    es_critico TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Fuera de rango critico',
    estado ENUM('cargado','validado','rectificado') NOT NULL DEFAULT 'cargado',
    version INT UNSIGNED NOT NULL DEFAULT 1,

    valor_referencia_min DECIMAL(12,4) NULL COMMENT 'Snapshot del rango aplicado',
    valor_referencia_max DECIMAL(12,4) NULL,
    texto_referencia VARCHAR(200) NULL,

    observaciones TEXT NULL,

    usuario_carga_id BIGINT UNSIGNED NOT NULL COMMENT 'FK externa a usuarios',
    usuario_validacion_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios',
    fecha_carga DATETIME NOT NULL,
    fecha_validacion DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_resultados_pedido_item (pedido_item_id),
    KEY idx_lab_resultados_estado (estado),
    KEY idx_lab_resultados_es_critico (es_critico),
    KEY idx_lab_resultados_es_anormal (es_anormal),
    KEY idx_lab_resultados_deleted_at (deleted_at),
    CONSTRAINT fk_lab_resultados_pedido_item
        FOREIGN KEY (pedido_item_id) REFERENCES lab_pedido_items (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Resultado actual (vivo) de cada item';
