-- Reparación post seed 003 en Control Salud
--
-- El seed 003 asume perfil_id 1-5 (HMG=1). En CS los perfiles suelen estar en
-- id 6-10 tras seeds 001+002. Sin este fix, la fórmula leucocitaria queda en
-- filas huérfanas y HMG puede quedar con determinaciones incorrectas.
--
-- Idempotente: borra y rearme por codigo de perfil/determinacion.

DELETE pd FROM lab_perfil_determinaciones pd
LEFT JOIN lab_perfiles p ON p.id = pd.perfil_id
WHERE p.id IS NULL;

DELETE pd FROM lab_perfil_determinaciones pd
INNER JOIN lab_perfiles p ON p.id = pd.perfil_id
WHERE p.codigo IN ('HMG', 'TIR', 'LIP', 'HEP', 'PREQ');

INSERT INTO lab_perfil_determinaciones (perfil_id, determinacion_id, orden)
SELECT p.id, d.id, m.orden
FROM (
    SELECT 'HMG' AS perfil_codigo, 'HB'  AS det_codigo,  1 AS orden UNION ALL
    SELECT 'HMG', 'HTO',  2 UNION ALL
    SELECT 'HMG', 'GR',   3 UNION ALL
    SELECT 'HMG', 'GB',   4 UNION ALL
    SELECT 'HMG', 'PLT',  5 UNION ALL
    SELECT 'HMG', 'VCM',  6 UNION ALL
    SELECT 'HMG', 'HCM',  7 UNION ALL
    SELECT 'HMG', 'CHCM', 8 UNION ALL
    SELECT 'HMG', 'RDW',  9 UNION ALL
    SELECT 'HMG', 'NEU', 10 UNION ALL
    SELECT 'HMG', 'EOS', 11 UNION ALL
    SELECT 'HMG', 'BAS', 12 UNION ALL
    SELECT 'HMG', 'LIN', 13 UNION ALL
    SELECT 'HMG', 'MON', 14 UNION ALL
    SELECT 'TIR', 'TSH',  1 UNION ALL
    SELECT 'TIR', 'T4L',  2 UNION ALL
    SELECT 'LIP', 'COL',  1 UNION ALL
    SELECT 'LIP', 'TG',   2 UNION ALL
    SELECT 'LIP', 'HDL',  3 UNION ALL
    SELECT 'LIP', 'LDL',  4 UNION ALL
    SELECT 'HEP', 'AST',  1 UNION ALL
    SELECT 'HEP', 'ALT',  2 UNION ALL
    SELECT 'HEP', 'BIT',  3 UNION ALL
    SELECT 'HEP', 'BID',  4 UNION ALL
    SELECT 'HEP', 'FAL',  5 UNION ALL
    SELECT 'HEP', 'ALB',  6 UNION ALL
    SELECT 'HEP', 'PT',   7 UNION ALL
    SELECT 'PREQ', 'GLU',  1 UNION ALL
    SELECT 'PREQ', 'URE',  2 UNION ALL
    SELECT 'PREQ', 'CRE',  3 UNION ALL
    SELECT 'PREQ', 'HB',   4 UNION ALL
    SELECT 'PREQ', 'HTO',  5 UNION ALL
    SELECT 'PREQ', 'PLT',  6
) AS m
INNER JOIN lab_perfiles p ON p.codigo = m.perfil_codigo AND p.deleted_at IS NULL
INNER JOIN lab_determinaciones d ON d.codigo = m.det_codigo AND d.deleted_at IS NULL;
