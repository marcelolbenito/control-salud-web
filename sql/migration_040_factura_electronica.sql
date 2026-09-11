-- Facturación electrónica (módulo aparte) — Gesis2 / AFIP.
-- Emisión manual: paciente (cliente) + concepto + importe.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS fe_parametros (
  id_clinica INT NOT NULL PRIMARY KEY,
  gesis_url VARCHAR(200) NOT NULL DEFAULT 'https://servicios.gesis2.com',
  gesis_email VARCHAR(120) NOT NULL DEFAULT '',
  gesis_password VARCHAR(255) NOT NULL DEFAULT '',
  cuit_emisor VARCHAR(13) NULL,
  razon_social VARCHAR(200) NULL,
  domicilio_comercial VARCHAR(255) NULL,
  condicion_iva_emisor VARCHAR(40) NOT NULL DEFAULT 'monotributo',
  punto_venta SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  cbte_tipo SMALLINT UNSIGNED NOT NULL DEFAULT 11 COMMENT '11=Factura C, 6=B, 1=A',
  concepto TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '2=servicios',
  production TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=homologación 1=producción',
  actualizado_en DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO fe_parametros (id_clinica) VALUES (1);

CREATE TABLE IF NOT EXISTS fe_comprobantes (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  id_paciente INT NULL,
  id_usuario INT NULL,
  paciente_nombre VARCHAR(200) NOT NULL,
  paciente_doc VARCHAR(30) NULL,
  concepto_texto VARCHAR(500) NOT NULL,
  letra CHAR(1) NOT NULL DEFAULT 'C',
  punto_venta SMALLINT UNSIGNED NOT NULL,
  numero INT UNSIGNED NOT NULL,
  cbte_tipo SMALLINT UNSIGNED NOT NULL DEFAULT 11,
  fecha_emision DATETIME NOT NULL,
  importe_total DECIMAL(14,2) NOT NULL,
  cae VARCHAR(20) NULL,
  cae_vencimiento DATE NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'autorizado',
  request_json MEDIUMTEXT NULL,
  response_json MEDIUMTEXT NULL,
  production TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_fe_pv_num_tipo (id_clinica, punto_venta, numero, cbte_tipo),
  KEY idx_fe_clin_fecha (id_clinica, fecha_emision),
  KEY idx_fe_paciente (id_paciente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
