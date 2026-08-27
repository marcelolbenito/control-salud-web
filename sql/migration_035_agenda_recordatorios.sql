-- Recordatorios de turnos (reemplazo web de Recordatorios.exe)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS agenda_recordatorios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  id_turno INT NOT NULL,
  id_doctor INT NULL,
  nro_hc INT NOT NULL,
  telefono_e164 VARCHAR(25) NULL,
  tipo VARCHAR(24) NOT NULL DEFAULT 'recordatorio' COMMENT 'confirmacion|recordatorio|anulacion',
  canal VARCHAR(20) NOT NULL DEFAULT 'whatsapp',
  proveedor VARCHAR(30) NOT NULL DEFAULT 'manual',
  template_codigo VARCHAR(80) NULL,
  mensaje_render TEXT NULL,
  enlace_whatsapp VARCHAR(600) NULL,
  estado VARCHAR(24) NOT NULL DEFAULT 'pendiente',
  programado_en DATETIME NOT NULL,
  enviado_en DATETIME NULL,
  id_mensaje_externo VARCHAR(120) NULL,
  intentos SMALLINT NOT NULL DEFAULT 0,
  ultimo_error VARCHAR(255) NULL,
  respuesta_texto TEXT NULL,
  respuesta_codigo VARCHAR(24) NULL,
  respuesta_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_recordatorios_clinica_estado_prog (id_clinica, estado, programado_en),
  KEY idx_recordatorios_turno_tipo (id_turno, tipo),
  KEY idx_recordatorios_msg_ext (id_mensaje_externo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO config (id_clinica, clave, valor)
SELECT 1, 'recordatorios.enabled', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM config WHERE clave = 'recordatorios.enabled' AND id_clinica = 1);

INSERT INTO config (id_clinica, clave, valor)
SELECT 1, 'recordatorios.auto_confirmar', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM config WHERE clave = 'recordatorios.auto_confirmar' AND id_clinica = 1);

INSERT INTO config (id_clinica, clave, valor)
SELECT 1, 'recordatorios.hora_recordatorio', '18:00'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM config WHERE clave = 'recordatorios.hora_recordatorio' AND id_clinica = 1);

INSERT INTO config (id_clinica, clave, valor)
SELECT 1, 'recordatorios.sucursal_plantillas', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM config WHERE clave = 'recordatorios.sucursal_plantillas' AND id_clinica = 1);

INSERT INTO config (id_clinica, clave, valor)
SELECT 1, 'recordatorios.modo', 'manual'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM config WHERE clave = 'recordatorios.modo' AND id_clinica = 1);
