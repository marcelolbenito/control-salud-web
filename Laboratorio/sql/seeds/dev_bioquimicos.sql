-- Seed dev de bioquimicos. Es nuestro catalogo (lab_bioquimicos).
-- La migration 013 debe estar aplicada antes.

INSERT INTO lab_bioquimicos
  (apellido, nombres, matricula, titulo, especialidad, telefono, email, activo)
VALUES
  ('Salinas',    'Lucia',    'BIO-001', 'Bioquimica',          'Hematologia',       '+5491133000001', 'lsalinas@lab.local',    1),
  ('Pereyra',    'Gustavo',  'BIO-002', 'Bioquimico',          'Quimica Clinica',   '+5491133000002', 'gpereyra@lab.local',    1),
  ('Vega',       'Ana',      'BIO-003', 'Bioquimica',          'Endocrinologia',    '+5491133000003', 'avega@lab.local',       1),
  ('Morales',    'Sebastian','BIO-004', 'Doctor en Bioquimica','Inmunologia',       '+5491133000004', 'smorales@lab.local',    1),
  ('Reyes',      'Maria',    'BIO-005', 'Bioquimica',          'Microbiologia',     '+5491133000005', 'mreyes@lab.local',      1),
  ('Quiroga',    'Daniel',   'BIO-006', 'Bioquimico',          'Coagulacion',       '+5491133000006', 'dquiroga@lab.local',    1),
  ('Bravo',      'Florencia','BIO-007', 'Bioquimica',          NULL,                NULL,             'fbravo@lab.local',      1),
  ('Toledo',     'Esteban',  'BIO-008', 'Bioquimico',          'Toxicologia',       '+5491133000008', 'etoledo@lab.local',     0)
ON DUPLICATE KEY UPDATE apellido = VALUES(apellido);
