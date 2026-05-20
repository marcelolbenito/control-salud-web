-- ============================================================================
-- 001_seed_catalogo.sql
-- ============================================================================
-- Datos semilla iniciales del catalogo:
--   - 5 areas
--   - 30 determinaciones de uso comun en laboratorio clinico
--   - ~36 valores de referencia (adultos, separados por sexo donde aplica)
--   - 5 perfiles tipicos con 28 vinculos perfil-determinacion
--
-- Idempotente:
--   - INSERT IGNORE en tablas con UNIQUE(codigo): areas, determinaciones, perfiles.
--   - DELETE WHERE IN (...) + INSERT en valores_referencia y perfil_determinaciones.
--
-- Notas clinicas:
--   - Los rangos son de adulto general (edad_min/max NULL).
--   - Rangos pediatricos pendientes para Fase 2.
--   - Valores criticos cargados solo en analitos de relevancia clinica
--     (glucemia, electrolitos, hemoglobina, plaquetas, bilirrubina).
-- ============================================================================

START TRANSACTION;

-- ----------------------------------------------------------------------------
-- 1. AREAS
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO lab_areas (codigo, nombre, descripcion, orden, activo) VALUES
('HEM', 'Hematologia',     'Estudio de componentes celulares de la sangre',          1, 1),
('QC',  'Quimica clinica', 'Determinaciones bioquimicas en sangre y orina',          2, 1),
('HOR', 'Hormonas',        'Determinaciones hormonales',                             3, 1),
('INM', 'Inmunologia',     'Determinaciones inmunologicas y serologicas',            4, 1),
('MIC', 'Microbiologia',   'Cultivos y determinaciones microbiologicas',             5, 1);

-- ----------------------------------------------------------------------------
-- 2. DETERMINACIONES (30)
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO lab_determinaciones
    (area_id, codigo, nombre, nombre_corto, unidad, metodo, tipo_resultado, decimales,
     valor_critico_min, valor_critico_max, activo)
SELECT a.id, d.codigo, d.nombre, d.nombre_corto, d.unidad, d.metodo,
       d.tipo_resultado, d.decimales, d.valor_critico_min, d.valor_critico_max, 1
FROM lab_areas a
JOIN (
    -- HEMATOLOGIA (10)
    SELECT 'HEM' AS area_codigo, 'HB'   AS codigo, 'Hemoglobina'                     AS nombre, 'Hb'   AS nombre_corto, 'g/dL'    AS unidad, 'Espectrofotometria'   AS metodo, 'numerico' AS tipo_resultado, 1 AS decimales, 7.0  AS valor_critico_min, 20.0   AS valor_critico_max UNION ALL
    SELECT 'HEM', 'HTO',  'Hematocrito',                       'Hto',  '%',       'Microcentrifugacion', 'numerico', 1, NULL,  NULL UNION ALL
    SELECT 'HEM', 'GR',   'Recuento de globulos rojos',        'GR',   'mill/uL', 'Citometria',          'numerico', 2, NULL,  NULL UNION ALL
    SELECT 'HEM', 'GB',   'Recuento de globulos blancos',      'GB',   'mil/uL',  'Citometria',          'numerico', 2, 1.0,   30.0 UNION ALL
    SELECT 'HEM', 'PLT',  'Recuento de plaquetas',             'Plaq', 'mil/uL',  'Citometria',          'numerico', 0, 50.0,  1000.0 UNION ALL
    SELECT 'HEM', 'VCM',  'Volumen corpuscular medio',         'VCM',  'fL',      'Calculado',           'numerico', 1, NULL,  NULL UNION ALL
    SELECT 'HEM', 'HCM',  'Hemoglobina corpuscular media',     'HCM',  'pg',      'Calculado',           'numerico', 1, NULL,  NULL UNION ALL
    SELECT 'HEM', 'CHCM', 'Concentracion de Hb corp. media',   'CHCM', 'g/dL',    'Calculado',           'numerico', 1, NULL,  NULL UNION ALL
    SELECT 'HEM', 'RDW',  'Ancho de distribucion eritrocit.',  'RDW',  '%',       'Calculado',           'numerico', 1, NULL,  NULL UNION ALL
    SELECT 'HEM', 'VSG',  'Eritrosedimentacion',               'VSG',  'mm/h',    'Westergren',          'numerico', 0, NULL,  NULL UNION ALL
    -- QUIMICA CLINICA (18)
    SELECT 'QC',  'GLU',  'Glucemia',                          'Gluc', 'mg/dL',   'Hexoquinasa',         'numerico', 0, 40.0,  400.0 UNION ALL
    SELECT 'QC',  'URE',  'Urea',                              'Urea', 'mg/dL',   'Ureasa-GLDH',         'numerico', 0, NULL,  200.0 UNION ALL
    SELECT 'QC',  'CRE',  'Creatinina',                        'Crea', 'mg/dL',   'Jaffe cinetico',      'numerico', 2, NULL,  8.0 UNION ALL
    SELECT 'QC',  'AU',   'Acido urico',                       'AU',   'mg/dL',   'Uricasa-PAP',         'numerico', 1, NULL,  NULL UNION ALL
    SELECT 'QC',  'COL',  'Colesterol total',                  'CT',   'mg/dL',   'CHOD-PAP',            'numerico', 0, NULL,  NULL UNION ALL
    SELECT 'QC',  'TG',   'Trigliceridos',                     'TG',   'mg/dL',   'GPO-PAP',             'numerico', 0, NULL,  NULL UNION ALL
    SELECT 'QC',  'HDL',  'Colesterol HDL',                    'HDL',  'mg/dL',   'Directo',             'numerico', 0, NULL,  NULL UNION ALL
    SELECT 'QC',  'LDL',  'Colesterol LDL',                    'LDL',  'mg/dL',   'Friedewald',          'numerico', 0, NULL,  NULL UNION ALL
    SELECT 'QC',  'AST',  'AST / GOT',                         'AST',  'U/L',     'IFCC',                'numerico', 0, NULL,  NULL UNION ALL
    SELECT 'QC',  'ALT',  'ALT / GPT',                         'ALT',  'U/L',     'IFCC',                'numerico', 0, NULL,  NULL UNION ALL
    SELECT 'QC',  'BIT',  'Bilirrubina total',                 'BiT',  'mg/dL',   'Diazorreaccion',      'numerico', 2, NULL,  15.0 UNION ALL
    SELECT 'QC',  'BID',  'Bilirrubina directa',               'BiD',  'mg/dL',   'Diazorreaccion',      'numerico', 2, NULL,  NULL UNION ALL
    SELECT 'QC',  'FAL',  'Fosfatasa alcalina',                'FAL',  'U/L',     'IFCC',                'numerico', 0, NULL,  NULL UNION ALL
    SELECT 'QC',  'ALB',  'Albumina',                          'Alb',  'g/dL',    'Verde de bromocresol','numerico', 1, NULL,  NULL UNION ALL
    SELECT 'QC',  'PT',   'Proteinas totales',                 'PT',   'g/dL',    'Biuret',              'numerico', 1, NULL,  NULL UNION ALL
    SELECT 'QC',  'NA',   'Sodio',                             'Na',   'mEq/L',   'ISE',                 'numerico', 0, 120.0, 160.0 UNION ALL
    SELECT 'QC',  'K',    'Potasio',                           'K',    'mEq/L',   'ISE',                 'numerico', 1, 2.5,   6.5 UNION ALL
    SELECT 'QC',  'CA',   'Calcio',                            'Ca',   'mg/dL',   'Arsenazo III',        'numerico', 1, 7.0,   12.0 UNION ALL
    -- HORMONAS (2)
    SELECT 'HOR', 'TSH',  'Tirotrofina',                       'TSH',  'mU/L',    'Quimioluminiscencia', 'numerico', 2, NULL,  NULL UNION ALL
    SELECT 'HOR', 'T4L',  'Tiroxina libre',                    'T4L',  'ng/dL',   'Quimioluminiscencia', 'numerico', 2, NULL,  NULL
) d ON d.area_codigo = a.codigo;

-- ----------------------------------------------------------------------------
-- 3. VALORES DE REFERENCIA (adultos)
-- ----------------------------------------------------------------------------
-- Limpiar primero para idempotencia (no hay UNIQUE compuesta en valores_referencia)
DELETE FROM lab_valores_referencia
WHERE determinacion_id IN (
    SELECT id FROM lab_determinaciones
    WHERE codigo IN ('HB','HTO','GR','GB','PLT','VCM','HCM','CHCM','RDW','VSG',
                     'GLU','URE','CRE','AU','COL','TG','HDL','LDL',
                     'AST','ALT','BIT','BID','FAL','ALB','PT','NA','K','CA',
                     'TSH','T4L')
);

INSERT INTO lab_valores_referencia
    (determinacion_id, sexo, edad_min_dias, edad_max_dias, valor_min, valor_max, observaciones)
SELECT d.id, vr.sexo, vr.edad_min_dias, vr.edad_max_dias, vr.valor_min, vr.valor_max, vr.observaciones
FROM lab_determinaciones d
JOIN (
    -- HEMATOLOGIA
    SELECT 'HB'   AS codigo, 'M'     AS sexo, NULL AS edad_min_dias, NULL AS edad_max_dias, 13.5  AS valor_min, 17.5   AS valor_max, 'Adulto'                 AS observaciones UNION ALL
    SELECT 'HB',   'F',     NULL, NULL, 12.0,   16.0,  'Adulto' UNION ALL
    SELECT 'HTO',  'M',     NULL, NULL, 41.0,   53.0,  'Adulto' UNION ALL
    SELECT 'HTO',  'F',     NULL, NULL, 36.0,   46.0,  'Adulto' UNION ALL
    SELECT 'GR',   'M',     NULL, NULL, 4.50,   5.90,  'Adulto' UNION ALL
    SELECT 'GR',   'F',     NULL, NULL, 4.00,   5.20,  'Adulto' UNION ALL
    SELECT 'GB',   'ambos', NULL, NULL, 4.50,   11.00, 'Adulto' UNION ALL
    SELECT 'PLT',  'ambos', NULL, NULL, 150.0,  450.0, 'Adulto' UNION ALL
    SELECT 'VCM',  'ambos', NULL, NULL, 80.0,   100.0, 'Adulto' UNION ALL
    SELECT 'HCM',  'ambos', NULL, NULL, 27.0,   33.0,  'Adulto' UNION ALL
    SELECT 'CHCM', 'ambos', NULL, NULL, 32.0,   36.0,  'Adulto' UNION ALL
    SELECT 'RDW',  'ambos', NULL, NULL, 11.5,   14.5,  'Adulto' UNION ALL
    SELECT 'VSG',  'M',     NULL, NULL, 0.0,    15.0,  'Adulto' UNION ALL
    SELECT 'VSG',  'F',     NULL, NULL, 0.0,    20.0,  'Adulto' UNION ALL
    -- QUIMICA CLINICA
    SELECT 'GLU',  'ambos', NULL, NULL, 70.0,   110.0, 'En ayunas' UNION ALL
    SELECT 'URE',  'ambos', NULL, NULL, 15.0,   45.0,  'Adulto' UNION ALL
    SELECT 'CRE',  'M',     NULL, NULL, 0.70,   1.30,  'Adulto' UNION ALL
    SELECT 'CRE',  'F',     NULL, NULL, 0.60,   1.10,  'Adulto' UNION ALL
    SELECT 'AU',   'M',     NULL, NULL, 3.4,    7.0,   'Adulto' UNION ALL
    SELECT 'AU',   'F',     NULL, NULL, 2.4,    6.0,   'Adulto' UNION ALL
    SELECT 'COL',  'ambos', NULL, NULL, NULL,   200.0, 'Deseable: < 200 mg/dL' UNION ALL
    SELECT 'TG',   'ambos', NULL, NULL, NULL,   150.0, 'Deseable: < 150 mg/dL' UNION ALL
    SELECT 'HDL',  'M',     NULL, NULL, 40.0,   NULL,  'Deseable: > 40 mg/dL' UNION ALL
    SELECT 'HDL',  'F',     NULL, NULL, 50.0,   NULL,  'Deseable: > 50 mg/dL' UNION ALL
    SELECT 'LDL',  'ambos', NULL, NULL, NULL,   100.0, 'Optimo: < 100 mg/dL' UNION ALL
    SELECT 'AST',  'ambos', NULL, NULL, 5.0,    40.0,  'Adulto' UNION ALL
    SELECT 'ALT',  'ambos', NULL, NULL, 5.0,    45.0,  'Adulto' UNION ALL
    SELECT 'BIT',  'ambos', NULL, NULL, 0.10,   1.20,  'Adulto' UNION ALL
    SELECT 'BID',  'ambos', NULL, NULL, 0.00,   0.30,  'Adulto' UNION ALL
    SELECT 'FAL',  'ambos', NULL, NULL, 40.0,   130.0, 'Adulto' UNION ALL
    SELECT 'ALB',  'ambos', NULL, NULL, 3.5,    5.0,   'Adulto' UNION ALL
    SELECT 'PT',   'ambos', NULL, NULL, 6.0,    8.3,   'Adulto' UNION ALL
    SELECT 'NA',   'ambos', NULL, NULL, 135.0,  145.0, 'Adulto' UNION ALL
    SELECT 'K',    'ambos', NULL, NULL, 3.5,    5.0,   'Adulto' UNION ALL
    SELECT 'CA',   'ambos', NULL, NULL, 8.5,    10.5,  'Adulto' UNION ALL
    -- HORMONAS
    SELECT 'TSH',  'ambos', NULL, NULL, 0.40,   4.50,  'Adulto' UNION ALL
    SELECT 'T4L',  'ambos', NULL, NULL, 0.80,   1.80,  'Adulto'
) vr ON vr.codigo = d.codigo;

-- ----------------------------------------------------------------------------
-- 4. PERFILES (5)
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO lab_perfiles (codigo, nombre, descripcion, activo) VALUES
('HMG',  'Hemograma completo',     'Recuento celular completo: serie roja, blanca y plaquetaria',  1),
('TIR',  'Perfil tiroideo',        'TSH y tiroxina libre',                                         1),
('LIP',  'Perfil lipidico',        'Colesterol total, trigliceridos, HDL y LDL',                   1),
('HEP',  'Hepatograma',            'Transaminasas, bilirrubinas, fosfatasa alcalina y proteinas',  1),
('PREQ', 'Prequirurgico basico',   'Estudios basicos previos a cirugia',                           1);

-- ----------------------------------------------------------------------------
-- 5. PIVOTE PERFIL <-> DETERMINACION (28 vinculos)
-- ----------------------------------------------------------------------------
DELETE FROM lab_perfil_determinaciones
WHERE perfil_id IN (
    SELECT id FROM lab_perfiles WHERE codigo IN ('HMG','TIR','LIP','HEP','PREQ')
);

INSERT INTO lab_perfil_determinaciones (perfil_id, determinacion_id, orden)
SELECT p.id, d.id, pd.orden
FROM lab_perfiles p
JOIN (
    -- Hemograma completo
    SELECT 'HMG'  AS perfil_codigo, 'HB'   AS det_codigo, 1 AS orden UNION ALL
    SELECT 'HMG',  'HTO',  2 UNION ALL
    SELECT 'HMG',  'GR',   3 UNION ALL
    SELECT 'HMG',  'GB',   4 UNION ALL
    SELECT 'HMG',  'PLT',  5 UNION ALL
    SELECT 'HMG',  'VCM',  6 UNION ALL
    SELECT 'HMG',  'HCM',  7 UNION ALL
    SELECT 'HMG',  'CHCM', 8 UNION ALL
    SELECT 'HMG',  'RDW',  9 UNION ALL
    -- Perfil tiroideo
    SELECT 'TIR',  'TSH',  1 UNION ALL
    SELECT 'TIR',  'T4L',  2 UNION ALL
    -- Perfil lipidico
    SELECT 'LIP',  'COL',  1 UNION ALL
    SELECT 'LIP',  'TG',   2 UNION ALL
    SELECT 'LIP',  'HDL',  3 UNION ALL
    SELECT 'LIP',  'LDL',  4 UNION ALL
    -- Hepatograma
    SELECT 'HEP',  'AST',  1 UNION ALL
    SELECT 'HEP',  'ALT',  2 UNION ALL
    SELECT 'HEP',  'BIT',  3 UNION ALL
    SELECT 'HEP',  'BID',  4 UNION ALL
    SELECT 'HEP',  'FAL',  5 UNION ALL
    SELECT 'HEP',  'ALB',  6 UNION ALL
    SELECT 'HEP',  'PT',   7 UNION ALL
    -- Prequirurgico basico
    SELECT 'PREQ', 'GLU',  1 UNION ALL
    SELECT 'PREQ', 'URE',  2 UNION ALL
    SELECT 'PREQ', 'CRE',  3 UNION ALL
    SELECT 'PREQ', 'HB',   4 UNION ALL
    SELECT 'PREQ', 'HTO',  5 UNION ALL
    SELECT 'PREQ', 'PLT',  6
) pd ON pd.perfil_codigo = p.codigo
JOIN lab_determinaciones d ON d.codigo = pd.det_codigo;

COMMIT;
