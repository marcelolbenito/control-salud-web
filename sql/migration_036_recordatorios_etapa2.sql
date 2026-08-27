-- Recordatorios etapa 2: datos de clínica en plantillas y aviso al anular turno
SET NAMES utf8mb4;

INSERT INTO config (id_clinica, clave, valor)
SELECT 1, 'recordatorios.nombre_clinica', 'CENTRO PRIVADO SALUD'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM config WHERE clave = 'recordatorios.nombre_clinica' AND id_clinica = 1);

INSERT INTO config (id_clinica, clave, valor)
SELECT 1, 'recordatorios.direccion_clinica', '12 de Octubre 158 - Unquillo'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM config WHERE clave = 'recordatorios.direccion_clinica' AND id_clinica = 1);

INSERT INTO config (id_clinica, clave, valor)
SELECT 1, 'recordatorios.aviso_anulacion', '1'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM config WHERE clave = 'recordatorios.aviso_anulacion' AND id_clinica = 1);
