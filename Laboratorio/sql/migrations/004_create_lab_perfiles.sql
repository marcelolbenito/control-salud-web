-- ============================================================================
-- 004_create_lab_perfiles.sql
-- ============================================================================
-- Agrupaciones predefinidas de determinaciones (Perfil tiroideo,
-- Hepatograma, Prequirurgico, Hemograma completo, etc.).
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_perfiles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_perfiles_codigo (codigo),
    KEY idx_lab_perfiles_activo (activo),
    KEY idx_lab_perfiles_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agrupaciones predefinidas de determinaciones';
