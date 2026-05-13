CREATE TABLE IF NOT EXISTS caja_cierres (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  fecha DATE NOT NULL,
  turno VARCHAR(20) NOT NULL DEFAULT 'dia',
  total_ingresos DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_egresos DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_sistema DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  efectivo_declarado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  diferencia DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  estado VARCHAR(20) NOT NULL DEFAULT 'cerrada',
  observaciones VARCHAR(500) NULL,
  id_usuario_cierre INT NULL,
  cerrado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_caja_cierre_clinica_fecha_turno (id_clinica, fecha, turno),
  KEY idx_caja_cierres_fecha (fecha),
  KEY idx_caja_cierres_usuario (id_usuario_cierre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
