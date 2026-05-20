-- ============================================================================
-- 010_create_lab_resultados_historico.sql
-- ============================================================================
-- Versiones anteriores de los resultados al rectificar. APPEND-ONLY e
-- inmutable: nunca UPDATE ni DELETE (la app lo respeta, la BD no lo enforce
-- mas alla de la falta de updated_at).
--
-- Cada vez que se rectifica un resultado validado (regla 2 de 
-- seccion 6) el Service:
--   1) Inserta el resultado actual en esta tabla (snapshot completo).
--   2) Incrementa version y actualiza el resultado en lab_resultados con
--      el nuevo valor.
--   3) Registra en lab_auditoria.
--
-- TODO INTEGRACION: FKs a usuarios pendientes.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_resultados_historico (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    resultado_id BIGINT UNSIGNED NOT NULL COMMENT 'Apunta al resultado actual',
    pedido_item_id BIGINT UNSIGNED NOT NULL,

    -- Snapshot del resultado anterior
    valor_numerico DECIMAL(15,4) NULL,
    valor_texto VARCHAR(500) NULL,
    unidad VARCHAR(20) NULL,
    es_anormal TINYINT(1) NOT NULL,
    es_critico TINYINT(1) NOT NULL,
    version INT UNSIGNED NOT NULL,
    valor_referencia_min DECIMAL(12,4) NULL,
    valor_referencia_max DECIMAL(12,4) NULL,
    texto_referencia VARCHAR(200) NULL,
    observaciones TEXT NULL,
    usuario_carga_id BIGINT UNSIGNED NOT NULL COMMENT 'FK externa a usuarios',
    usuario_validacion_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios',
    fecha_carga DATETIME NOT NULL,
    fecha_validacion DATETIME NULL,

    -- Datos de la rectificacion
    motivo_rectificacion VARCHAR(500) NOT NULL,
    usuario_rectificacion_id BIGINT UNSIGNED NOT NULL COMMENT 'FK externa a usuarios',
    fecha_rectificacion DATETIME NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_lab_resultados_hist_resultado (resultado_id),
    KEY idx_lab_resultados_hist_pedido_item (pedido_item_id),
    KEY idx_lab_resultados_hist_version (resultado_id, version),
    CONSTRAINT fk_lab_resultados_hist_resultado
        FOREIGN KEY (resultado_id) REFERENCES lab_resultados (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_lab_resultados_hist_pedido_item
        FOREIGN KEY (pedido_item_id) REFERENCES lab_pedido_items (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Versiones anteriores de resultados (append-only)';
