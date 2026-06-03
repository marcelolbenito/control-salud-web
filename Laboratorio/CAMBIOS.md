# CAMBIOS

Registro de cambios funcionales hechos sobre el modulo `laboratorio` para
que el dev del sistema clinico principal este al tanto. Orden cronologico
descendente (lo mas nuevo arriba).

Si algun cambio toca tablas / endpoints / sesion compartidos, esta marcado
con **(IMPACTA INTEGRACION)**.

---

## 2026-06-01 — Sesion 4: autoservicio de edicion

Objetivo: que el cliente pueda editar solo (sin pedirle al dev ni tocar SQL)
cosas que antes estaban hardcodeadas o solo se cargaban por base.

### 1. Acto bioquimico configurable desde el nomenclador *(hecho)*

- **Antes:** la determinacion "solo facturacion" (acto bioquimico, se cobra en
  todo pedido pero no se informa) usaba la columna `lab_determinaciones.solo_facturacion`,
  pero el editor del nomenclador no la exponia → solo se cambiaba por SQL.
- **Ahora:** el editor del nomenclador tiene un checkbox *"Acto bioquimico /
  solo facturacion"*. El cliente marca/desmarca que determinacion se auto-agrega
  a los pedidos y le pone el NBU, todo desde la UI. El motor de pedidos ya era
  dinamico (`DeterminacionRepository::findSoloFacturacionIds`), no cambio.
- **Archivos:** `src/Repositories/DeterminacionRepository.php` (SELECT/INSERT/UPDATE
  con `solo_facturacion`), `api/determinaciones.php`,
  `public/views/nomenclador/index.php`, `public/assets/js/nomenclador/index.js`.
- **DB:** sin cambios (la columna ya existia).

### 2. ABM de valores de referencia (rangos) *(hecho)* **(IMPACTA INTEGRACION)**

- **Antes:** los rangos normales (los que marcan resultado anormal) solo se
  cargaban por SQL en `lab_valores_referencia`. No habia UI ni API.
- **Ahora:** ABM completo integrado al nomenclador. Cada fila tiene un boton
  **"Rangos"** que abre un modal para crear/editar/borrar rangos por sexo y
  edad (la UI toma años/meses y los convierte a dias). Borrado por soft delete.
- **Nuevo endpoint:** `/api/valores-referencia` con
  `GET ?accion=listar&determinacion_id=<id>`,
  `POST ?accion=crear`, `POST ?accion=actualizar`, `POST ?accion=eliminar&id=<id>`.
  Respuestas con el envelope `{success,data,error}` habitual; validaciones (sexo,
  min<=max, edades, rango no vacio) devuelven 422.
- **La evaluacion de resultados NO cambio:** `ValorReferenciaRepository::findRangoAplicable`
  (lo que usa la carga de resultados para marcar anormal) quedo intacto.
- **Archivos:** `src/Repositories/ValorReferenciaRepository.php` (+listar/crear/
  actualizar/softDelete/findById), `src/Services/ValorReferenciaService.php` (nuevo,
  validacion + auditoria), `api/valores-referencia.php` (nuevo),
  `public/views/nomenclador/index.php` + `.js` (boton + modal),
  `tests/Services/ValorReferenciaServiceTest.php` (nuevo, 10 tests).
- **DB:** usa la tabla `lab_valores_referencia` existente; sin cambios de schema.
- **IMPACTA INTEGRACION:** endpoint nuevo. Si el sistema mayor administra los
  rangos por otra via, este ABM escribe sobre la misma tabla `lab_valores_referencia`.

### 3. Textos del informe PDF configurables *(hecho)* **(IMPACTA INTEGRACION)**

- **Antes:** textos del informe hardcodeados en el template
  (texto legal del pie, etiquetas de columnas, etiqueta "CRITICO").
- **Ahora:** se editan desde **Configuracion** y se guardan en `lab_config`
  (key/value, sin cambios de schema). Si un campo queda vacio, el informe usa el
  texto por defecto actual (fallback, no rompe nada).
- **Claves nuevas en `lab_config`:** `informe_legal_prefijo`,
  `informe_label_determinacion`, `informe_label_resultado`,
  `informe_label_valores_referencia`, `informe_label_critico`, `informe_nota_pie`.
- **Archivos:** `public/views/configuracion/index.php` + `.js` (seccion "Textos
  del informe"), `public/views/informes/template.php` (lee config con fallback).
- **IMPACTA INTEGRACION:** nuevas claves de configuracion en `lab_config`.

### 4. Menor — UX del editor del nomenclador *(hecho)*

- El editor de determinaciones del nomenclador pasa de aparecer debajo de la
  tabla a abrirse como **modal centrado** (fondo oscuro, cierra con la X, Escape
  o clic afuera). Mismo patron reusado por el modal de Rangos.
- **Archivos:** `public/views/nomenclador/index.php`, `public/assets/js/nomenclador/index.js`,
  `public/assets/css/app.css` (estilos `.modal-overlay`/`.modal-card`).

### Verificacion

- Suite PHPUnit en verde: **94 tests, 176 assertions** (se sumaron 10 tests del
  service de rangos; ademas se actualizaron 3 tests que habian quedado viejos
  respecto al diseño final de NBU/paciente_id, y el smoke `smoke_regression.php`
  usaba un metodo renombrado).
- Las 3 features probadas end-to-end contra la base (API) y en navegador.

---

## 2026-05-29 — Sesion 3

### 1. El NBU de la OS se aplica por fecha del analisis + re-precio automatico *(hecho)*

- **Que se reporto:** "cuando cambia el NBU de una obra social desde tal fecha,
  los analisis de ahi en adelante tienen que cobrarse con ese NBU". No pasaba:
  habia montos inflados y cambiar la vigencia no re-preciaba los pedidos.
- **Causas encontradas (2 bugs):**
  1. **Duplicacion de monto.** `PedidoRepository::recalcularMontoSeguroAlVuelo`
     y `recalcularPedidoConExclusiones` hacian un `LEFT JOIN` a
     `lab_nbu_valores_os`. Si la OS tenia vigencias con fechas **superpuestas**,
     cada item se multiplicaba por cada vigencia que matcheaba → monto inflado
     (ej. un pedido guardaba $5502.50 cuando correspondia $852.50 = 15.5 u × 55).
  2. **Vigencias superpuestas.** `NbuValorOsRepository::crearVigencia` solo
     cerraba la vigencia anterior si se cargaban en orden; al cargar fechas
     fuera de orden quedaban rangos pisados.
- **Que cambia ahora:**
  - **Regla unica de valor NBU (step-function):** el valor a una fecha es el de
    la vigencia con `fecha_desde` mas reciente <= esa fecha (sin tope por
    `fecha_hasta`). Aplicada en `findValorAt`, en los recalculos de pedido y en
    las subconsultas de la planilla (`LoteOsRepository`). Imposible que un
    solape devuelva un valor ambiguo o duplicado.
  - **Precio por fecha del pedido:** los montos del seguro se resuelven al NBU
    vigente a la `fecha_solicitud` del pedido (cada analisis con el NBU de su
    fecha), consistente entre alta, listado y lote.
  - **`crearVigencia` mantiene un timeline limpio:** si ya existe una vigencia
    con la misma `fecha_desde` actualiza su valor (no duplica); luego recomputa
    los `fecha_hasta` de toda la OS para que sean contiguos y sin solapes.
  - **Re-precio automatico:** al crear/cambiar una vigencia
    (`POST /api/aranceles?accion=valores-os`) se recalculan los pedidos **no
    facturados** de esa OS al NBU de su fecha. Los pedidos que ya estan en un
    lote **abierto o cobrado** NO se tocan (la facturacion queda bloqueada). La
    respuesta incluye `pedidos_repreciados`.
- **Archivos:** `src/Repositories/PedidoRepository.php` (recalculos corregidos +
  nuevo `reprecioPorVigenciaOs`), `src/Repositories/NbuValorOsRepository.php`
  (`findValorAt` step-function, `crearVigencia` + `recomputarTimeline`),
  `src/Repositories/LoteOsRepository.php` (subconsultas de la planilla),
  `src/Controllers/AranceladorController.php` (dispara el re-precio),
  `api/aranceles.php` (inyecta `PedidoRepository`).
- **DB:** no hay cambios de schema. Las vigencias superpuestas eran datos de
  prueba locales (el otro dev no los tiene); el codigo ya impide generarlas.
- **IMPACTA INTEGRACION:** comportamiento de calculo. Si el sistema mayor crea
  vigencias NBU por otra via, ahora cada analisis se cobra al NBU vigente a su
  fecha y cambiar una vigencia re-precia los pedidos libres de esa OS.

### 2. Quitar/editar un resultado mal cargado *(hecho)*

- Editar ya existia (re-cargar sobrescribe). Se agrega **quitar**: boton en
  "Cargar resultados" que elimina el resultado del item, lo deja en `pendiente`,
  recalcula el estado del pedido (`completo`→`parcial`/`pendiente`) y el flag de
  critico. Queda en auditoria. Bloqueado si el pedido esta `entregado`/`anulado`.
- **Archivos:** `ResultadoRepository::deleteByPedidoItemId`, `ResultadoService::quitar`,
  `ResultadoController::quitar`, `api/resultados.php` (metodo DELETE),
  `public/views/resultados/cargar.php`, `public/assets/js/resultados/cargar.js`.
- **IMPACTA INTEGRACION:** no.

### 3. Resultados anteriores del paciente al cargar *(hecho)*

- En "Cargar resultados", bajo cada determinacion se muestran los ultimos valores
  del mismo paciente en ese analisis (valor + fecha, en rojo si fue anormal).
  Match por `paciente_id` o por DNI del snapshot.
- **Archivos:** `ResultadoRepository::findAnterioresPorPaciente`,
  `HistorialService::dossierPedido` (enriquece items con `anteriores`),
  `public/assets/js/resultados/cargar.js`.
- **IMPACTA INTEGRACION:** no.

### 4. Buscar pacientes: ficha con sus analisis + nuevo pedido *(hecho)*

- En "Pacientes", al elegir uno se ve su ficha + tabla de todos sus pedidos
  (estado, items, "Ver resultados") y un boton "Generar analisis nuevo" que abre
  `/pedidos/nuevo?paciente_id=N` con el paciente precargado.
- **Archivos:** `public/views/pacientes/buscar.php`,
  `public/assets/js/pacientes/ficha.js` *(nuevo)*,
  `public/assets/js/pedidos/nuevo.js` (precarga por query param).
- **IMPACTA INTEGRACION:** no (reusa `/api/historial` y `/api/pacientes`).

### 5. ABM de nomenclador (determinaciones) *(hecho)* **(IMPACTA INTEGRACION)**

- Pantalla `/nomenclador` para crear/editar determinaciones (ej. COVID) con area,
  codigo, nombre, unidad, metodo, tipo, decimales, precio y NBU. El NBU se guarda
  en `lab_nbu_determinaciones`.
- **Archivos:** `DeterminacionRepository` (crear/actualizar/listarAreas/
  findAllParaAbm/findByIdParaAbm/codigoExiste), `api/determinaciones.php`
  (GET `?accion=abm|areas|obtener`, POST `?accion=crear|actualizar`),
  `public/views/nomenclador/index.php` *(nuevo)*,
  `public/assets/js/nomenclador/index.js` *(nuevo)*, ruta en `public/index.php`,
  tarjeta en `public/views/inicio.php`.
- **Que tiene que hacer el otro dev:** ninguna migracion; usa tablas existentes.

### 6. Acto bioquimico "solo facturacion" *(hecho)* **(IMPACTA INTEGRACION)**

- El acto bioquimico (determinacion codigo '1', NBU 6) se agrega automaticamente a
  cada pedido y se factura, pero **no figura como resultado ni en el informe PDF**.
  Se marca con la bandera `lab_determinaciones.solo_facturacion`. No aparece en el
  selector de nuevo pedido (se agrega solo).
- **DB:** migration `027` agrega `solo_facturacion` y marca el acto.
- **Archivos:** `DeterminacionRepository` (findSoloFacturacionIds; excluye solo_fact
  del selector), `PedidoService::crear` (auto-agrega el acto),
  `PedidoRepository::findItemsByPedidoId` (lo oculta de resultados).
- **Que tiene que hacer el otro dev:** aplicar migration 027.

### 7. Perfil facturado como una sola linea *(hecho)* **(IMPACTA INTEGRACION)**

- Un perfil puede tener su propio NBU (`lab_perfiles.nbu_unidades`). Si lo tiene,
  se factura **una sola linea con ese NBU** (no se desglosan los componentes); si
  queda NULL, se factura como antes (por componente). El hemograma (HMG) queda en
  NBU 5. Los resultados se siguen cargando por componente.
- **Modelo de monto del seguro:** `valor_OS_a_la_fecha × (NBU de perfiles-con-NBU
  presentes + NBU de componentes sueltos / de perfiles sin NBU, incluido el acto)`.
  Centralizado en `PedidoRepository` (`unidadesNbuSql` / `valorNbuSql` /
  `calcularMontoSeguroUnits`) y aplicado en alta, re-precio, recalculo con
  exclusiones y en el detalle de la planilla (`LoteOsRepository`).
- **DB:** migration `027` agrega `lab_perfiles.nbu_unidades` y setea HMG=5.
- **Archivos:** `PedidoRepository`, `PedidoService::crear`, `LoteOsRepository`
  (detalle del lote por concepto), schema install actualizado.
- **Que tiene que hacer el otro dev:** aplicar migration 027. Para que otros
  perfiles se facturen como una linea, cargarles `nbu_unidades` (hoy via SQL; el
  ABM de perfiles todavia no expone el campo).

---

## 2026-05-28 — Sesion 2

### 1. Listado de pedidos muestra todo al abrir *(hecho)*

- **Que cambia:** `/pedidos` ahora ejecuta la busqueda sin filtros al cargar la
  pagina, mostrando todos los pedidos. El buscador de arriba filtra sobre eso.
  Antes arrancaba vacio ("Aplica filtros y presiona Buscar").
- **Archivos:** `public/assets/js/pedidos/listado.js` (`init()` llama `ejecutarBusqueda()`).
- **IMPACTA INTEGRACION:** no.

### 2. paciente_id opcional en el pedido *(hecho)* **(IMPACTA INTEGRACION)**

- **Que cambia:** `lab_pedidos.paciente_id` pasa de NOT NULL a NULL. El modulo
  ya no obliga a cargar un paciente_id: en el sistema mayor el paciente viene
  del contexto. La fuente de verdad por pedido es `snapshot_paciente`.
- **DB:** migration `025_paciente_id_opcional.sql` *(nueva)*. Solo afloja el
  NOT NULL (no hay FK sobre la columna). Schema dump regenerado.
- **Backend:** `PedidoService::crear` ya no exige paciente_id; `Pedido::$pacienteId`
  pasa a `?int`.
- **Que tiene que hacer el otro dev:** aplicar migration 025 a la BD compartida.

### 3. Nuevo pedido: buscador de paciente del sistema *(hecho)*

- **Que cambia:** El form de `/pedidos/nuevo` ya no tiene el campo numerico
  "ID paciente" suelto. En su lugar hay un boton "Buscar paciente del sistema"
  que abre el `PacienteSelector` (lista/busca todos los pacientes). Al elegir
  uno se autocompletan nombre, DNI, sexo, fecha nac., obra social y N° afiliado,
  y se guarda el vinculo (paciente_id) en un campo oculto. Tambien se puede
  cargar a mano (queda sin paciente_id).
- **Por que:** evita el cruce nombre/id (antes el id y el nombre se cargaban por
  separado y podian no coincidir) y replica el flujo del sistema integrado.
- **Archivos:** `public/views/pedidos/nuevo.php`, `public/assets/js/pedidos/nuevo.js`.
- **IMPACTA INTEGRACION:** no (reusa `/api/pacientes?accion=buscar`).

### 4. Listado y detalle muestran el nombre cargado en la orden *(hecho)*

- **Que cambia:** El listado mostraba el nombre del registro `pacientes` (via
  JOIN por paciente_id); ahora muestra el nombre del `snapshot_paciente` (lo
  cargado en la orden), cayendo al join solo si falta. La columna HC cae al DNI
  del snapshot cuando no hay paciente_id. La busqueda rapida (`q`) tambien
  matchea sobre nombre/DNI del snapshot (via JSON_EXTRACT), asi se puede buscar
  por lo tipeado aunque el pedido no tenga paciente_id.
- **Por que:** un pedido con paciente_id que no coincidia con el nombre tipeado
  mostraba un nombre distinto en el listado que en el detalle/portada.
- **Archivos:** `src/Repositories/PedidoRepository.php` (`buscar`),
  `public/assets/js/pedidos/listado.js`.
- **IMPACTA INTEGRACION:** parcial — el endpoint `/api/pedidos?accion=listar`
  ahora devuelve campos extra `paciente_nombre` y `paciente_dni`. Backward compatible.

### 5. Detalle de pedido en un solo cuadro *(hecho)*

- **Que cambia:** En `/pedidos/ver`, los 3 cuadros (Datos del pedido / Paciente /
  Medico-Obra social) se unifican en un solo cuadro con subsecciones. Se quita
  el HC que mostraba "(paciente_id=N)"; la obra social ahora se muestra por
  nombre (no por id); el link "Ver historial" se oculta si el pedido no tiene
  paciente_id.
- **Backend:** `PedidoRepository::findById` ahora trae `obra_social_nombre`
  (LEFT JOIN obras_sociales) — esto tambien arregla la obra social en la portada.
- **Archivos:** `public/views/pedidos/ver.php`, `public/assets/js/pedidos/ver.js`,
  `public/assets/css/app.css` (`.subseccion`), `src/Repositories/PedidoRepository.php`.
- **IMPACTA INTEGRACION:** no.

### 6. Cargar resultados: lista todos los pedidos + filtro por dia *(hecho)*

- **Que cambia:** En `/resultados/cargar`, la seccion "Ultimos analisis
  realizados" (que mostraba solo parcial/completo, max 10) se reemplaza por un
  listado de **todos los pedidos** ordenados por fecha desc, con un **filtro por
  dia** (input date + botones "Hoy" y "Ver todos"). Click en "Cargar" abre el
  panel del pedido. El buscador con dropdown sigue igual.
- **Como filtra:** usa `fecha_solicitud_desde`/`fecha_solicitud_hasta` del
  endpoint `/api/pedidos?accion=listar` (mismo dia en ambos). Sin cambios de backend.
- **Archivos:** `public/views/resultados/cargar.php`,
  `public/assets/js/resultados/cargar.js`.
- **IMPACTA INTEGRACION:** no.

### 7. Fix: el PDF del informe no mostraba los valores de referencia *(hecho)*

- **Que pasaba:** la columna "Valores de referencia" del PDF salia vacia. El
  template solo mostraba `texto_referencia`, pero al cargar un resultado la
  referencia se guarda como rango numerico (`valor_referencia_min`/`max`) y el
  `texto_referencia` queda NULL.
- **Fix:** el template ahora arma la referencia igual que la vista de detalle:
  usa `texto_referencia` si existe; si no, formatea el rango min/max
  (`12,0 - 16,0`, `> X`, `< Y`) con los decimales de la determinacion.
- **Archivos:** `public/views/informes/template.php`.
- **IMPACTA INTEGRACION:** no.

### 8. Cargar resultados: guardar todo junto *(hecho)*

- **Que cambia:** en `/resultados/cargar` se quitan los botones "Guardar" por
  fila. Se carga el valor de cada item y se guarda todo de una con un unico
  boton "Guardar todos los resultados". Enter ahora salta al siguiente campo
  (carga rapida sin mouse) en vez de guardar fila por fila.
- **Como:** el front recorre las filas con valor y hace POST a `/api/resultados`
  por cada una (mismo endpoint de siempre), recarga una sola vez y muestra un
  resumen (N guardados / errores). Sin cambios de backend.
- **Archivos:** `public/views/resultados/cargar.php`,
  `public/assets/js/resultados/cargar.js`.
- **IMPACTA INTEGRACION:** no.

### 9. Emitir informes: lista de pedidos listos + firmante fijo *(hecho)*

- **Que cambia:** en `/informes` se agrega la seccion "Pedidos listos para
  informar" (pedidos con resultados cargados: estado completo o parcial). Se
  elige uno y se abre el panel para emitir, sin tener que buscar el numero.
  El campo "Firma (opcional)" se reemplaza por un campo de solo lectura que
  muestra el firmante configurado (Bioquimica Brizuela Laura · Mat. 3529),
  leido de `/api/lab-config`. La seccion "Ultimos informes emitidos" se movio
  al final de la pagina (debajo del panel de emision).
- **Nota:** el campo "firma" del formulario no se usaba en el PDF (el firmante
  sale de lab_config), por eso se quito del payload.
- **Archivos:** `public/views/informes/index.php`,
  `public/assets/js/informes/index.js`.
- **IMPACTA INTEGRACION:** no.

### 10. Catalogo: importar unidades, metodos y referencias *(hecho)* **(IMPACTA INTEGRACION)**

- **Que pasaba:** la mayoria de las determinaciones no tenian unidad ni rango
  de referencia, asi que en la pantalla de resultados y en el PDF salian vacios.
  Solo ~30 de 398 estaban configuradas.
- **Que se hizo:** se importo el catalogo del cliente (`Determinaciones.xlsx`,
  columnas codigo/nombre/unidad/referencia/metodo) a la BD, rellenando *solo
  los huecos* (no pisa lo ya cargado). Match por `codigo`. Resultado:
  126 unidades, 153 metodos y 164 referencias nuevas (catalogo paso de 30 a 156
  determinaciones con unidad y 194 con referencia).
- **Referencias:** son texto libre del cliente; se insertan en
  `lab_valores_referencia` con `sexo='ambos'` y edad sin limites (aplican a
  cualquier paciente). La referencia se *fotografia* al cargar el resultado,
  asi que aplica a cargas NUEVAS de pacientes con sexo y fecha de nacimiento;
  los resultados ya cargados conservan lo que tenian.
- **DB:** migration `026_texto_referencia_text.sql` *(nueva)*:
  `lab_valores_referencia.texto_referencia` pasa de VARCHAR(200) a TEXT (algunas
  referencias del cliente son tablas multilinea de hasta ~950 caracteres).
- **Que tiene que hacer el otro dev:** aplicar migration 026 y, si corresponde,
  correr la misma importacion del catalogo en la BD compartida.

### 11. Facturacion OS: obra social por nombre (select) *(hecho)*

- **Que cambia:** en `/facturacion-os` los campos de obra social (filtro de
  lotes y "Nuevo lote") dejan de ser un input numerico de ID y pasan a ser un
  `<select>` con el nombre de la OS. Se poblan desde
  `/api/pacientes?accion=obras_sociales`. El select sigue enviando el
  `obra_social_id` al backend, sin cambios en API ni logica.
- **Archivos:** `public/views/facturacion-os/index.php`,
  `public/assets/js/facturacion-os/index.js`.
- **IMPACTA INTEGRACION:** no.

### 12. Lote OS: PDF rediseñado como "Planilla de facturacion" + fix duplicados *(hecho)*

- **Que cambia:** el PDF del lote pasa de un resumen de 1 fila por pedido al
  formato de planilla del cliente: agrupado por pedido, con header del paciente
  (Paciente / Nº Afiliado / Nº Orden / Fecha / Bioquimico), tabla
  `CODIGO | DESCRIPCION | U.B. | U.B. O.S. | PRECIO`, SUB TOTAL por orden y
  TOTAL GENERAL. El header del laboratorio (nombre, direccion) y el bioquimico
  firmante salen de `lab_config`.
- **Fix (bug):** el detalle de items se duplicaba cuando la OS tenia vigencias
  NBU superpuestas (LEFT JOIN que matcheaba varias) y ademas tomaba el valor
  unitario de "hoy". Ahora una subconsulta toma una sola vigencia, la aplicable
  a la **fecha del pedido** (misma logica que el arancelador), asi el total de
  la planilla coincide con el `monto_total` guardado del lote. Aplica al PDF y
  al CSV.
- **Archivos:** `src/Services/LoteOsPdfRenderer.php`,
  `src/Repositories/LoteOsRepository.php` (`findDetalleParaPlanilla` nueva +
  fix en `findItemsDelLote`), `public/views/pdf/lote-os.php`.
- **IMPACTA INTEGRACION:** no.

### 13. Carga de resultados sin decimales forzados + informe sin espacio en blanco *(hecho)*

- **Carga de resultados:** al recargar un resultado, el input mostraba el valor
  con los ceros decimales de la BD (`9.5000`, `95.0000`). Ahora muestra el valor
  tal cual se cargo (`9.5`, `95`), sin agregar decimales. El usuario pone los
  decimales solo si los necesita. (`public/assets/js/resultados/cargar.js`)
- **Informe PDF:** habia un bloque de espacio en blanco grande entre el
  encabezado y los datos del paciente, porque el margen superior reservado
  (65mm) era mucho mayor que el encabezado real. Se ajusto a 42mm (y el header
  se reposiciono), eliminando el hueco. (`public/views/informes/template.php`)
- **IMPACTA INTEGRACION:** no.

### 14. Referencias: visibles al cargar + formato fiel + hemograma del informe real *(hecho)*

- **Aparecen antes de guardar:** en `/resultados/cargar`, la columna Referencia
  ahora muestra el rango del catalogo apenas se abre el pedido (no hay que
  guardar primero). El dossier (`HistorialService`) enriquece cada item con la
  referencia aplicable segun sexo/edad del paciente (misma logica que usa
  `ResultadoService` al fotografiar el resultado).
- **Formato fiel:** las referencias de texto libre se muestran tal cual (con su
  unidad, `%`, multilinea por sexo, etc.). Las que solo tienen rango numerico se
  muestran como `min a max unidad` (antes era `min - max` sin unidad). Aplica a
  la carga y al informe PDF.
- **Hemograma:** se cargaron las referencias del hemograma copiadas del informe
  real del cliente (HB `12,0 a 16,0 g/dL`, HTO `36,0 a 46,0 %`, VCM `80 a 97 fL`,
  GR por sexo, GB `4.400 a 11.300 /mm3`, HCM, CHCM, RDW).
- **Archivos:** `src/Services/HistorialService.php`, `api/historial.php`,
  `public/assets/js/resultados/cargar.js`, `public/views/informes/template.php`,
  y datos en `lab_valores_referencia`.
- **IMPACTA INTEGRACION:** no (los textos de referencia son datos del catalogo).

### 15. Hemograma: agregada la formula leucocitaria *(hecho)*

- **Que cambia:** el perfil Hemograma (HMG) suma 5 determinaciones nuevas de la
  formula leucocitaria: Neutrofilos Segmentados, Eosinofilos, Basofilos,
  Linfocitos y Monocitos (area Hematologia, unidad `%`). Cada una con su
  referencia copiada del informe real (`% + valor absoluto`). El perfil pasa de
  9 a 14 determinaciones.
- **Datos:** nuevas filas en `lab_determinaciones`, `lab_valores_referencia` y
  `lab_perfil_determinaciones` (perfil HMG).
- **Nota:** la pantalla de Nuevo pedido ya permite agregar perfiles (columna
  "Perfiles" en "Items del pedido"); al elegir un perfil se expande a sus
  determinaciones.
- **IMPACTA INTEGRACION:** no.

### 16. Fix: "Nueva vigencia" en aranceles no abria *(hecho)*

- **Que pasaba:** en `/aranceles` (pestaña "Valor por obra social"), el boton
  "Nueva vigencia" parecia no hacer nada. El modal usaba `class="modal"` en el
  div exterior, pero en el CSS `.modal` es solo la tarjeta interna (sin posicion
  fija ni centrado); el overlay de pantalla completa es `.modal-overlay`. Asi el
  modal aparecia como una caja suelta al final de la pagina.
- **Fix:** el div exterior pasa a `class="modal-overlay"` y la tarjeta interna a
  `class="modal"`. Ahora abre centrado. Verificado end-to-end: crea la vigencia
  y muestra "Vigencia creada".
- **Archivos:** `public/views/aranceles/index.php`.
- **IMPACTA INTEGRACION:** no.

### 17. ABM de perfiles (crear / editar) *(hecho)*

- **Que cambia:** nueva pantalla `/perfiles` (tarjeta "Perfiles" en el inicio)
  donde el bioquimico crea perfiles nuevos o edita los existentes: codigo,
  nombre, descripcion y el set de determinaciones (buscador + agregar/quitar).
  Los cambios impactan de inmediato en la columna "Perfiles" de Nuevo pedido.
- **Backend:** `api/perfiles.php` ahora ademas de listar (GET) acepta
  `POST ?accion=crear` y `POST ?accion=actualizar` (valida codigo unico, nombre,
  determinaciones validas; transaccion perfil + determinaciones).
  `PerfilRepository` suma `crear`, `actualizar`, `reemplazarDeterminaciones`,
  `codigoExiste`.
- **Archivos:** `api/perfiles.php`, `src/Repositories/PerfilRepository.php`,
  `public/views/perfiles/index.php` *(nueva)*,
  `public/assets/js/perfiles/index.js` *(nueva)*, `public/index.php` (ruta),
  `public/views/inicio.php` (tarjeta).
- **IMPACTA INTEGRACION:** parcial — `/api/perfiles` agrega acciones POST.
  El GET sigue igual. Backward compatible.

---

## 2026-05-19 — Sesion 1

### 1. Perfiles muestran las determinaciones que incluyen *(hecho)*

- **Que cambia:** En la vista `/pedidos/nuevo` y en el modal "Planilla por
  perfil" del listado, cada perfil muestra debajo del nombre la lista de
  determinaciones que lo componen (ej: `GOT - GPT - FAL - BILIRRUBINA - GGT`).
- **Por que:** Antes solo se veia el nombre/codigo del perfil. El usuario
  de recepcion no podia saber que analisis estaba sumando al pedido sin
  abrirlo y expandirlo a mano.
- **Archivos tocados:** `public/assets/js/pedidos/nuevo.js`,
  `public/assets/js/pedidos/listado.js`, CSS del proyecto.
- **Backend / DB:** sin cambios. El endpoint `/api/perfiles?accion=listar`
  ya devolvia `determinaciones[]` por perfil; solo se usa en el front.
- **IMPACTA INTEGRACION:** no.

### 2. Busqueda rapida en listado de pedidos *(hecho)*

- **Que cambia:** `/pedidos` arriba de los filtros, un input unico
  "Buscar (DNI, apellido, nombre, HC, N° orden)" que matchea sobre
  cualquiera de esos campos. El resto de los filtros (estado, prioridad,
  obra social, fechas, etc.) queda colapsado bajo `<details>` "Mas filtros".
- **Por que:** Antes habia que abrir el modal de paciente o tipear el
  numero exacto. Ahora se tipea libre y filtra.
- **Archivos tocados:** `public/views/pedidos/listado.php`,
  `public/assets/js/pedidos/listado.js`,
  `src/Services/PedidoService.php` (acepta `q` en filtros),
  `src/Repositories/PedidoRepository.php` (suma `OR LIKE` sobre paciente +
  numero de orden).
- **API:** `GET /api/pedidos?accion=buscar&q=<texto>` queda equivalente
  a usar los filtros separados; los filtros viejos siguen funcionando.
- **DB:** sin cambios de esquema. Se aprovechan los indices existentes
  sobre `pacientes.dni` y `pacientes.apellido`.
- **IMPACTA INTEGRACION:** parcial — si el otro dev consume
  `/api/pedidos?accion=buscar` por su cuenta, ahora puede mandarle `q`
  ademas de los filtros separados. Backward compatible.

### 3. Busqueda en /resultados/cargar *(hecho)*

- **Que cambia:** Antes pedia el **ID interno** del pedido (numerico).
  Ahora hay un buscador identico al del listado: tipeas DNI / nombre /
  N° de orden y un dropdown muestra los pedidos coincidentes (filtrados
  a estados `pendiente`, `en_proceso`, `parcial`). Click selecciona el
  pedido.
- **Por que:** El tecnico no sabia el ID interno del pedido y tenia que
  abrir el listado en otra solapa para anotarlo. Ahora busca directo.
- **Archivos tocados:** `public/views/resultados/cargar.php`,
  `public/assets/js/resultados/cargar.js`.
- **IMPACTA INTEGRACION:** no.

### 4. Numero de orden sin prefijo (solo digitos) *(hecho)* **(IMPACTA INTEGRACION)**

- **Que cambia:** El numero de orden visible y guardado en
  `lab_pedidos.numero` deja de ser `P-YYYY-NNNNN` y pasa a ser un
  correlativo **simple por año** (ej: `42` para el pedido 42 del año
  actual). Reseteo anual.
- **Por que:** Pedido del cliente para acortar y simplificar el numero.
- **Impacto en datos historicos:** los pedidos viejos (con formato
  `P-2026-XXXXX`) se migran al formato nuevo. **Si ya se imprimieron
  informes PDF con el numero viejo, esos PDFs quedan con el numero viejo
  y los pedidos en sistema con el nuevo**. Coordinar antes de aplicar en
  produccion.
- **Constraint UNIQUE:** se cambia de `UNIQUE (numero)` a una constraint
  compuesta `UNIQUE (anio_orden, numero)` donde `anio_orden` es una
  columna generada `YEAR(fecha_solicitud)`. Asi el correlativo puede
  reiniciar cada año sin colisionar.
- **Archivos tocados:**
  - `src/Services/PedidoService.php` — `sprintf('%d', ...)` en vez de
    `'P-%04d-%05d'`.
  - `sql/migrations/024_numero_orden_simple.sql` *(nueva)*: agrega
    `anio_orden` generada, cambia UNIQUE, hace `UPDATE` de los viejos.
  - `sql/install/lab_schema.sql` — schema dump regenerado.
  - `public/views/pedidos/listado.php` — placeholder del input de N°.
- **Que tiene que hacer el otro dev:** aplicar la migration 024 a la BD
  compartida. Si tiene codigo que arme/parsee el numero de orden, ajustar.

---

## Para que el otro dev cargue estos cambios

El codigo todavia no esta commiteado/pusheado: hay que hacer commit + push
para que el otro dev pueda traer estos cambios. Una vez que los tenga, en la
BD compartida debe correr **en este orden**:

1. **Migraciones** (en `sql/migrations/`, aplicar las que falten):
   - [ ] `024_numero_orden_simple.sql` (Sesion 1 — numero de orden simple).
   - [ ] `025_paciente_id_opcional.sql` (Sesion 2 — `paciente_id` NULL).
   - [ ] `026_texto_referencia_text.sql` (Sesion 2 — `texto_referencia` -> TEXT).
   - [ ] `027_acto_facturacion_y_nbu_perfil.sql` (Sesion 3 — `solo_facturacion`
     en determinaciones + `nbu_unidades` en perfiles; marca el acto y HMG=5).
2. **Seed de catalogo** (despues de las migraciones):
   - [ ] `sql/seeds/003_catalogo_unidades_referencias.sql` — unidades, metodos,
     referencias del catalogo + hemograma + formula leucocitaria. Es REPLACE
     (idempotente), no pisa otras filas. Incluye los cambios de los items 10, 14 y 15.

> Nota: el seed asume que la BD ya tiene el catalogo base (seeds 001 y 002) con
> los mismos IDs de determinaciones. Si el catalogo del otro dev difiere en IDs,
> coordinar antes de correrlo.

## Pendiente de coordinar con el otro dev

Items abiertos del `INTEGRACION.md` que afectan a estos cambios o al
proximo deploy:

- [ ] Contrato `pacientes` confirmado.
- [ ] Contrato `obras_sociales` confirmado.
- [ ] Contrato `medicos` confirmado.
- [ ] Contrato `usuarios` confirmado.
- [ ] Mecanismo de sesion acordado (claves en `$_SESSION`).
- [ ] Estrategia de embebido acordada (rutas / iframe / sub-app).
- [ ] Esquema de roles acordado.
