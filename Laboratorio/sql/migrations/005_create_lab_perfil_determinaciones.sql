-- ============================================================================
-- 005_create_lab_perfil_determinaciones.sql
-- ============================================================================
-- Pivote perfiles <-> determinaciones.
-- Si un perfil se modifica (se quita o agrega una determinacion), los pedidos
-- ya emitidos con ese perfil NO se ven afectados (cada pedido_item preserva
-- su perfil_id de origen como referencia historica).
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_perfil_determinaciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    perfil_id BIGINT UNSIGNED NOT NULL,
    determinacion_id BIGINT UNSIGNED NOT NULL,
    orden INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_perfil_det (perfil_id, determinacion_id),
    KEY idx_lab_perfil_det_determinacion (determinacion_id),
    CONSTRAINT fk_lab_perfil_det_perfil
        FOREIGN KEY (perfil_id) REFERENCES lab_perfiles (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lab_perfil_det_determinacion
        FOREIGN KEY (determinacion_id) REFERENCES lab_determinaciones (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Pivote perfil <-> determinacion';
