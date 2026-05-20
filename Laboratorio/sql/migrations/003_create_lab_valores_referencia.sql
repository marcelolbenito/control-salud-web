-- ============================================================================
-- 003_create_lab_valores_referencia.sql
-- ============================================================================
-- Rangos normales por sexo y edad (en dias) para cada determinacion.
-- Una determinacion puede tener N rangos (recien nacido, nino, adulto, etc.).
-- La aplicacion selecciona el rango aplicable al momento de la extraccion
-- segun sexo + edad del paciente y lo "snapshot-ea" en lab_resultados.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_valores_referencia (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    determinacion_id BIGINT UNSIGNED NOT NULL,
    sexo ENUM('M','F','ambos') NOT NULL DEFAULT 'ambos',
    edad_min_dias INT UNSIGNED NULL COMMENT 'NULL = sin limite inferior',
    edad_max_dias INT UNSIGNED NULL COMMENT 'NULL = sin limite superior',
    valor_min DECIMAL(12,4) NULL,
    valor_max DECIMAL(12,4) NULL,
    texto_referencia VARCHAR(200) NULL COMMENT 'Para resultados textuales (ej: Negativo)',
    observaciones TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_lab_valores_ref_lookup (determinacion_id, sexo, edad_min_dias, edad_max_dias),
    KEY idx_lab_valores_ref_deleted_at (deleted_at),
    CONSTRAINT fk_lab_valores_ref_determinacion
        FOREIGN KEY (determinacion_id) REFERENCES lab_determinaciones (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Rangos normales por sexo y edad (en dias)';
