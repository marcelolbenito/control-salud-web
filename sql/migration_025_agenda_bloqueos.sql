-- Bloqueos de agenda: el profesional no atiende en un rango de fechas (día completo o franja horaria).
-- La web no ofrece esos huecos al cargar turnos ni permite guardar en horario bloqueado (salvo edición sin cambio de fecha/hora/doctor).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS agenda_bloqueos (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_clinica INT NOT NULL DEFAULT 1,
  doctor INT NOT NULL COMMENT 'id lista_doctores',
  fecha_desde DATE NOT NULL,
  fecha_hasta DATE NOT NULL,
  hora_desde TIME NULL COMMENT 'NULL = bloquea todo el día en cada fecha del rango',
  hora_hasta TIME NULL COMMENT 'Fin exclusivo del intervalo (misma convención que slots de agenda)',
  motivo VARCHAR(255) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_agenda_bloq_clin_doc (id_clinica, doctor),
  KEY idx_agenda_bloq_fechas (id_clinica, doctor, fecha_desde, fecha_hasta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
