-- ============================================================================
-- 016_create_lab_nbu.sql
-- ============================================================================
-- Sub-proyecto 7: NBU y aranceles por obra social.
--
-- Dos catalogos:
--   lab_nbu_determinaciones: cantidad de unidades NBU por analisis (uno-a-uno).
--   lab_nbu_valores_os:      $ por unidad NBU para cada obra social.
--
-- Monto a cobrar a la OS por un analisis = unidades * valor_unitario.
-- Si el paciente no tiene OS (particular), se usa lab_determinaciones.precio.
--
-- Sin FK a obras_sociales: tabla del sistema principal.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_nbu_determinaciones (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    determinacion_id BIGINT UNSIGNED NOT NULL,
    unidades         DECIMAL(8,2) NOT NULL DEFAULT 0.00
                     COMMENT 'Cantidad de unidades NBU del analisis',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nbu_det_determinacion (determinacion_id),
    CONSTRAINT fk_lab_nbu_det_det FOREIGN KEY (determinacion_id)
        REFERENCES lab_determinaciones (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Unidades NBU por analisis (catalogo)';

CREATE TABLE IF NOT EXISTS lab_nbu_valores_os (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    obra_social_id BIGINT UNSIGNED NOT NULL,
    valor_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00
                   COMMENT '$ por unidad NBU',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nbu_valores_os_obra_social (obra_social_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Valor por unidad NBU por obra social. Sin FK a obras_sociales (otro dev).';
