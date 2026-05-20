-- ============================================================================
-- 012_create_lab_auditoria.sql
-- ============================================================================
-- Log de cambios. APPEND-ONLY, inmutable.
-- Cumple regla 6: toda creacion, modificacion o
-- cambio de estado se registra aqui con usuario, accion, tabla, registro_id,
-- valores antes/despues, IP y timestamp.
--
-- valor_anterior y valor_nuevo se guardan como JSON. Para acciones que no
-- modifican datos (login, logout) ambos pueden ser NULL.
--
-- TODO INTEGRACION: FK a usuarios pendiente.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_auditoria (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios. NULL = accion del sistema/cron',
    accion ENUM('crear','actualizar','eliminar','validar','rectificar','anular','login','logout','cambiar_estado') NOT NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    registro_id BIGINT UNSIGNED NULL,
    valor_anterior JSON NULL,
    valor_nuevo JSON NULL,
    ip VARCHAR(45) NULL COMMENT 'Soporta IPv6',
    user_agent VARCHAR(500) NULL,
    contexto VARCHAR(500) NULL COMMENT 'Descripcion opcional de la accion',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_lab_auditoria_tabla_registro (tabla_afectada, registro_id),
    KEY idx_lab_auditoria_usuario (usuario_id),
    KEY idx_lab_auditoria_created_at (created_at),
    KEY idx_lab_auditoria_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log de auditoria (append-only)';
