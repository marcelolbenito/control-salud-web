-- 027: Acto bioquimico "solo facturacion" + NBU a nivel perfil.
--
-- #1 Acto bioquimico: se cobra en todo pedido pero no figura como resultado ni
--    en el informe PDF. Se marca con la bandera solo_facturacion.
-- #4 Perfil facturado como una linea: el perfil puede tener su propio NBU. Si lo
--    tiene, se factura una sola linea con ese NBU (no se desglosan los
--    componentes). Si queda NULL, se factura como hasta ahora (por componente).

ALTER TABLE lab_determinaciones
    ADD COLUMN solo_facturacion TINYINT(1) NOT NULL DEFAULT 0 AFTER activo;

ALTER TABLE lab_perfiles
    ADD COLUMN nbu_unidades DECIMAL(8,2) NULL AFTER descripcion;

-- Marcar el acto bioquimico (codigo '1', NBU 6) como solo facturacion.
UPDATE lab_determinaciones SET solo_facturacion = 1 WHERE codigo = '1' AND nombre = 'ACTO BIOQUIMICO';

-- Hemograma completo se factura como una sola linea con NBU 5.
UPDATE lab_perfiles SET nbu_unidades = 5.00 WHERE codigo = 'HMG';
