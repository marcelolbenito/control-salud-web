-- ============================================================================
-- 002_create_lab_determinaciones.sql
-- ============================================================================
-- Catalogo maestro de analisis (Glucemia, TSH, Hemoglobina, etc.).
-- Cada determinacion pertenece a un area y define unidad, metodo y rangos
-- criticos. Los rangos normales (por sexo/edad) se definen en
-- lab_valores_referencia.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_determinaciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    area_id BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(20) NOT NULL COMMENT 'Identificacion corta (ej: GLU, TSH)',
    nombre VARCHAR(150) NOT NULL,
    nombre_corto VARCHAR(60) NULL COMMENT 'Version abreviada para PDF',
    unidad VARCHAR(20) NOT NULL COMMENT 'Ej: mg/dL, mU/L',
    metodo VARCHAR(100) NULL COMMENT 'Ej: Enzimatico, ELISA',
    tipo_resultado ENUM('numerico','texto','seleccion') NOT NULL DEFAULT 'numerico',
    decimales TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT 'Cantidad de decimales a mostrar',
    tiempo_demora_horas INT UNSIGNED NULL COMMENT 'TAT estimado en horas',
    precio DECIMAL(10,2) NULL COMMENT 'Precio referencial',
    valor_critico_min DECIMAL(12,4) NULL,
    valor_critico_max DECIMAL(12,4) NULL,
    observaciones TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_determinaciones_codigo (codigo),
    KEY idx_lab_determinaciones_area_id (area_id),
    KEY idx_lab_determinaciones_activo (activo),
    KEY idx_lab_determinaciones_deleted_at (deleted_at),
    CONSTRAINT fk_lab_determinaciones_area
        FOREIGN KEY (area_id) REFERENCES lab_areas (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catalogo maestro de analisis del laboratorio';
