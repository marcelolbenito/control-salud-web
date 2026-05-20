-- Seed de desarrollo SOLO. NO correr en produccion.
-- En produccion estas tablas las maneja el sistema principal.

SET NAMES utf8mb4;

-- ============================================================
-- obras_sociales
-- ============================================================
CREATE TABLE IF NOT EXISTS obras_sociales (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(150) NOT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_obras_sociales_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- pacientes
-- ============================================================
CREATE TABLE IF NOT EXISTS pacientes (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nro_hc            VARCHAR(20)  NOT NULL,
    dni               VARCHAR(20)  NOT NULL,
    apellido          VARCHAR(100) NOT NULL,
    nombres           VARCHAR(100) NOT NULL,
    telefono          VARCHAR(30)  NULL,
    fecha_nacimiento  DATE         NULL,
    sexo              ENUM('M','F','X') NOT NULL,
    obra_social_id    BIGINT UNSIGNED NULL,
    nro_afiliado      VARCHAR(50)  NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pacientes_nro_hc (nro_hc),
    KEY idx_pacientes_dni (dni),
    KEY idx_pacientes_apellido (apellido),
    KEY idx_pacientes_obra_social (obra_social_id),
    CONSTRAINT fk_pacientes_obra_social
        FOREIGN KEY (obra_social_id) REFERENCES obras_sociales(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Datos de prueba
-- ============================================================
INSERT INTO obras_sociales (id, nombre, activo) VALUES
  (1, 'OSDE',           1),
  (2, 'Swiss Medical',  1),
  (3, 'Galeno',         1),
  (4, 'IOMA',           1),
  (5, 'PAMI',           1),
  (6, 'OSECAC',         1),
  (7, 'Medicus',        1),
  (8, 'Omint',          1),
  (9, 'Sancor Salud',   1),
  (10,'Particular',     1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO pacientes
  (nro_hc, dni, apellido, nombres, telefono, fecha_nacimiento, sexo, obra_social_id, nro_afiliado)
VALUES
  ('00001','30111111','Perez',     'Juan',          '+5491155551001','1985-03-12','M',1,'OSD-1001'),
  ('00002','30222222','Perez',     'Ana',           '+5491155551002','1990-07-21','F',1,'OSD-1002'),
  ('00003','30333333','Gomez',     'Maria',         '+5491155551003','1978-11-05','F',2,'SM-2003'),
  ('00004','30444444','Gomez',     'Luis',          NULL,            '1965-02-28','M',2,'SM-2004'),
  ('00005','30555555','Lopez',     'Carla',         '+5491155551005','2001-09-15','F',3,'GAL-3005'),
  ('00006','30666666','Lopez',     'Federico',      '+5491155551006','1995-12-01','M',3,'GAL-3006'),
  ('00007','30777777','Martinez',  'Sofia',         '+5491155551007','1988-06-18','F',4,'IOM-4007'),
  ('00008','30888888','Martinez',  'Diego',         '+5491155551008','1972-10-30','M',4,'IOM-4008'),
  ('00009','30999999','Rodriguez', 'Lucia',         '+5491155551009','1999-04-22','F',5,'PAMI-5009'),
  ('00010','31000000','Rodriguez', 'Carlos',        '+5491155551010','1955-08-14','M',5,'PAMI-5010'),
  ('00011','31111111','Fernandez', 'Valentina',     '+5491155551011','2003-01-09','F',6,'OSEC-6011'),
  ('00012','31222222','Fernandez', 'Mateo',         '+5491155551012','1980-05-25','M',6,'OSEC-6012'),
  ('00013','31333333','Garcia',    'Camila',        '+5491155551013','1992-11-11','F',7,'MED-7013'),
  ('00014','31444444','Garcia',    'Tomas',         '+5491155551014','1986-03-03','M',7,'MED-7014'),
  ('00015','31555555','Sanchez',   'Julieta',       '+5491155551015','2000-07-07','F',8,'OMI-8015'),
  ('00016','31666666','Sanchez',   'Nicolas',       '+5491155551016','1975-09-19','M',8,'OMI-8016'),
  ('00017','31777777','Romero',    'Florencia',     '+5491155551017','1993-12-24','F',9,'SAN-9017'),
  ('00018','31888888','Romero',    'Lucas',         '+5491155551018','1968-04-04','M',9,'SAN-9018'),
  ('00019','31999999','Diaz',      'Agustina',      NULL,            '2002-08-31','F',10,NULL),
  ('00020','32000000','Diaz',      'Martin',        '+5491155551020','1983-02-14','M',10,NULL),
  ('00021','32111111','Acosta',    'Brenda',        '+5491155551021','1996-10-10','F',1,'OSD-1021'),
  ('00022','32222222','Benitiz',   'Hernan',        '+5491155551022','1971-06-06','M',2,'SM-2022'),
  ('00023','32333333','Castro',    'Veronica',      '+5491155551023','1989-09-09','F',3,'GAL-3023'),
  ('00024','32444444','Diaz',      'Pablo',         '+5491155551024','1977-11-29','M',4,'IOM-4024'),
  ('00025','32555555','Espinoza',  'Gabriela',      '+5491155551025','1994-04-17','F',5,'PAMI-5025'),
  ('00026','32666666','Flores',    'Sebastian',     '+5491155551026','1981-12-12','M',6,'OSEC-6026'),
  ('00027','32777777','Gonzalez',  'Mariana',       '+5491155551027','1998-05-05','F',7,'MED-7027'),
  ('00028','32888888','Herrera',   'Ezequiel',      '+5491155551028','1969-01-23','M',8,'OMI-8028'),
  ('00029','32999999','Iglesias',  'Paula',         '+5491155551029','1991-08-08','F',9,'SAN-9029'),
  ('00030','33000000','Jimenez',   'Ricardo',       '+5491155551030','1973-07-19','M',10,NULL)
ON DUPLICATE KEY UPDATE apellido = VALUES(apellido);

-- Un paciente soft-deleted para verificar que NO aparece en busquedas.
INSERT INTO pacientes
  (nro_hc, dni, apellido, nombres, sexo, deleted_at)
VALUES
  ('99999','99999999','Borrado','Paciente','X','2025-01-01 00:00:00')
ON DUPLICATE KEY UPDATE deleted_at = VALUES(deleted_at);
