-- ============================================================================
-- Seed de desarrollo: medicos.
-- En produccion la tabla la maneja el sistema principal.
-- ============================================================================

CREATE TABLE IF NOT EXISTS medicos (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    apellido     VARCHAR(100) NOT NULL,
    nombres      VARCHAR(100) NOT NULL,
    matricula    VARCHAR(40)  NOT NULL,
    especialidad VARCHAR(100) NULL,
    telefono     VARCHAR(30)  NULL,
    email        VARCHAR(150) NULL,
    activo       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at   DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medicos_matricula (matricula),
    KEY idx_medicos_apellido (apellido),
    KEY idx_medicos_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO medicos (apellido, nombres, matricula, especialidad, telefono, email, activo) VALUES
  ('Alvarez',    'Martin',    'MN-12345', 'Clinica Medica',     '+5491111000001', 'malvarez@clinica.local', 1),
  ('Benitez',    'Laura',     'MN-12346', 'Endocrinologia',     '+5491111000002', 'lbenitez@clinica.local', 1),
  ('Castillo',   'Roberto',   'MN-12347', 'Cardiologia',        '+5491111000003', 'rcastillo@clinica.local', 1),
  ('Diaz',       'Patricia',  'MN-12348', 'Ginecologia',        '+5491111000004', 'pdiaz@clinica.local', 1),
  ('Esposito',   'Andres',    'MN-12349', 'Urologia',           '+5491111000005', 'aesposito@clinica.local', 1),
  ('Fernandez',  'Carolina',  'MN-12350', 'Pediatria',          '+5491111000006', 'cfernandez@clinica.local', 1),
  ('Gomez',      'Federico',  'MN-12351', 'Traumatologia',      '+5491111000007', 'fgomez@clinica.local', 1),
  ('Herrera',    'Soledad',   'MN-12352', 'Dermatologia',       '+5491111000008', 'sherrera@clinica.local', 1),
  ('Iglesias',   'Tomas',     'MN-12353', 'Gastroenterologia',  '+5491111000009', 'tiglesias@clinica.local', 1),
  ('Jimenez',    'Valentina', 'MN-12354', 'Neurologia',         '+5491111000010', 'vjimenez@clinica.local', 1),
  ('Lopez',      'Hernan',    'MN-12355', 'Nefrologia',         '+5491111000011', 'hlopez@clinica.local', 1),
  ('Martinez',   'Daniela',   'MN-12356', 'Hematologia',        '+5491111000012', 'dmartinez@clinica.local', 1),
  ('Nunez',      'Pablo',     'MN-12357', 'Reumatologia',       '+5491111000013', 'pnunez@clinica.local', 1),
  ('Ortega',     'Mariana',   'MN-12358', 'Oncologia',          '+5491111000014', 'mortega@clinica.local', 1),
  ('Perez',      'Jorge',     'MN-12359', 'Clinica Medica',     '+5491111000015', 'jperez@clinica.local', 1)
ON DUPLICATE KEY UPDATE apellido = VALUES(apellido);
