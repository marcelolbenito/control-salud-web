-- ============================================================================
-- 006_create_lab_pedidos.sql
-- ============================================================================
-- Orden medica. Punto de entrada de un analisis de laboratorio.
--
-- TODO INTEGRACION: las FKs a tablas externas (pacientes, medicos,
-- obras_sociales, usuarios) NO se declaran aqui porque esas tablas las
-- mantiene el sistema principal (otro dev). Cuando se acuerden tipos en
-- INTEGRACION.md, se agregan via ALTER TABLE en una migration posterior.
--
-- snapshot_paciente: copia de los datos relevantes (sexo, fecha_nac,
-- nombre completo, dni) al momento del pedido. Necesario para que los
-- rangos de referencia se apliquen siempre con la edad/sexo correctos
-- aunque el paciente luego se modifique en el sistema principal.
-- ============================================================================

CREATE TABLE IF NOT EXISTS lab_pedidos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero VARCHAR(20) NOT NULL COMMENT 'Numero visible: P-YYYY-NNNNN',

    paciente_id BIGINT UNSIGNED NOT NULL COMMENT 'FK externa a pacientes',
    medico_id BIGINT UNSIGNED NULL COMMENT 'FK externa a medicos',
    medico_externo VARCHAR(150) NULL COMMENT 'Nombre del medico si no esta en sistema',
    obra_social_id BIGINT UNSIGNED NULL COMMENT 'FK externa a obras_sociales',
    numero_afiliado VARCHAR(50) NULL,

    diagnostico VARCHAR(500) NULL,
    prioridad ENUM('rutina','urgente','guardia') NOT NULL DEFAULT 'rutina',
    estado ENUM('pendiente','en_proceso','parcial','completo','entregado','anulado') NOT NULL DEFAULT 'pendiente',
    es_critico TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Denormalizado para listados rapidos',

    fecha_solicitud DATETIME NOT NULL,
    fecha_extraccion DATETIME NULL,
    fecha_entrega DATETIME NULL,

    usuario_recepcion_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios',
    usuario_anulacion_id BIGINT UNSIGNED NULL COMMENT 'FK externa a usuarios',
    motivo_anulacion VARCHAR(500) NULL,

    observaciones TEXT NULL,
    snapshot_paciente JSON NULL COMMENT 'Snapshot inmutable de datos del paciente al momento del pedido',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uk_lab_pedidos_numero (numero),
    KEY idx_lab_pedidos_paciente (paciente_id),
    KEY idx_lab_pedidos_medico (medico_id),
    KEY idx_lab_pedidos_estado (estado),
    KEY idx_lab_pedidos_fecha_solicitud (fecha_solicitud),
    KEY idx_lab_pedidos_es_critico (es_critico),
    KEY idx_lab_pedidos_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Pedidos / ordenes medicas de laboratorio';
