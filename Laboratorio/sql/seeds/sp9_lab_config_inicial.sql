-- ============================================================================
-- sp9_lab_config_inicial.sql
-- ============================================================================
-- Carga inicial de datos institucionales del Centro Privado Salud.
-- IDEMPOTENTE: usa INSERT ... ON DUPLICATE KEY UPDATE.
--
-- IMPORTANTE - Encoding: este archivo contiene caracteres UTF-8 (Resolución
-- Nº con tilde y ordinal). En Windows, mysql.exe por default decodifica el
-- input segun la codepage del cmd (CP437), lo que corrompe estos bytes y
-- guarda mojibake en BD.
--
-- Aplicar SIEMPRE con --default-character-set=utf8mb4:
--   mysql.exe -u root --default-character-set=utf8mb4 clinica < sp9_lab_config_inicial.sql
--
-- El SET NAMES debajo es defensa en profundidad pero NO compensa si el
-- cliente ya entrego bytes en otra codepage.
--
-- Pendiente: archivos reales de logo y firma. Logo del Centro Privado Salud
-- esta en storage/logos/centro_privado_salud.png. La firma escaneada de
-- Brizuela Laura aun no fue entregada.
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO lab_config (clave, valor, descripcion) VALUES
    ('laboratorio_nombre', 'CENTRO PRIVADO SALUD', 'Nombre principal en encabezado de PDF'),
    ('laboratorio_subtitulo', 'LABORATORIO DE ANALISIS CLINICOS', 'Subtitulo bajo el nombre principal'),
    ('laboratorio_direccion', '12 de octubre 158 - Unquillo - Cordoba', 'Direccion en pie de PDF'),
    ('laboratorio_telefono', 'Whatsapp - 351-2419001', 'Telefono / WhatsApp en pie de PDF'),
    ('laboratorio_logo_path', 'logos/centro_privado_salud.png', 'Path relativo a storage/'),
    ('laboratorio_resolucion_colegio', 'Resolución Nº A 16463/2021', 'Resolucion del Colegio de Bioquimicos'),
    ('laboratorio_resolucion_vencimiento', '09/06/24', 'Vencimiento de la autorizacion'),
    ('laboratorio_registro_sisa_codigo', '51140212336170', 'Codigo de inscripcion REFE/SISA'),
    ('laboratorio_registro_sisa_razon_social', 'CENTRO PRIVADO SALUD SRL Laboratorios inscripto en REFE', 'Razon social registrada en SISA'),
    ('tecnico_principal_nombre', 'Carrizo Lucas', 'Nombre del tecnico de laboratorio en header'),
    ('tecnico_principal_titulo', 'Tecnico en Laboratorio', 'Titulo profesional del tecnico')
ON DUPLICATE KEY UPDATE valor = VALUES(valor), descripcion = VALUES(descripcion);

-- Bioquimica titular: Brizuela Laura. Si ya existe (seed dev_bioquimicos.sql),
-- actualiza la marca. Si no, crea la fila.
INSERT INTO lab_bioquimicos
    (apellido, nombres, matricula, titulo, especialidad, telefono, email,
     firma_path, usuario_id, activo, es_responsable_principal)
VALUES
    ('Brizuela', 'Laura', '3529', 'Bioquimica', NULL, NULL, NULL,
     'firmas/brizuela_laura.png', NULL, 1, 1)
ON DUPLICATE KEY UPDATE
    es_responsable_principal = 1,
    firma_path = COALESCE(firma_path, VALUES(firma_path)),
    titulo = COALESCE(titulo, VALUES(titulo));

-- Si hubiera otros bioquimicos marcados como principales, desmarcarlos.
UPDATE lab_bioquimicos
   SET es_responsable_principal = 0
 WHERE matricula <> '3529' AND es_responsable_principal = 1;
