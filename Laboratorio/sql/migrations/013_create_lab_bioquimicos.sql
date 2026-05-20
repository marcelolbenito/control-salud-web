-- ============================================================================
-- 013_create_lab_bioquimicos.sql
-- ============================================================================
-- Catalogo de bioquimicos del laboratorio. Es nuestro (no del sistema
-- principal): contiene info especifica del lab (matricula, firma, cargo).
--
-- Si el sistema principal tiene una tabla de usuarios y un bioquimico
-- ya esta cargado alli, se puede vincular via usuario_id (FK externa,
-- declarada como BIGINT UNSIGNED NULL sin FK formal porque es tabla de
-- otro dev — mismo patron que medico_id en lab_pedidos).
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_bioquimicos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    apellido VARCHAR(100) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    matricula VARCHAR(40) NOT NULL,
    titulo VARCHAR(100) NULL COMMENT 'Ej: Bioquimico, Doctor en Bioquimica',
    especialidad VARCHAR(100) NULL,
    telefono VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    firma_path VARCHAR(255) NULL COMMENT 'Ruta del PNG de firma escaneada (informes)',
    usuario_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios del sistema principal',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_bioquimicos_matricula (matricula),
    KEY idx_lab_bioquimicos_apellido (apellido),
    KEY idx_lab_bioquimicos_activo (activo),
    KEY idx_lab_bioquimicos_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Bioquimicos del laboratorio (firma de informes, validacion).';
