-- ============================================================================
-- 001_create_lab_areas.sql
-- ============================================================================
-- Areas del laboratorio (hematologia, quimica clinica, hormonas, etc.)
-- Catalogo base usado para agrupar determinaciones en informes y reportes.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_areas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(20) NOT NULL COMMENT 'Identificacion corta (ej: HEM, QC, HOR)',
    descripcion TEXT NULL,
    orden INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Orden de aparicion en informes',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_areas_nombre (nombre),
    UNIQUE KEY uk_lab_areas_codigo (codigo),
    KEY idx_lab_areas_activo (activo),
    KEY idx_lab_areas_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Areas del laboratorio (hematologia, quimica, hormonas, etc.)';
