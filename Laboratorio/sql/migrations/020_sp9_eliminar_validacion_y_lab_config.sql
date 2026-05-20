-- ============================================================================
-- 020_sp9_eliminar_validacion_y_lab_config.sql
-- ============================================================================
-- SP9: elimina el paso de validacion en resultados y agrega configuracion
-- institucional del laboratorio.
--
-- Cambios:
--   1) lab_resultados: drop usuario_validacion_id, fecha_validacion, version.
--      Reduce ENUM de estado a ('cargado','rectificado'). Antes del DROP,
--      convierte estado='validado' a 'cargado' (resultados validados quedan
--      como cargados firmes).
--   2) lab_bioquimicos: agrega es_responsable_principal (TINYINT(1)).
--      Solo uno con =1; unicidad validada en BioquimicoService.
--   3) lab_config: tabla clave/valor para datos institucionales.
--
-- NOTA: lab_resultados_historico queda intacta como datos legacy. El service
-- ya no escribe en ella.
-- ============================================================================

-- 1) lab_resultados: convertir validados a cargados antes de drops
UPDATE lab_resultados
   SET estado = 'cargado'
 WHERE estado = 'validado';

ALTER TABLE lab_resultados
    DROP COLUMN usuario_validacion_id,
    DROP COLUMN fecha_validacion,
    DROP COLUMN version,
    MODIFY estado ENUM('cargado','rectificado') NOT NULL DEFAULT 'cargado';

-- 2) lab_bioquimicos: marca de principal
ALTER TABLE lab_bioquimicos
    ADD COLUMN es_responsable_principal TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Bioquimico que firma todos los informes. Solo uno con =1',
    ADD KEY idx_lab_bioquimicos_principal (es_responsable_principal);

-- 3) lab_config: configuracion institucional del laboratorio
CREATE TABLE IF NOT EXISTS lab_config (
    clave VARCHAR(80) NOT NULL,
    valor TEXT NULL,
    descripcion VARCHAR(255) NULL COMMENT 'Para que un admin entienda que guarda la clave',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuracion institucional del laboratorio (clave/valor)';
