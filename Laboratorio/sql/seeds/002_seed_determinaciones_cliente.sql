-- ============================================================================
-- 002_seed_determinaciones_cliente.sql
-- ============================================================================
-- Listado de analisis del cliente (importado desde Determinaciones.xlsx /
-- codigos laboratorio.pdf). El cliente trabaja con estos codigos y nombres
-- y quiere seguir haciendolo, asi que esta es la fuente de verdad del
-- catalogo en produccion.
--
-- Diferencias con 001_seed_catalogo.sql:
--   - Usa los codigos numericos del cliente (412, 475, 865, ...) en vez
--     de codigos alfabeticos genericos (GLU, HEM, TSH).
--   - Cubre ~300 determinaciones (vs 30 del seed generico).
--   - unidad queda vacia: el cliente la completa al cargar valores de
--     referencia, ya que el PDF original no la trae.
--   - Areas asignadas heuristicamente por keywords del nombre. Las que
--     no matchean caen en OTROS para que el cliente las reclasifique.
--
-- Idempotente: INSERT IGNORE sobre UNIQUE(codigo).
-- Se asume que 001_seed_catalogo.sql ya creo las areas HEM, QC, HOR, INM, MIC.
-- ============================================================================

START TRANSACTION;

-- ----------------------------------------------------------------------------
-- 1. Area "OTROS" (fallback para todo lo no clasificable por heuristica)
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO lab_areas (codigo, nombre, descripcion, orden, activo) VALUES
('OTR', 'Otros', 'Determinaciones sin area asignada (clasificar manualmente)', 99, 1);

-- ----------------------------------------------------------------------------
-- 2. Determinaciones del cliente
-- ----------------------------------------------------------------------------
-- Insertamos en una tabla derivada y dejamos que un CASE asigne el area_id
-- segun palabras clave del nombre. Si nada matchea, va a OTR.
-- ----------------------------------------------------------------------------

INSERT IGNORE INTO lab_determinaciones
    (area_id, codigo, nombre, unidad, tipo_resultado, decimales, activo)
SELECT
    (SELECT id FROM lab_areas WHERE codigo =
        CASE
            -- Hematologia
            WHEN s.nombre LIKE '%HEMOGRAMA%'
              OR s.nombre LIKE '%HEMOGLOBINA%'
              OR s.nombre LIKE '%HEMATOCRITO%'
              OR s.nombre LIKE '%PLAQUETAS%'
              OR s.nombre LIKE '%RETICULOCITOS%'
              OR s.nombre LIKE '%ERITROSED%'
              OR s.nombre LIKE '%GRUPO SANGUINEO%'
              OR s.nombre LIKE '%COOMBS%'
              OR s.nombre LIKE '%METAHEMOGLOBINA%'
              OR s.nombre LIKE '%ERITROPOYETINA%'
              OR s.nombre LIKE '%POLIMORFONUCLEARES%'
              OR s.nombre LIKE '%EOSINOFILOS%'
                THEN 'HEM'

            -- Coagulacion → HEM (no hay area COA en el seed base)
            WHEN s.nombre LIKE '%COAGULACION%'
              OR s.nombre LIKE '%COAGULOGRAMA%'
              OR s.nombre LIKE '%PROTROMBINA%'
              OR s.nombre LIKE '%TROMBOPLASTINA%'
              OR s.nombre LIKE '%KPTT%'
              OR s.nombre LIKE '%FIBRIN%'
              OR s.nombre LIKE '%DIMERO%'
              OR s.nombre LIKE '%FACTOR VIII%'
              OR s.nombre LIKE '%VON WILLEBRAND%'
              OR s.nombre LIKE '%ANTICOAGULANTE LUPICO%'
              OR s.nombre LIKE '%RIN%'
                THEN 'HEM'

            -- Hormonas
            WHEN s.nombre LIKE '%TSH%'
              OR s.nombre LIKE '%TIROTROFINA%'
              OR s.nombre LIKE '%TIROXINA%'
              OR s.nombre LIKE '%T3%'
              OR s.nombre LIKE '%T4%'
              OR s.nombre LIKE '%TIROIDE%'
              OR s.nombre LIKE '%TIROGLOBULINA%'
              OR s.nombre LIKE '%FSH%'
              OR s.nombre LIKE '%LH%'
              OR s.nombre LIKE '%LUTEINIZANTE%'
              OR s.nombre LIKE '%PROLACTINA%'
              OR s.nombre LIKE '%CORTISOL%'
              OR s.nombre LIKE '%INSULINA%'
              OR s.nombre LIKE '%ESTRADIOL%'
              OR s.nombre LIKE '%ESTRONA%'
              OR s.nombre LIKE '%PROGESTERONA%'
              OR s.nombre LIKE '%TESTOSTERONA%'
              OR s.nombre LIKE '%ANDROSTENODIONA%'
              OR s.nombre LIKE '%ALDOSTERONA%'
              OR s.nombre LIKE '%ACTH%'
              OR s.nombre LIKE '%PARATHORMONA%'
              OR s.nombre LIKE '%PTH%'
              OR s.nombre LIKE '%CALCITONINA%'
              OR s.nombre LIKE '%SOMATOTROFINA%'
              OR s.nombre LIKE '%SOMATOMEDINA%'
              OR s.nombre LIKE '%RENINA%'
              OR s.nombre LIKE '%DHEA%'
              OR s.nombre LIKE '%DEHIDROEPIANDROSTERONA%'
              OR s.nombre LIKE '%MULLERIANA%'
              OR s.nombre LIKE '%INHIBINA%'
              OR s.nombre LIKE '%HCG%'
              OR s.nombre LIKE '%GONADOTROFINA%'
              OR s.nombre LIKE '%GRAVINDEX%'
              OR s.nombre LIKE '%SUBUNIDAD BETA%'
              OR s.nombre LIKE '%17-OH%'
              OR s.nombre LIKE '%HIDROXIPROGESTERONA%'
              OR s.nombre LIKE '%ANTIDIURETICA%'
              OR s.nombre LIKE '%VASOPRESINA%'
              OR s.nombre LIKE '%PEPTIDO C%'
              OR s.nombre LIKE '%FRUCTOSAMINA%'
                THEN 'HOR'

            -- Microbiologia
            WHEN s.nombre LIKE '%CULTIVO%'
              OR s.nombre LIKE '%COPROCULTIVO%'
              OR s.nombre LIKE '%MICOLOGIA%'
              OR s.nombre LIKE '%BACTERIOLOGIA%'
              OR s.nombre LIKE '%ANTIBIOGRAMA%'
              OR s.nombre LIKE '%RECUENTO DE COLONIAS%'
              OR s.nombre LIKE '%MYCOPLASMA%'
              OR s.nombre LIKE '%UREAPLASMA%'
              OR s.nombre LIKE '%EXUDADO%'
              OR s.nombre LIKE '%HISOPADO%'
              OR s.nombre LIKE '%PARASITOLOG%'
              OR s.nombre LIKE '%STREPTOCOCCUS%'
              OR s.nombre LIKE '%CRYPTOCOCCUS%'
              OR s.nombre LIKE '%CANDIDA%'
              OR s.nombre LIKE '%COCCIDIOIDES%'
              OR s.nombre LIKE '%HISTOPLASMA%'
              OR s.nombre LIKE '%PARACOCCIDIOIDES%'
              OR s.nombre LIKE '%CLOSTRIDIUM%'
              OR s.nombre LIKE '%MANTOUX%'
              OR s.nombre LIKE '%PPD%'
                THEN 'MIC'

            -- Inmunologia / serologia
            WHEN s.nombre LIKE '%ANTICUERPO%'
              OR s.nombre LIKE 'Ac.%'
              OR s.nombre LIKE '% AC %'
              OR s.nombre LIKE '%ASTO%'
              OR s.nombre LIKE '%ESTREPTOLISINAS%'
              OR s.nombre LIKE '%ANCA%'
              OR s.nombre LIKE '%FAN%'
              OR s.nombre LIKE '%ANTINUCLEARES%'
              OR s.nombre LIKE '%ASMA%'
              OR s.nombre LIKE '%AMA%'
              OR s.nombre LIKE '%ENA Ac%'
              OR s.nombre LIKE '%ENDOMISIO%'
              OR s.nombre LIKE '%MEMBRANA BASAL%'
              OR s.nombre LIKE '%LKM%'
              OR s.nombre LIKE '%JO-1%'
              OR s.nombre LIKE '%SCL 70%'
              OR s.nombre LIKE '%CENTROMERO%'
              OR s.nombre LIKE '%RIBONUCLEOPROT%'
              OR s.nombre LIKE '%RNP%'
              OR s.nombre LIKE '%Ro %'
              OR s.nombre LIKE '%La /%'
              OR s.nombre LIKE '%SS-%'
              OR s.nombre LIKE '%HISTONA%'
              OR s.nombre LIKE '%CARDIOLIPINAS%'
              OR s.nombre LIKE '%FOSFOLIPIDOS%'
              OR s.nombre LIKE '%BETA 2 GLICOPROTEINA%'
              OR s.nombre LIKE '%PEPTIDO CITRULINADO%'
              OR s.nombre LIKE '%FACTOR REUMATOIDEO%'
              OR s.nombre LIKE '%ARTRITEST%'
              OR s.nombre LIKE '%CRIOGLOBULINAS%'
              OR s.nombre LIKE '%COMPLEMENTO%'
              OR s.nombre LIKE '%INMUNOGLOBULINA%'
              OR s.nombre LIKE '%IgE%'
              OR s.nombre LIKE '%IgG%'
              OR s.nombre LIKE '%IgM%'
              OR s.nombre LIKE '%IgA%'
              OR s.nombre LIKE '%RAST%'
              OR s.nombre LIKE '%CHAGAS%'
              OR s.nombre LIKE '%TOXOPLASMOSIS%'
              OR s.nombre LIKE '%TOCOPLASMOSIS%'
              OR s.nombre LIKE '%TOXO%'
              OR s.nombre LIKE '%RUBEOLA%'
              OR s.nombre LIKE '%SARAMPION%'
              OR s.nombre LIKE '%PAROTIDITIS%'
              OR s.nombre LIKE '%VARICELA%'
              OR s.nombre LIKE '%HERPES%'
              OR s.nombre LIKE '%HIV%'
              OR s.nombre LIKE '%HEPATITIS%'
              OR s.nombre LIKE '%HCV%'
              OR s.nombre LIKE '%HBV%'
              OR s.nombre LIKE '%HBs%'
              OR s.nombre LIKE '%HAV%'
              OR s.nombre LIKE '%CITOMEGAL%'
              OR s.nombre LIKE '%EPSTEIN%'
              OR s.nombre LIKE '%MONONUCLEOSIS%'
              OR s.nombre LIKE '%DENGUE%'
              OR s.nombre LIKE '%COVID%'
              OR s.nombre LIKE '%INFLUENZA%'
              OR s.nombre LIKE '%PARVOVIRUS%'
              OR s.nombre LIKE '%HPV%'
              OR s.nombre LIKE '%CHLAMYDIA%'
              OR s.nombre LIKE '%LEPTOSPIRA%'
              OR s.nombre LIKE '%BRUCELOSIS%'
              OR s.nombre LIKE '%HUDDLESSON%'
              OR s.nombre LIKE '%TREPONEMA%'
              OR s.nombre LIKE '%FTA%'
              OR s.nombre LIKE '%VDRL%'
              OR s.nombre LIKE '%V D R L%'
              OR s.nombre LIKE '%USR%'
              OR s.nombre LIKE '%HIDATIDOSIS%'
              OR s.nombre LIKE '%HELICOBACTER%'
              OR s.nombre LIKE '%PYLORI%'
              OR s.nombre LIKE '%ASPERGILLUS%'
              OR s.nombre LIKE '%ASPERGILLIUS%'
              OR s.nombre LIKE '%SACCHAROMYSES%'
              OR s.nombre LIKE '%CELULAS LE%'
              OR s.nombre LIKE '%GLIADINA%'
              OR s.nombre LIKE '%TRANSGLUTAMINASA%'
              OR s.nombre LIKE '%RETICULINA%'
              OR s.nombre LIKE '%TIROIDEO%'
              OR s.nombre LIKE '%PEROXIDASA%'
              OR s.nombre LIKE '%RECEPTOR%'
              OR s.nombre LIKE '%GAD%'
              OR s.nombre LIKE '%GLOMERULO%'
              OR s.nombre LIKE '%ACETILCOLINA%'
              OR s.nombre LIKE '%MARCADOR%'
              OR s.nombre LIKE '%CEA%'
              OR s.nombre LIKE '%PSA%'
              OR s.nombre LIKE '%CA 1%'
              OR s.nombre LIKE '%CA125%'
              OR s.nombre LIKE '%CA15%'
              OR s.nombre LIKE '%CA19%'
              OR s.nombre LIKE '%CA 21%'
              OR s.nombre LIKE '%ALFA FETO%'
              OR s.nombre LIKE '%CARCINOEMBR%'
              OR s.nombre LIKE '%CALPROTECTINA%'
              OR s.nombre LIKE '%PROCALCITONINA%'
              OR s.nombre LIKE '%PROTEINA C REACTIVA%'
              OR s.nombre LIKE '%PCR%'
              OR s.nombre LIKE '%TROPONINA%'
              OR s.nombre LIKE '%NT - Pro BNP%'
              OR s.nombre LIKE '%HLA%'
                THEN 'INM'

            -- Quimica clinica (todo lo bioquimico/electrolitos/orina/lipidos)
            WHEN s.nombre LIKE '%GLUCEMIA%'
              OR s.nombre LIKE 'glucemia%'
              OR s.nombre LIKE '%GLUCOSA%'
              OR s.nombre LIKE '%COLESTEROL%'
              OR s.nombre LIKE '%TRIGLICERID%'
              OR s.nombre LIKE '%PERFIL LIPIDICO%'
              OR s.nombre LIKE '%LDH%'
              OR s.nombre LIKE '%HDL%'
              OR s.nombre LIKE '%LDL%'
              OR s.nombre LIKE '%VLDL%'
              OR s.nombre LIKE '%APOLIPO%'
              OR s.nombre LIKE '%UREMIA%'
              OR s.nombre LIKE '%URICEMIA%'
              OR s.nombre LIKE '%URICO%'
              OR s.nombre LIKE '%CREATININ%'
              OR s.nombre LIKE '%CLEARENCE%'
              OR s.nombre LIKE '%ALBUMIN%'
              OR s.nombre LIKE '%PROTEINA%'
              OR s.nombre LIKE '%PROTEINOGRAMA%'
              OR s.nombre LIKE '%BILIRRUBINA%'
              OR s.nombre LIKE '%HEPATOGRAMA%'
              OR s.nombre LIKE '%TRANSAMINASA%'
              OR s.nombre LIKE '%GAMMA-GT%'
              OR s.nombre LIKE '%FOSFATASA%'
              OR s.nombre LIKE '%CPK%'
              OR s.nombre LIKE '%ALDOLASA%'
              OR s.nombre LIKE '%LIPASA%'
              OR s.nombre LIKE '%AMILASEMIA%'
              OR s.nombre LIKE '%AMILASURIA%'
              OR s.nombre LIKE '%CALCEMIA%'
              OR s.nombre LIKE '%CALCIO%'
              OR s.nombre LIKE '%CALCIURIA%'
              OR s.nombre LIKE '%FOSFATEMIA%'
              OR s.nombre LIKE '%FOSFATURIA%'
              OR s.nombre LIKE '%FOSFOLIPID%'
              OR s.nombre LIKE '%MAGNESIO%'
              OR s.nombre LIKE '%COBRE%'
              OR s.nombre LIKE '%ZINC%'
              OR s.nombre LIKE '%LITIO%'
              OR s.nombre LIKE '%PLOMO%'
              OR s.nombre LIKE '%CROMO%'
              OR s.nombre LIKE '%SELENIO%'
              OR s.nombre LIKE '%FERREMIA%'
              OR s.nombre LIKE '%FERRITINA%'
              OR s.nombre LIKE '%TRANSFERRINA%'
              OR s.nombre LIKE '%HAPTOGLOBINA%'
              OR s.nombre LIKE '%IONOGRAMA%'
              OR s.nombre LIKE '%ORINA COMPLETA%'
              OR s.nombre LIKE '%PROTEINURIA%'
              OR s.nombre LIKE '%ALBUMINURIA%'
              OR s.nombre LIKE '%MICROALBUMIN%'
              OR s.nombre LIKE '%FENILALANINA%'
              OR s.nombre LIKE '%FOLICO%'
              OR s.nombre LIKE '%VITAMINA%'
              OR s.nombre LIKE '%PSEUDOCOLINESTERASA%'
              OR s.nombre LIKE '%5- NUCLEOTIDASA%'
              OR s.nombre LIKE '%HOMOCISTEINA%'
              OR s.nombre LIKE '%OSTEOCALCINA%'
              OR s.nombre LIKE '%HIDROXIPROLIN%'
              OR s.nombre LIKE '%CITRATURIA%'
              OR s.nombre LIKE '%OXALICO%'
              OR s.nombre LIKE '%METIL HIPURICO%'
              OR s.nombre LIKE '%ACIDO LACTICO%'
              OR s.nombre LIKE '%LACTICO%'
              OR s.nombre LIKE '%BILIARES%'
              OR s.nombre LIKE '%AMONIO%'
              OR s.nombre LIKE '%METANEFRINA%'
              OR s.nombre LIKE '%DOPAMINA%'
              OR s.nombre LIKE '%SEROTONINA%'
              OR s.nombre LIKE '%VAINILLIN%'
              OR s.nombre LIKE '%CARBAMAZEPINA%'
              OR s.nombre LIKE '%VANCOMICINA%'
              OR s.nombre LIKE '%VALPROICO%'
              OR s.nombre LIKE '%MICROGLOBULINA%'
              OR s.nombre LIKE '%ALFA 1 ANTITRIPSINA%'
              OR s.nombre LIKE '%ESTEATOCRITO%'
              OR s.nombre LIKE '%SANGRE OCULTA%'
              OR s.nombre LIKE '%GRAHAM%'
              OR s.nombre LIKE '%HUEVO%'
              OR s.nombre LIKE '%HEMOGLOBINA GLICOSIDASA%'
              OR s.nombre LIKE '%PERFIL%'
              OR s.nombre LIKE '%INDICE DE HOMA%'
              OR s.nombre LIKE '%ACETILCOLINESTERASA%'
              OR s.nombre LIKE '%ADENOSIN%'
              OR s.nombre LIKE '%CRESOL%'
              OR s.nombre LIKE '%HIDROXIPIRENO%'
              OR s.nombre LIKE '%DEOXIPIRIDIN%'
              OR s.nombre LIKE '%BETA CROSS%'
              OR s.nombre LIKE '%CTX-C%'
                THEN 'QC'

            ELSE 'OTR'
        END
    ) AS area_id,
    s.codigo,
    s.nombre,
    '' AS unidad,
    'numerico' AS tipo_resultado,
    2 AS decimales,
    1 AS activo
FROM (
    -- ====================================================
    -- 10 codigos sin numerar en el PDF -> codigo sintetico
    -- ====================================================
    SELECT 'X-ASTO'   AS codigo, 'ANTIESTREPTOLISINAS "O"'           AS nombre UNION ALL
    SELECT 'X-CALP'   , 'CALPROTECTINA'                                       UNION ALL
    SELECT 'X-MAU24'  , 'Microalbuminuria 24 hs'                              UNION ALL
    SELECT 'X-GLU'    , 'glucemia'                                            UNION ALL
    SELECT 'X-CANCA'  , 'cANCA Ac (Citoplasmatico)'                           UNION ALL
    SELECT 'X-DENNS1' , 'Dengue NS 1 Antigeno, FIA'                           UNION ALL
    SELECT 'X-ASPM'   , 'Aspergillus IgM'                                     UNION ALL
    SELECT 'X-COVRAP' , 'Test rapido covid'                                   UNION ALL
    SELECT 'X-ASPG'   , 'Aspergillus IgG'                                     UNION ALL
    SELECT 'X-DENRAP' , 'TEST RAPIDO PARA DENGUE'                             UNION ALL

    -- ====================================================
    -- Codigos del PDF (paginas 1-8)
    -- ====================================================
    SELECT '1-1'     , 'ACTO BIOQUIMICO'                                      UNION ALL
    SELECT '1'       , 'ACTO BIOQUIMICO'                                      UNION ALL
    SELECT '6'       , 'ACTH'                                                 UNION ALL
    SELECT '15'      , 'ALBUMINA'                                             UNION ALL
    SELECT '18'      , 'ALDOLASA'                                             UNION ALL
    SELECT '19'      , 'ALDOSTERONA'                                          UNION ALL
    SELECT '20'      , 'ALFA FETO PROTEINA'                                   UNION ALL
    SELECT '22'      , 'AMILASEMIA'                                           UNION ALL
    SELECT '23'      , 'AMILASURIA'                                           UNION ALL
    SELECT '35'      , 'ANTIBIOGRAMA'                                         UNION ALL
    SELECT '42'      , 'Musculo Liso Ac (ASMA)'                               UNION ALL
    SELECT '44'      , 'ANTICUERPOS ANTIFRACCION MICROSOMAL DE TIROIDES'      UNION ALL
    SELECT '46'      , 'ANTICUERPOS ANTITIROGLOBULINA'                        UNION ALL
    SELECT '49'      , 'ANTIDESIXIRRIBONUCLEASA - ADNEASA - Anti-DNA'         UNION ALL
    SELECT '51'      , 'ASTO'                                                 UNION ALL
    SELECT '55'      , 'Mitocondriales Ac (AMA)'                              UNION ALL
    SELECT '56'      , 'ANTINUCLEARES ANTICUERPOS - FAN'                      UNION ALL
    SELECT '60'      , 'VITAMINA C (Acido Ascorbico)'                         UNION ALL
    SELECT '63'      , 'HIV 4 Generacion (Ag / Ac Combinado)'                 UNION ALL
    SELECT '105'     , 'BACTERIOLOGIA DIRECTA, CULTIVO y ANTIBIOGRAMA'        UNION ALL
    SELECT '110'     , 'BILIRRUBINA TOTAL, DIRECTA e INDIR.'                  UNION ALL
    SELECT '120'     , 'COMPLEMENTO C3'                                       UNION ALL
    SELECT '121'     , 'COMPLEMENTO C4'                                       UNION ALL
    SELECT '133'     , 'CALCEMIA'                                             UNION ALL
    SELECT '133.U'   , 'Calcio urinario, al azar'                             UNION ALL
    SELECT '134'     , 'CALCIO IONICO HASTINGS'                               UNION ALL
    SELECT '136'     , 'CALCIURIA'                                            UNION ALL
    SELECT '136.R'   , 'Calcio urinario: Relacion Calcio / Creatinina urin'   UNION ALL
    SELECT '137'     , 'CALCITONINA PLASMATICA'                               UNION ALL
    SELECT '144'     , 'CEA CARCINOEMBRIOGENICO'                              UNION ALL
    SELECT '169'     , 'COAGULACION Y SANGRIA TIEMPO DE'                      UNION ALL
    SELECT '171'     , 'COAGULOGRAMA'                                         UNION ALL
    SELECT '172'     , 'COBRE SERICO'                                         UNION ALL
    SELECT '174'     , 'COLESTEROL TOTAL'                                     UNION ALL
    SELECT '176'     , 'RECUENTO DE COLONIAS'                                 UNION ALL
    SELECT '179'     , 'COMPLEMENTO ACTIVIDAD TOTAL'                          UNION ALL
    SELECT '184'     , 'COOMBS DIRECTA POLIESPECIFICA, PRUEBA DE'             UNION ALL
    SELECT '186'     , 'COOMBS INDIRECTA'                                     UNION ALL
    SELECT '187'     , 'COPROCULTIVO'                                         UNION ALL
    SELECT '189'     , 'CORTISOL'                                             UNION ALL
    SELECT '190'     , 'CPK'                                                  UNION ALL
    SELECT '191'     , 'CREATININURIA'                                        UNION ALL
    SELECT '192.00'  , 'Creatinina urinaria, al azar'                         UNION ALL
    SELECT '192'     , 'CREATININEMIA'                                        UNION ALL
    SELECT '193'     , 'CLEARENCE DE CREATININA'                              UNION ALL
    SELECT '195'     , 'CRIOGLOBULINAS'                                       UNION ALL
    SELECT '242'     , 'Chagas HAI'                                           UNION ALL
    SELECT '243.1'   , 'Chagas IgG IFI'                                       UNION ALL
    SELECT '243'     , 'Chagas Ac'                                            UNION ALL
    SELECT '262'     , 'DEHIDROEPIANDROSTERONA - DEHID. SULFATO ( DHEA-S )'   UNION ALL
    SELECT '293'     , 'GRAVINDEX'                                            UNION ALL
    SELECT '295'     , 'EOSINOFILOS RECUENTO (NASAL)'                         UNION ALL
    SELECT '297'     , 'ERITROSEDIMENTACION'                                  UNION ALL
    SELECT '300'     , 'ESTRADIOL PLASMATICO'                                 UNION ALL
    SELECT '305'     , 'ESTRONA PLASMATICA'                                   UNION ALL
    SELECT '309'     , 'HISOPADO FARINGEO'                                    UNION ALL
    SELECT '333'     , 'FACTOR DE COAGULACION VIII'                           UNION ALL
    SELECT '337'     , 'FENILALANINA'                                         UNION ALL
    SELECT '338'     , 'FENILALANINA (NEO)'                                   UNION ALL
    SELECT '343'     , 'FERREMIA'                                             UNION ALL
    SELECT '344'     , 'FIBRINA PRODUCTOS DE DEGRADACION -PDF-'               UNION ALL
    SELECT '345'     , 'FIBRINOGENO EN SANGRE'                                UNION ALL
    SELECT '352'     , 'FOLICO, ACIDO'                                        UNION ALL
    SELECT '355'     , 'FOSFATASA ACIDA PROSTATICA - EFM'                     UNION ALL
    SELECT '356'     , 'FOSFATASA ACIDA TOTAL - EFM'                          UNION ALL
    SELECT '357'     , 'FOSFATASA ALCALINA'                                   UNION ALL
    SELECT '362'     , 'FOSFATEMIA'                                           UNION ALL
    SELECT '362-U'   , 'Fosforo urinario, al azar'                            UNION ALL
    SELECT '363'     , 'FOSFATURIA'                                           UNION ALL
    SELECT '365'     , 'FOSFOLIPIDOS ( * )'                                   UNION ALL
    SELECT '370'     , 'FSH'                                                  UNION ALL
    SELECT '371'     , 'FTA/ ABS'                                             UNION ALL
    SELECT '412'     , 'GLUCEMIA'                                             UNION ALL
    SELECT '413'     , 'GLUCOSA POST 120 MIN'                                 UNION ALL
    SELECT '420'     , 'GAMMA-GT'                                             UNION ALL
    SELECT '430'     , 'GRAHAM TEST'                                          UNION ALL
    SELECT '433'     , 'GRUPO SANGUINEO y FACTOR'                             UNION ALL
    SELECT '463'     , 'HAPTOGLOBINA'                                         UNION ALL
    SELECT '466'     , 'Hematocrito'                                          UNION ALL
    SELECT '470'     , 'Hemoglobina dosaje'                                   UNION ALL
    SELECT '471'     , 'HEMOGLOBINA ELECTROFORESIS'                           UNION ALL
    SELECT '475'     , 'HEMOGRAMA'                                            UNION ALL
    SELECT '481'     , 'HEPATOGRAMA'                                          UNION ALL
    SELECT '483'     , 'Hidatidosis HAI'                                      UNION ALL
    SELECT '488'     , 'HIDROXIPROLINURIA'                                    UNION ALL
    SELECT '494'     , 'HUDDLESSON REACCION DE - (BRUCELOSIS)'                UNION ALL
    SELECT '537'     , 'INMUNOGLOBULINA A'                                    UNION ALL
    SELECT '539'     , 'INMUNOGLOBULINA E'                                    UNION ALL
    SELECT '539-ACARO', 'IGE especifica para acaros: Derm. Farinae, 3 Ge'    UNION ALL
    SELECT '539-MOHOS', 'IGE especifica para panel Mohos, 3 Generaci'        UNION ALL
    SELECT '539-PASTO', 'IgE especifica para mix de pastos'                  UNION ALL
    SELECT '539-ARBOL', 'IgE especifico para mix de arboles'                 UNION ALL
    SELECT '540'     , 'INMUNOGLOBULINA G'                                    UNION ALL
    SELECT '541'     , 'INMUNOGLOBULINA M'                                    UNION ALL
    SELECT '543'     , 'INSULINA'                                             UNION ALL
    SELECT '543-1'   , 'INSULINA POST 120min'                                 UNION ALL
    SELECT '546'     , 'IONOGRAMA PLASMATICO'                                 UNION ALL
    SELECT '547'     , 'IONOGRAMA URINARIO'                                   UNION ALL
    SELECT '594'     , 'LDH'                                                  UNION ALL
    SELECT '598'     , 'ARTRITEST'                                            UNION ALL
    SELECT '612'     , 'LH- HORMONA LUTEINIZANTE'                             UNION ALL
    SELECT '613'     , 'LIPASA EN SANGRE'                                     UNION ALL
    SELECT '617'     , 'PERFIL LIPIDICO (COL,HDL,LDL,TGC)'                    UNION ALL
    SELECT '623'     , 'LITIO (serico)'                                       UNION ALL
    SELECT '653'     , 'MAGNESIO DE SANGRE'                                   UNION ALL
    SELECT '654'     , 'MAGNESIO EN ORINA'                                    UNION ALL
    SELECT '656'     , 'MANTOUX INTRADERMO- REACCION de (PPD)'                UNION ALL
    SELECT '664'     , 'MICOLOGIA DIRECTO'                                    UNION ALL
    SELECT '665'     , 'MICOLOGIA (Cultivo e Identificacion)'                 UNION ALL
    SELECT '669'     , 'Epstein Barr Ac Heterofilos (Monotest)'               UNION ALL
    SELECT '670'     , 'MONONUCLEOSIS HEMOAGLUTINACION (P. B.)'               UNION ALL
    SELECT '702'     , '5- NUCLEOTIDASA'                                      UNION ALL
    SELECT '711'     , 'ORINA COMPLETA'                                       UNION ALL
    SELECT '736'     , 'PERFIL PARASITOLOGICO'                                UNION ALL
    SELECT '746'     , 'PLAQUETAS, RECUENTO DE'                               UNION ALL
    SELECT '749'     , 'Plomo en sangre'                                      UNION ALL
    SELECT '758'     , 'PROGESTERONA'                                         UNION ALL
    SELECT '759'     , 'PROLACTINA'                                           UNION ALL
    SELECT '761'     , 'PCR'                                                  UNION ALL
    SELECT '763'     , 'PROTEINAS TOTALES'                                    UNION ALL
    SELECT '764'     , 'PROTEINOGRAMA'                                        UNION ALL
    SELECT '767-1'   , 'PROTEINURIA (muestra aislada)'                        UNION ALL
    SELECT '767'     , 'PROTEINURIA'                                          UNION ALL
    SELECT '771'     , 'PROTROMBINA TIEMPO DE'                                UNION ALL
    SELECT '771-1'   , 'RIN'                                                  UNION ALL
    SELECT '772'     , 'PSEUDOCOLINESTERASA'                                  UNION ALL
    SELECT '818'     , 'RETICULOCITOS RECUENTO DE'                            UNION ALL
    SELECT '833'     , 'SANGRE OCULTA EN MATERIA FECAL'                       UNION ALL
    SELECT '835'     , 'SEROTONINA SERICA'                                    UNION ALL
    SELECT '841'     , 'SOMATOTROFINA'                                        UNION ALL
    SELECT '863'     , 'TESTOSTERONA'                                         UNION ALL
    SELECT '865'     , 'TSH TIROTROFINA'                                      UNION ALL
    SELECT '866'     , 'T4 TIROXINA TOTAL'                                    UNION ALL
    SELECT '867'     , 'T 4 LIBRE TIROXINA EFECTIVA'                          UNION ALL
    SELECT '870'     , 'TOXOPLASMOSIS, HEMOAGLUTINACION'                      UNION ALL
    SELECT '871'     , 'TOXOPLASMOSIS, IFI'                                   UNION ALL
    SELECT '873'     , 'TRANSAMINASA (GOT)'                                   UNION ALL
    SELECT '874'     , 'TRANSAMINASA (GPT)'                                   UNION ALL
    SELECT '875'     , 'TRANSFERRINA'                                         UNION ALL
    SELECT '876'     , 'TRIGLICERIDOS'                                        UNION ALL
    SELECT '878'     , 'TRIIODOTIRONINA TOTAL T3'                             UNION ALL
    SELECT '887'     , 'TROMBOPLASTINA, TIEMPO DE (KPTT)'                     UNION ALL
    SELECT '902'     , 'UREMIA'                                               UNION ALL
    SELECT '904'     , 'URICEMIA'                                             UNION ALL
    SELECT '905'     , 'URICO ACIDO EN ORINA (URICOSURIA)'                    UNION ALL
    SELECT '931'     , 'EXUDADO VAGINAL (BACOVA)'                             UNION ALL
    SELECT '932'     , 'VAINILLIN MANDELICO, ACIDO EN ORINA'                  UNION ALL
    SELECT '933'     , 'V D R L CUALITATIVA ( * )'                            UNION ALL
    SELECT '934'     , 'V D R L CUANTITATIVA - USR CUANTITATIVA'              UNION ALL
    SELECT '937'     , 'VITAMINA A -Beta Carotenos'                           UNION ALL
    SELECT '938'     , 'VITAMINA B12'                                         UNION ALL
    SELECT '939'     , 'VITAMINA E'                                           UNION ALL
    SELECT '982'     , 'ZINC SERICO'                                          UNION ALL
    SELECT '1000.L'  , 'Antigeno Prostatico Especifico Libre (PSA L)'         UNION ALL
    SELECT '1000'    , 'AG. PROSTATICO ESPECIFICO (PSA)'                      UNION ALL
    SELECT '1025'    , 'CITOMEGALOVIRUS, Ac. Anti- IgG'                       UNION ALL
    SELECT '1030'    , 'CITOMEGALOVIRUS, Ac. Anti- IgM'                       UNION ALL
    SELECT '1035'    , 'COLESTEROL HDL'                                       UNION ALL
    SELECT '1040'    , 'COLESTEROL LDL'                                       UNION ALL
    SELECT '1045'    , 'CPK- MB'                                              UNION ALL
    SELECT '1055'    , 'Epstein Barr VCA IgG'                                 UNION ALL
    SELECT '1060'    , 'EPSTEIN BARR, Ac. IgM Anti- (VEB / VCA IgM)'          UNION ALL
    SELECT '1070'    , 'HEMOGLOBINA GLICOSIDASA'                              UNION ALL
    SELECT '1075'    , 'HEPATITIS A, Ac. Anti- IgM (HAV IgM)'                 UNION ALL
    SELECT '1080'    , 'Hepatitis B HBc Ac'                                   UNION ALL
    SELECT '1085'    , 'HEPATITIS B, Antigeno e (Ag.Hbe)'                     UNION ALL
    SELECT '1086'    , 'HEPATITIS B, Antig de sup. (Ag.HBs)'                  UNION ALL
    SELECT '1090'    , 'HEPATITIS B, Ac. Anti- (HBsAc)'                       UNION ALL
    SELECT '1095'    , 'Hepatitis C (HCV) Ac'                                 UNION ALL
    SELECT '1105'    , 'HIV CARGA VIRAL'                                      UNION ALL
    SELECT '1110'    , 'HIV WESTERN- BLOT'                                    UNION ALL
    SELECT '1115'    , 'MARCADOR CA 125'                                      UNION ALL
    SELECT '1120'    , 'MARCADOR CA 15. 3'                                    UNION ALL
    SELECT '1125'    , 'MARCADOR CA 19. 9'                                    UNION ALL
    SELECT '1130'    , 'ALBUMINURIA'                                          UNION ALL
    SELECT '1135'    , 'ACIDO VALPROICO'                                      UNION ALL
    SELECT '1145'    , 'RUBEOLA, Ac. Anti- IgG'                               UNION ALL
    SELECT '1150'    , 'RUBEOLA, Ac. Anti- IgM'                               UNION ALL
    SELECT '1170'    , 'SUBUNIDAD BETA HCG'                                   UNION ALL
    SELECT '1175-1'  , 'Gonadotrofina Corionica Beta (hCG), serica'           UNION ALL
    SELECT '1175'    , 'SUBUNIDAD BETA (sangre)'                              UNION ALL
    SELECT '1185'    , 'TESTOSTERONA BIODISPONIBLE'                           UNION ALL
    SELECT '1196'    , 'SCREENING NEONATAL x 6'                               UNION ALL
    SELECT '2001'    , 'ABC - ACTO BIOQUIMICO COMPLEMENTARIO'                 UNION ALL
    SELECT '2025'    , 'ACETILCOLINA, Ac. Anti- RECEPTORES (ACRA)'            UNION ALL
    SELECT '2034'    , 'ACETILCOLINESTERASA ERITROCITARIA'                    UNION ALL
    SELECT '2230'    , 'ACIDO LACTICO, LCR'                                   UNION ALL
    SELECT '2264'    , 'ACIDO METIL HIPURICO'                                 UNION ALL
    SELECT '2299'    , 'ACIDO OXALICO, Urinario (2/ 12 / 24 hs)'              UNION ALL
    SELECT '2367'    , 'ACIDOS BILIARES'                                      UNION ALL
    SELECT '2418'    , 'ADENOSIN DEAMINASA, Liquido Pleural'                  UNION ALL
    SELECT '2504'    , 'ALFA 1 ANTITRIPSINA, Serica'                          UNION ALL
    SELECT '2649'    , 'AMONIO'                                               UNION ALL
    SELECT '2675'    , 'ANDROSTENODIONA'                                      UNION ALL
    SELECT '2709'    , 'ANTICOAGULANTE LUPICO'                                UNION ALL
    SELECT '2734'    , 'AG. PROSTATICO ESPECIFICO (total-libre)'              UNION ALL
    SELECT '2790'    , 'Hormona anti Mulleriana'                              UNION ALL
    SELECT '2810'    , 'Apolipoproteina A'                                    UNION ALL
    SELECT '2811'    , 'Apolipoproteina B'                                    UNION ALL
    SELECT '2846'    , 'ASPERGILLIUS, Ac. Anti-'                              UNION ALL
    SELECT '2982'    , 'BETA 2 GLICOPROTEINA, Ac. Anti- IgG'                  UNION ALL
    SELECT '3025'    , 'BETA CROSS LAPS - CTX-C'                              UNION ALL
    SELECT '3093'    , 'NT - Pro BNP'                                         UNION ALL
    SELECT '3179'    , 'BRUCELOSIS, Ac. ANTI- IgG o Totales'                  UNION ALL
    SELECT '3187'    , 'BRUCELOSIS, Ac. ANTI- IgM'                            UNION ALL
    SELECT '3230'    , 'Complemento C1 inhibidor antigenico'                  UNION ALL
    SELECT '3247'    , 'CA 21-1 CYFRA (Marcador Tumoral de Pulmon)'           UNION ALL
    SELECT '3324'    , 'CANDIDA ALBICANS, Ac. Totales'                        UNION ALL
    SELECT '3367'    , 'CARBAMAZEPINA, EPOXIDO DE'                            UNION ALL
    SELECT '3392'    , 'CARDIOLIPINAS, Ac. Anti- IgG'                         UNION ALL
    SELECT '3401'    , 'CARDIOLIPINAS, Ac. Anti- IgM'                         UNION ALL
    SELECT '3546'    , 'CELULAS LE (*)'                                       UNION ALL
    SELECT '3563'    , 'CENTROMERO, Ac. Anti-'                                UNION ALL
    SELECT '3572'    , 'CHAGAS, Ac. Anti- IgM (IFI)'                          UNION ALL
    SELECT '3623'    , 'Chlamydia trachomatis IgG'                            UNION ALL
    SELECT '3632'    , 'CHLAMYDIA TRACHOMATIS, Ac. Anti- IgM'                 UNION ALL
    SELECT '3640'    , 'CHLAMYDIA TRACHOMATIS, Ag.'                           UNION ALL
    SELECT '3649'    , 'Chlamydia trachomatis RT- PCR'                        UNION ALL
    SELECT '3734'    , 'ANCA Ac (Ac anti citoplasma de neutrofilo'            UNION ALL
    SELECT '3743'    , 'CITRATURIA'                                           UNION ALL
    SELECT '3760'    , 'CLOSTRIDIUM DIFFICILE, Toxinas (A + B) - Materia F'   UNION ALL
    SELECT '3854'    , 'COCCIDIOIDES INMITIS, Ac. CIE'                        UNION ALL
    SELECT '3922'    , 'Complemento C1q'                                      UNION ALL
    SELECT '4008'    , 'CORTISOL LIBRE, Urinaria (CLU)'                       UNION ALL
    SELECT '4012'    , 'CORTISOL SALIVAL'                                     UNION ALL
    SELECT '4012.23' , 'CORTISOL SALIVAL 23:00hs'                             UNION ALL
    SELECT '4136'    , 'CROMO, Sangre u Orina'                                UNION ALL
    SELECT '4264'    , 'CRYPTOCOCCUS NEOFORMANS, Ag.'                         UNION ALL
    SELECT '4273'    , 'CRYPTOCOCCUS NEOFORMANS, (Tinta china)'               UNION ALL
    SELECT '4363'    , 'Dengue IgM cualitativo, FIA'                          UNION ALL
    SELECT '4365'    , 'IgG ANTI-VIRUS DENGUE'                                UNION ALL
    SELECT '4375'    , 'Deoxipiridinolina'                                    UNION ALL
    SELECT '4418'    , 'DIMERO-D'                                             UNION ALL
    SELECT '4469'    , 'DNA Ac Anti -'                                        UNION ALL
    SELECT '4512'    , 'DOPAMINA PLASMATICA'                                  UNION ALL
    SELECT '4623'    , 'ENA Ac (4 Ag: Ro, La, Sm, RNP)'                       UNION ALL
    SELECT '4632'    , 'ENDOMISIO, Ac. Anti- IgA'                             UNION ALL
    SELECT '4640'    , 'ENDOMISIO, Ac. Anti- IgG'                             UNION ALL
    SELECT '4734'    , 'ERITROPOYETINA (EPO)'                                 UNION ALL
    SELECT '4755'    , 'HEMOGRAMA (4755)'                                     UNION ALL
    SELECT '4999'    , 'ESTEATOCRITO'                                         UNION ALL
    SELECT '5093'    , 'FACTOR REUMATOIDEO'                                   UNION ALL
    SELECT '5110'    , 'FACTOR VON WILLEBRAND, Funcional'                     UNION ALL
    SELECT '5119'    , 'FACTOR VON WILLEBRAND, Inmunologico'                  UNION ALL
    SELECT '5230'    , 'FERRITINA'                                            UNION ALL
    SELECT '5349'    , 'FOSFATASA ALCALINA -OSEA'                             UNION ALL
    SELECT '5452'    , 'FOSFOLIPIDOS, Ac. Anti- IgG'                          UNION ALL
    SELECT '5461'    , 'FOSFOLIPIDOS, Ac. Anti- IgM'                          UNION ALL
    SELECT '5478'    , 'FRUCTOSAMINA'                                         UNION ALL
    SELECT '5503'    , 'GAD, Ac. Anti- Glutamico Acid Decarboxilase'          UNION ALL
    SELECT '5572'    , 'GLIADINA DIAMINADA IgA'                               UNION ALL
    SELECT '5580'    , 'GLIADINA DIAMINADA IgG'                               UNION ALL
    SELECT '5632'    , 'GLAE (SHBG) Globulina Ligadora'                       UNION ALL
    SELECT '5649'    , 'GLOMERULO, Ac. Ant-'                                  UNION ALL
    SELECT '5743'    , 'Helicobacter pylori IgA, ELISA'                       UNION ALL
    SELECT '5751'    , 'Helicobacter pylori IgG'                              UNION ALL
    SELECT '5751.AG' , 'Helicobacter pylori Antigeno'                         UNION ALL
    SELECT '5760'    , 'Helicobacter Pylori IgM'                              UNION ALL
    SELECT '5888'    , 'Hepatitis A (HAV) Ac Totales'                         UNION ALL
    SELECT '5905'    , 'Hepatitis B HBc IgM'                                  UNION ALL
    SELECT '5965'    , 'HEPATITIS C, Cenotipificacion - PCR'                  UNION ALL
    SELECT '5990'    , 'HEPATITIS DELTA, Ac. Anti- IgG o Totales'             UNION ALL
    SELECT '6040'    , 'HERPES SIMPLEX 1, Ac. IgA Anti-'                      UNION ALL
    SELECT '6042'    , 'Herpes Simplex 1 IgG, ELISA'                          UNION ALL
    SELECT '6050'    , 'HERPES SIMPLEX 1, Ac. IgM Anti-'                      UNION ALL
    SELECT '6059'    , 'HERPES SIMPLEX 2, Ac. IgA Anti-'                      UNION ALL
    SELECT '6067'    , 'Herpes Simplex 2 IgG, ELISA'                          UNION ALL
    SELECT '6076'    , 'HERPES SIMPLEX 2, Ac. Anti- IgM'                      UNION ALL
    SELECT '6161'    , 'HIDATIDOSIS, Ac. Anti- IgG o Totales'                 UNION ALL
    SELECT '6204'    , 'HIDROXIPIRENO'                                        UNION ALL
    SELECT '6238'    , 'HISTONA, Ac. Anti-'                                   UNION ALL
    SELECT '6247'    , 'HISTOPLASMA CAPSULATUM, Ac. Anti- IgG'                UNION ALL
    SELECT '6332.PCR', 'HLA B 27, PCR'                                        UNION ALL
    SELECT '6452'    , 'HOMOCISTEINA'                                         UNION ALL
    SELECT '6469'    , 'HORMONA ANTIDIURETICA, HAD (VASOPRESINA)'             UNION ALL
    SELECT '6495'    , 'HPV, GENOTIPIFICACION por PCR'                        UNION ALL
    SELECT '6589'    , 'Inmunoglobulina A secretora'                          UNION ALL
    SELECT '6606.YEMA','IgE Especifica (RAST), Yema de Huevo, 3 Generacion'  UNION ALL
    SELECT '6606ABE' , 'IgE (RAST), Veneno de Abeja'                          UNION ALL
    SELECT '6606.1'  , 'IgE ESPECIFICA DE PESCADO'                            UNION ALL
    SELECT '6606.PROT','IgE Especifica (RAST), Carne de Vac'                  UNION ALL
    SELECT '6606.PENI','IgE (RAST), Penicilina V (Oral), 3 Gene'              UNION ALL
    SELECT '6606.CACA','Inmunoglobulina E Especifica Cacao, 3 Gen'            UNION ALL
    SELECT '6606.LECH','IgE Especifica (RAST) Leche de Vaca, 3 Generacion'    UNION ALL
    SELECT '6606.TRIG','IgE Especifica (RAST), Trigo, 3 Gene.'                UNION ALL
    SELECT '6606.TOMA','IgE Especifica (RAST), Tomate, 3 Gen.'                UNION ALL
    SELECT '6606.AMOX','IgE Especifica (RAST), Amoxicilina, 3 Generacion'     UNION ALL
    SELECT '6606AVI' , 'Inmunoglobulina E Especifica 3 Generacion, Avispa'    UNION ALL
    SELECT '6606.HUE', 'IgE Especifica (RAST), Huevo, 3 Gene.'                UNION ALL
    SELECT '6606.CLAR','IgE Especifica (RAST), Clara de Huevo, 3 Generacion'  UNION ALL
    SELECT '6606.CLAV','IgE Especifica (RAST), Clavulanico'                   UNION ALL
    SELECT '6606.GATO','IgE.(RAST), Epitelio de Gato'                         UNION ALL
    SELECT '6708'    , 'INDICE DE HOMA'                                       UNION ALL
    SELECT '6714'    , 'Relacion Proteina / Creatinina urina'                 UNION ALL
    SELECT '6725'    , 'INFLUENZA A, ANTIGENO (Ag.)'                          UNION ALL
    SELECT '6788'    , 'INHIBINA B'                                           UNION ALL
    SELECT '6922'    , 'JO-1, Ac. Anti-'                                      UNION ALL
    SELECT '6930'    , 'La / SS-B Ac'                                         UNION ALL
    SELECT '6999'    , 'LEPTOSPIRA, Ac. Anti-'                                UNION ALL
    SELECT '7007'    , 'LEPTOSPIRA, Ac. Anti- IgM'                            UNION ALL
    SELECT '7272'    , 'LKM, Ac. Anti-'                                       UNION ALL
    SELECT '7366'    , 'MEMBRANA BASAL, Ac. Anti-'                            UNION ALL
    SELECT '7409'    , 'METAHEMOGLOBINA'                                      UNION ALL
    SELECT '7418'    , 'METANEFRINA'                                          UNION ALL
    SELECT '7503'    , 'MICROGLOBULINA BETA 2'                                UNION ALL
    SELECT '7700'    , 'MYCOPLASMA - UREAPLASMA, Cultivo'                     UNION ALL
    SELECT '7751'    , '17-OH-HIDROXIPROGESTERONA NEONATAL'                   UNION ALL
    SELECT '7759'    , 'BIOTINIDASA NEONATAL'                                 UNION ALL
    SELECT '7768'    , 'GALACTOSEMIA NEONATAL'                                UNION ALL
    SELECT '7777'    , 'TRIPSINA NEONATAL'                                    UNION ALL
    SELECT '7785'    , 'T.S.H. NEONATAL'                                      UNION ALL
    SELECT '7905'    , 'ORTO CRESOL (2-metilfenol)'                           UNION ALL
    SELECT '7939'    , 'OSTEOCALCINA'                                         UNION ALL
    SELECT '8127'    , 'PARACOCCIDIOIDES, Ac. Anti-'                          UNION ALL
    SELECT '8161'    , 'PARATHORMONA - PTH'                                   UNION ALL
    SELECT '8187'    , 'PAROTIDITIS, Ac. IgM'                                 UNION ALL
    SELECT '8238'    , 'PARVOVIRUS, Ac. Anti- IgM'                            UNION ALL
    SELECT '8281'    , 'PEPTIDO C'                                            UNION ALL
    SELECT '8284'    , 'PEPTIDO CITRULINADO CICLICO'                          UNION ALL
    SELECT '8315'    , 'PEROXIDASA TIROIDEO, Ac. ANTI- (ATPPO)'               UNION ALL
    SELECT '8452'    , 'POLIMORFONUCLEARES'                                   UNION ALL
    SELECT '8563'    , 'PROCALCITONINA'                                       UNION ALL
    SELECT '8580'    , 'PROGESTERONA 17-HIDROXI'                              UNION ALL
    SELECT '8623'    , 'PROTEINA C REACTIVA - ULTRASENSIBLE'                  UNION ALL
    SELECT '8802'    , 'RECEPTOR TSH, Ac. Anti-'                              UNION ALL
    SELECT '8816'    , 'Receptor Soluble de Transferrina'                     UNION ALL
    SELECT '8819'    , 'RENINA ACTIVADA'                                      UNION ALL
    SELECT '8836'    , 'RETICULINA, Ac. Anti- (ARA)'                          UNION ALL
    SELECT '8896'    , 'RNP, Ac. Anti- (RIBONUCLEOPROT)'                      UNION ALL
    SELECT '8905'    , 'Ro / SS-A Ac'                                         UNION ALL
    SELECT '8956'    , 'SACCHAROMYSES, Ac. Anti-'                             UNION ALL
    SELECT '8973'    , 'SANGRE OCULTA en MATERIA FECAL ESPECIFICO (S.O.M.F)'  UNION ALL
    SELECT '8982'    , 'SARAMPION, Ac. Anti- IgG'                             UNION ALL
    SELECT '8999'    , 'SCL 70, Ac. Anti-'                                    UNION ALL
    SELECT '9024'    , 'SELENIO'                                              UNION ALL
    SELECT '9110'    , 'SM, Ac. Anti-'                                        UNION ALL
    SELECT '9118'    , 'SOMATOMEDINA C- IGFB1'                                UNION ALL
    SELECT '9127'    , 'Streptococcus grupo B (exud.vaginal)'                 UNION ALL
    SELECT '9375'    , 'TESTOSTERONA LIBRE, To-L'                             UNION ALL
    SELECT '9443'    , 'TIROGLOBULINA'                                        UNION ALL
    SELECT '9460'    , 'TIROGLOBULINA, Ac. ULTRA SENS.'                       UNION ALL
    SELECT '9571'    , 'Toxoplasmosis Quimioluminiscencia IgG'                UNION ALL
    SELECT '9575'    , 'TOCOPLASMOSIS, AC IGG ANTI (TEST DE AVIDEZ)'          UNION ALL
    SELECT '9580'    , 'TOXOPLASMOSIS, Ac. Anti- IgM'                         UNION ALL
    SELECT '9622'    , 'Transglutaminasa, Ac. Anti- IgA'                      UNION ALL
    SELECT '9631'    , 'Transglutaminasa, Ac. Anti- IgG'                      UNION ALL
    SELECT '9644'    , 'Treponema pallidum Ac'                                UNION ALL
    SELECT '9661'    , 'TRIIODOTIRONINA Libre - T3 Libre'                     UNION ALL
    SELECT '9725'    , 'TROPONINA I'                                          UNION ALL
    SELECT '9734'    , 'TROPONINA T'                                          UNION ALL
    SELECT '9793'    , 'VANCOMICINA'                                          UNION ALL
    SELECT '9819'    , 'VARICELA ZOSTER, Ac. Anti- IgG'                       UNION ALL
    SELECT '9828'    , 'VARICELA ZOSTER, Ac. Anti- IgM'                       UNION ALL
    SELECT '9879'    , 'VITAMINA B 1 (TIAMINA)'                               UNION ALL
    SELECT '9887'    , 'VITAMINA B6 (PIRIDOXINA)'                             UNION ALL
    SELECT '9913'    , 'VITAMINA D3 (25-HIDROXICALCIFEROL)'                   UNION ALL
    SELECT '9918'    , 'VLDL-COLESTEROL'                                      UNION ALL
    SELECT '11111'   , 'CONTROL EMBARAZO'
) AS s;

COMMIT;

-- ============================================================================
-- Verificacion (correr a mano):
--   SELECT a.codigo AS area, COUNT(*) FROM lab_determinaciones d
--     JOIN lab_areas a ON a.id = d.area_id
--    GROUP BY a.codigo ORDER BY 2 DESC;
--
-- Si "OTR" tiene muchas filas, revisar las heuristicas en el CASE.
-- ============================================================================
