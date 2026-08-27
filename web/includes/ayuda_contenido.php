<?php

declare(strict_types=1);

/**
 * Contenido de ayuda de uso. Cada módulo puede limitarse con 'roles' (null = todos).
 *
 * @return array<string, array{
 *   titulo: string,
 *   categoria: string,
 *   resumen: string,
 *   roles?: list<string>|null,
 *   secciones: list<array{titulo: string, items: list<string>}>,
 *   enlaces?: list<array{href: string, texto: string}>
 * }>
 */
function ayuda_modulos(): array
{
    return [
        'inicio' => [
            'titulo' => 'Guía de uso — inicio',
            'categoria' => 'Introducción',
            'resumen' => 'Visión general del sistema web y del circuito diario recomendado en recepción.',
            'secciones' => [
                [
                    'titulo' => '¿Qué es Control Salud Web?',
                    'items' => [
                        'Versión web del sistema de gestión del centro: pacientes, agenda, órdenes, caja, facturación a obras sociales y configuración.',
                        'Cada usuario opera en su clínica (multi-clínica). Los datos de una clínica no se mezclan con los de otra.',
                        'El menú lateral agrupa las pantallas por área; la barra superior muestra el logo de su clínica.',
                    ],
                ],
                [
                    'titulo' => 'Circuito diario recomendado (recepción)',
                    'items' => [
                        '1. Agenda diaria: revisar turnos del día o dar uno nuevo.',
                        '2. Llegada: marcar «Llegó» cuando el paciente está físicamente en el centro.',
                        '3. Cobro / Orden: registrar pago particular o cargar la orden de obra social vinculada al turno.',
                        '4. Atención: el profesional usa «Llamar» (Anunciador) y luego «Atendido».',
                        '5. Control diario: revisar pendientes (sin orden, sin pago, sin caja) antes de cerrar.',
                        '6. Cierre de caja: declarar efectivo y cerrar el turno o día.',
                    ],
                ],
                [
                    'titulo' => 'Roles de usuario',
                    'items' => [
                        'Doctor: agenda, anunciador, pacientes (consulta), odontograma e historia clínica según permisos.',
                        'Admin clínica: todo lo operativo de su clínica + usuarios y configuración (excepto superadmin).',
                        'Superadmin: acceso global y configuración de todas las clíicas.',
                    ],
                ],
                [
                    'titulo' => 'Portal del paciente',
                    'items' => [
                        'Enlace público para que los pacientes saquen turno online con su DNI (menú Agenda → Portal pacientes, o recuadro en Inicio/Ayuda).',
                        'Compartilo por WhatsApp o en la web del centro; los turnos entran solos a la Agenda diaria.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/index.php', 'texto' => 'Panel de inicio'],
                ['href' => '/agenda.php', 'texto' => 'Agenda diaria'],
            ],
            'capturas' => [
                ['archivo' => 'inicio-panel.png', 'titulo' => 'Panel de inicio', 'texto' => 'Resumen operativo y accesos del día.'],
                ['archivo' => 'barra-superior.png', 'titulo' => 'Barra superior', 'texto' => 'Logo de la clínica y usuario activo en todas las pantallas.'],
            ],
        ],
        'pacientes' => [
            'titulo' => 'Pacientes',
            'categoria' => 'Recepción',
            'resumen' => 'Alta, búsqueda y ficha del paciente (Nro. HC, cobertura, datos de contacto).',
            'secciones' => [
                [
                    'titulo' => 'Buscar y abrir ficha',
                    'items' => [
                        'En Pacientes use el buscador por apellido, nombre, DNI o Nro. HC.',
                        'Desde la ficha puede editar datos, ver historia clínica u odontograma y acceder a órdenes relacionadas.',
                        'El Nro. HC es único dentro de cada clínica.',
                    ],
                ],
                [
                    'titulo' => 'Alta de paciente',
                    'items' => [
                        'Complete apellido, nombre, documento y cobertura cuando corresponda.',
                        'Teléfono y email son útiles para recordatorios WhatsApp y Agenda Web.',
                        'Guarde antes de cargar turnos u órdenes para evitar duplicados.',
                    ],
                ],
                [
                    'titulo' => 'Buenas prácticas',
                    'items' => [
                        'Verifique si el paciente ya existe antes de crear uno nuevo.',
                        'Mantenga actualizada la obra social y el plan: impacta aranceles y facturación.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/pacientes.php', 'texto' => 'Ir a Pacientes'],
            ],
            'capturas' => [
                ['archivo' => 'pacientes-listado.png', 'titulo' => 'Listado de pacientes', 'texto' => 'Buscador y tabla con Nro. HC, cobertura y acciones.'],
            ],
        ],
        'agenda' => [
            'titulo' => 'Agenda diaria',
            'categoria' => 'Recepción',
            'resumen' => 'Turnos del día: estados, cobro, llamado y atención.',
            'secciones' => [
                [
                    'titulo' => 'Filtros y grilla',
                    'items' => [
                        'Elija fecha y profesional para ver la agenda correspondiente.',
                        'Puede ordenar la tabla (por hora, paciente, etc.) según necesite.',
                        'Los colores y estados indican confirmación, llegada, llamado y atención.',
                        'La agenda muestra únicamente turnos con un paciente asignado. Los registros de horarios vacíos del sistema anterior no se muestran ni se cuentan.',
                    ],
                ],
                [
                    'titulo' => 'Acciones por turno',
                    'items' => [
                        'Nuevo turno: botón correspondiente; elija profesional y fecha, luego un horario en la grilla verde (clic) sin tipear la hora a mano.',
                        'Horario disponible (sin paciente): doble clic en la fila o botón «Asignar turno» abre el formulario con la hora ya cargada.',
                        'Llegó: presencia física en el centro. No implica que esté cobrado.',
                        'Cobro / Orden: abre la gestión administrativa del turno (particular u OS).',
                        'Llamar: envía al Anunciador (monitor de sala). Requiere que el paciente haya llegado.',
                        'Atendido: cierra el circuito asistencial del turno.',
                        'Anular: cancela el turno; puede disparar aviso por WhatsApp si está configurado.',
                    ],
                ],
                [
                    'titulo' => 'Elegir horario al cargar turno',
                    'items' => [
                        'En el formulario de turno, la grilla debajo del profesional muestra horarios libres en verde.',
                        'Un clic en verde completa el campo Hora automáticamente; doble clic además lleva el cursor al Nro HC.',
                        '«Próximos turnos libres» permite elegir día y luego la hora con un clic.',
                        'El campo Hora manual sigue disponible si necesita un horario fuera de la grilla.',
                    ],
                ],
                [
                    'titulo' => 'Precauciones',
                    'items' => [
                        'Si el paciente ya llegó, algunas acciones de edición se limitan para evitar inconsistencias.',
                        'Revise bloqueos de agenda si no aparecen horarios libres esperados.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/agenda.php', 'texto' => 'Ir a Agenda'],
                ['href' => '/turno_form.php', 'texto' => 'Nuevo turno'],
            ],
            'capturas' => [
                ['archivo' => 'agenda-diaria.png', 'titulo' => 'Agenda del día', 'texto' => 'Filtros de fecha/profesional y acciones por turno (Llegó, Cobro, Llamar, Atendido).'],
            ],
        ],
        'cobro_orden' => [
            'titulo' => 'Cobro / Orden (recepción del turno)',
            'categoria' => 'Recepción',
            'resumen' => 'Pantalla administrativa vinculada a un turno concreto.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Paciente particular',
                    'items' => [
                        'Registre importe, forma de pago y turno de caja (mañana/tarde/noche).',
                        'El pago genera movimiento en Caja automáticamente.',
                        'Puede imprimir recibo desde Pagos.',
                    ],
                ],
                [
                    'titulo' => 'Obra social',
                    'items' => [
                        'Use «Cargar orden completa» para abrir el formulario de orden prellenado (paciente, fecha, profesional).',
                        'Si ya existe orden para el turno, use «Ver / editar orden» para no duplicar.',
                        'Complete práctica, médico, cobertura, Paga IVA y estado Cerrada. Las sesiones se administran después desde la orden guardada.',
                    ],
                ],
                [
                    'titulo' => 'Llegada y cobro',
                    'items' => [
                        'La llegada física se marca aparte en Agenda.',
                        'Opción «También marcar como Llegó» solo si corresponde en el momento del cobro.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/control_administrativo.php', 'texto' => 'Control diario'],
                ['href' => '/ordenes.php', 'texto' => 'Órdenes'],
            ],
            'capturas' => [
                ['archivo' => 'cobro-orden.png', 'titulo' => 'Cobro / Orden', 'texto' => 'Gestión administrativa del turno: pago particular u orden de obra social.'],
            ],
        ],
        'control_diario' => [
            'titulo' => 'Control diario',
            'categoria' => 'Recepción',
            'resumen' => 'Auditoría de turnos del día: órdenes, pagos y caja.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Para qué sirve',
                    'items' => [
                        'Detecta turnos atendidos o en curso con pendientes administrativos.',
                        'Indicadores superiores filtran: sin orden, sin pago, pago sin caja, etc.',
                        'Pantalla recomendada antes del cierre de caja o facturación a OS.',
                    ],
                ],
                [
                    'titulo' => 'Cómo resolver pendientes',
                    'items' => [
                        'Desde cada fila vuelva a Cobro / Orden o a la orden/pago correspondiente.',
                        'Use los accesos a Pagos y Caja del día para verificar montos.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/control_administrativo.php', 'texto' => 'Ir a Control diario'],
            ],
            'capturas' => [
                ['archivo' => 'control-diario.png', 'titulo' => 'Control diario', 'texto' => 'Indicadores de pendientes y detalle por turno.'],
            ],
        ],
        'anunciador' => [
            'titulo' => 'Anunciador',
            'categoria' => 'Recepción',
            'resumen' => 'Monitor de sala y panel operador para llamar pacientes.',
            'secciones' => [
                [
                    'titulo' => 'Dos modos',
                    'items' => [
                        'Monitor (TV/sala): /anunciador.php — pantalla limpia, solo llamados activos.',
                        'Operador: /anunciador.php?modo=operador — gestiona estados desde recepción o consultorio.',
                    ],
                ],
                [
                    'titulo' => 'Flujo',
                    'items' => [
                        'Desde Agenda, «Llamar» publica paciente, consultorio y profesional.',
                        'Al marcar «Atendido» en Agenda se finaliza el llamado activo.',
                        'Si no hay llamados, el monitor muestra un mensaje de espera.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/anunciador.php', 'texto' => 'Monitor'],
                ['href' => '/anunciador.php?modo=operador', 'texto' => 'Modo operador'],
            ],
        ],
        'agenda_web' => [
            'titulo' => 'Portal del paciente (turnos online)',
            'categoria' => 'Recepción',
            'resumen' => 'Pantalla pública para que el paciente pida turno con su documento, sin entrar al sistema interno.',
            'secciones' => [
                [
                    'titulo' => 'Enlace para compartir',
                    'items' => [
                        'Cada clínica tiene su propia URL (ver recuadro «Portal del paciente» en Inicio o Ayuda).',
                        'Podés copiar el enlace y enviarlo por WhatsApp, mail o colgarlo en la web del centro.',
                        'El paciente solo necesita el DNI/documento que figura en su ficha.',
                    ],
                ],
                [
                    'titulo' => 'Qué hace el paciente',
                    'items' => [
                        'Ingresa con documento → elige fecha → profesional → horario libre → confirma.',
                        'El turno aparece en Agenda diaria (recepción lo ve igual que un turno cargado a mano).',
                        'No puede entrar al resto del sistema (pacientes, caja, etc.).',
                    ],
                ],
                [
                    'titulo' => 'Requisitos y límites',
                    'items' => [
                        'El DNI debe existir en Pacientes; si es nuevo, cargarlo antes o usar recepción.',
                        'Respeta horarios ocupados y bloqueos de agenda.',
                        'Probá el enlace con un paciente de prueba antes de difundirlo.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/ayuda.php?mod=agenda', 'texto' => 'Ayuda: Agenda diaria'],
            ],
        ],
        'bloqueos' => [
            'titulo' => 'Bloqueos de agenda',
            'categoria' => 'Recepción',
            'resumen' => 'Feriados, vacaciones o franjas sin atención por profesional.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Uso',
                    'items' => [
                        'Defina rango de fechas y profesional (o todos) para impedir nuevos turnos en ese período.',
                        'Los turnos ya existentes no se borran automáticamente: revíselos en Agenda.',
                        'Útil antes de vacaciones o cambios de horario.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/agenda_bloqueos.php', 'texto' => 'Ir a Bloqueos'],
            ],
        ],
        'recordatorios' => [
            'titulo' => 'Recordatorios WhatsApp',
            'categoria' => 'Recepción',
            'resumen' => 'Confirmación y avisos de turnos por WhatsApp (Gesis / modo manual).',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Bandeja de recordatorios',
                    'items' => [
                        'Lista mensajes pendientes, enviados o con error.',
                        'Al crear o anular turnos puede encolarse un aviso automático según configuración.',
                        'Revise teléfonos en la ficha del paciente (formato válido).',
                    ],
                ],
                [
                    'titulo' => 'Plantillas',
                    'items' => [
                        'En Plantillas WhatsApp edite textos con variables ({paciente}, {fecha}, {hora}, {clinica}, etc.).',
                        'Nombre y dirección de clínica se configuran en Sistema → Logo e identidad / parámetros.',
                    ],
                ],
                [
                    'titulo' => 'Proveedor',
                    'items' => [
                        'Modo producción: integración Gesis (webhook y vinculación QR).',
                        'Modo manual: enlace wa.me para enviar desde el celular del centro.',
                        'Consulte documentación de despliegue si los mensajes no salen.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/recordatorios.php', 'texto' => 'Recordatorios'],
                ['href' => '/recordatorios_plantillas.php', 'texto' => 'Plantillas WhatsApp'],
            ],
        ],
        'ordenes' => [
            'titulo' => 'Órdenes',
            'categoria' => 'Prestaciones',
            'resumen' => 'Órdenes de prestaciones para obra social o internas.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Formulario compacto',
                    'items' => [
                        'Seleccione el paciente por DNI o nombre; el sistema completa su Nro. HC. Indique también la fecha de la orden.',
                        'Los campos de uso diario son práctica, médico, cobertura médica, costo paciente, costo obra social, Paga IVA y Cerrada. El médico es obligatorio.',
                        'En una orden nueva, cobertura e IVA se proponen desde la ficha del paciente.',
                        'Al elegir cobertura y práctica, el sistema propone Costo paciente y Costo obra social desde Aranceles. Podés ajustarlos antes de guardar.',
                        'Al editar, los datos históricos no visibles se conservan y no se borran.',
                    ],
                ],
                [
                    'titulo' => 'Práctica y nomenclador',
                    'items' => [
                        'Busque la práctica por código real del nomenclador o por parte del nombre.',
                        'Las opciones se muestran como «420101 - CONSULTA ESPECIALISTA»; el ID interno continúa usándose para relacionar órdenes y aranceles.',
                        'La columna Práctica del listado también muestra código y nombre.',
                    ],
                ],
                [
                    'titulo' => 'Estados y facturación',
                    'items' => [
                        'Toda orden nueva con cobertura comienza como A: pendiente de facturación a la obra social.',
                        'Facturación OS cambia las órdenes seleccionadas de A (a facturar) a F (facturadas).',
                        'Si cambian los aranceles, puede propagar importes a órdenes A desde Aranceles o desde Facturación OS antes de cerrar el lote.',
                        '«Cerrada» es un indicador propio de la orden y no significa que esté facturada.',
                        'Los filtros de estado, IVA, autorización y liquidación están en «Filtros avanzados».',
                    ],
                ],
                [
                    'titulo' => 'Desde turno',
                    'items' => [
                        'Lo más habitual es crear la orden desde Cobro / Orden en Agenda.',
                        'Paciente, fecha y profesional llegan preseleccionados desde el turno.',
                        'Evite duplicar órdenes para el mismo turno.',
                        'Después de guardar puede registrar pagos o administrar sesiones desde los accesos de la orden.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/ordenes.php', 'texto' => 'Listado de órdenes'],
                ['href' => '/orden_form.php', 'texto' => 'Nueva orden'],
                ['href' => '/facturacion_ordenes.php', 'texto' => 'Facturación OS'],
            ],
        ],
        'sesiones' => [
            'titulo' => 'Sesiones',
            'categoria' => 'Prestaciones',
            'resumen' => 'Sesiones de tratamiento vinculadas a órdenes.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Uso',
                    'items' => [
                        'Registre sesiones realizadas o planificadas según el tipo de práctica.',
                        'Impacta cantidades al facturar órdenes con múltiples sesiones.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/sesiones.php', 'texto' => 'Ir a Sesiones'],
            ],
        ],
        'facturacion' => [
            'titulo' => 'Facturación a obra social',
            'categoria' => 'Prestaciones',
            'resumen' => 'Reportes e impresión de lotes a facturar o ya facturados.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => '1. Seleccionar pendientes',
                    'items' => [
                        'Elija la vista «A facturar (pendientes)», una obra social y el período desde/hasta.',
                        'Pulse «Generar reporte». Solo se incluyen órdenes de esa cobertura que continúan en estado A.',
                    ],
                ],
                [
                    'titulo' => '2. Revisar e imprimir',
                    'items' => [
                        'El reporte muestra afiliado, paciente, código y nombre de práctica, fecha, cantidad, costo y totales.',
                        'Puede seleccionar todas las órdenes o desmarcar las que no correspondan al lote.',
                        '«Imprimir / PDF» genera un listado A4 horizontal con cabecera de la clínica. Imprimir no cambia el estado.',
                    ],
                ],
                [
                    'titulo' => '2b. Actualizar costos antes de facturar',
                    'items' => [
                        'Si los importes de la columna Costo práctica no coinciden con los aranceles vigentes, use «Actualizar costos desde aranceles».',
                        'Recalcula costo paciente y costo obra social de todas las órdenes pendientes (A) del período filtrado, tomando los valores de Aranceles.',
                        'Solo afecta órdenes en estado A; las ya facturadas (F) no se modifican.',
                        'Úselo como revisión final antes de imprimir o marcar el lote, por ejemplo si se ajustaron aranceles y no se propagaron al guardarlos.',
                    ],
                ],
                [
                    'titulo' => '3. Marcar como facturadas',
                    'items' => [
                        'Después de imprimir, pulse «Marcar seleccionadas como facturadas» y confirme la operación.',
                        'Solo las órdenes que todavía están en A pasan a F; por eso dejan de mezclarse con el próximo período.',
                        'La pantalla redirige a «Ya facturadas» para verificar el lote cerrado.',
                    ],
                ],
                [
                    'titulo' => 'Reimpresión',
                    'items' => [
                        'Use la vista «Ya facturadas (reimpresión)» con la misma obra social y período.',
                        'Puede volver a imprimir el reporte sin modificar las órdenes.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/facturacion_ordenes.php', 'texto' => 'Facturación OS'],
                ['href' => '/ordenes.php?estado_os=A', 'texto' => 'Órdenes pendientes (A)'],
            ],
        ],
        'historia' => [
            'titulo' => 'Historia clínica',
            'categoria' => 'Clínica',
            'resumen' => 'Notas y evolución del paciente.',
            'secciones' => [
                [
                    'titulo' => 'Acceso',
                    'items' => [
                        'Desde la ficha del paciente abra Historia clínica.',
                        'Registre evoluciones con fecha y profesional.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/pacientes.php', 'texto' => 'Buscar paciente'],
            ],
        ],
        'odontograma' => [
            'titulo' => 'Odontograma',
            'categoria' => 'Clínica',
            'resumen' => 'Registro odontológico por pieza y superficies.',
            'secciones' => [
                [
                    'titulo' => 'Uso',
                    'items' => [
                        'Abra desde Pacientes → paciente → Odontograma.',
                        'Seleccione piezas, estados y observaciones.',
                        'Use Imprimir para entregar registro en papel o PDF.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/odontograma.php', 'texto' => 'Odontograma (requiere paciente)'],
            ],
        ],
        'pagos' => [
            'titulo' => 'Pagos',
            'categoria' => 'Finanzas',
            'resumen' => 'Pagos particulares registrados en el sistema.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Consulta y recibos',
                    'items' => [
                        'Filtre por fecha, paciente u orden.',
                        'Desde el listado puede imprimir recibo.',
                        'Anular un pago genera contra movimiento en caja si correspondía.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/pagos.php', 'texto' => 'Ir a Pagos'],
            ],
        ],
        'caja' => [
            'titulo' => 'Caja y cierre',
            'categoria' => 'Finanzas',
            'resumen' => 'Movimientos diarios, correcciones y cierre de turno.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Movimientos',
                    'items' => [
                        'Ingresos y egresos por fecha, profesional, cobertura y texto libre.',
                        'Los movimientos no se editan ni borran: use «Contra movimiento» para corregir.',
                        'Pagos de turnos particulares suelen aparecer automáticamente.',
                    ],
                ],
                [
                    'titulo' => 'Cálculo y cierre',
                    'items' => [
                        'Seleccione fecha y turno: día completo, mañana o tarde.',
                        'Revise movimientos, ingresos, egresos, total del sistema y totales por medio de pago.',
                        'Indique el efectivo declarado; la diferencia se compara contra los movimientos registrados como efectivo (modo 0).',
                        'Un cierre queda histórico; no se reescribe.',
                    ],
                ],
                [
                    'titulo' => 'Distribución informativa 70/30',
                    'items' => [
                        'La base son únicamente los ingresos positivos asociados a un profesional válido.',
                        'El resumen calcula 70% para el profesional y 30% para la clínica. Los egresos se informan aparte y no reducen esa base.',
                        'Los movimientos sin profesional se identifican como no distribuibles.',
                        'Es una simulación informativa: no genera pagos, liquidaciones ni nuevos movimientos de caja.',
                    ],
                ],
                [
                    'titulo' => 'Detalle por profesional e impresión',
                    'items' => [
                        'Pulse «Ver detalle» en un profesional para mostrar solamente sus movimientos.',
                        'El detalle discrimina importe total, profesional 70% y clínica 30%, con totales automáticos al final.',
                        '«Ver todos los profesionales» quita el filtro.',
                        '«Imprimir detalle» imprime solo cabecera clínica, movimientos filtrados y totales; no incluye menú ni acciones de corrección.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/caja.php', 'texto' => 'Caja'],
                ['href' => '/caja_cierre.php', 'texto' => 'Cierre de caja'],
            ],
            'capturas' => [
                ['archivo' => 'caja-movimientos.png', 'titulo' => 'Movimientos de caja', 'texto' => 'Ingresos, egresos y filtros del día.'],
                ['archivo' => 'caja-cierre.png', 'titulo' => 'Cierre de caja', 'texto' => 'Efectivo declarado, total sistema y diferencia.'],
            ],
        ],
        'doctores' => [
            'titulo' => 'Doctores / profesionales',
            'categoria' => 'Administración',
            'resumen' => 'Alta de profesionales que atienden en la clínica.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Alta y vínculo con usuario',
                    'items' => [
                        'Cada doctor del listado puede vincularse a un usuario con rol «doctor» para acceder solo a su agenda.',
                        'Complete matrícula y consultorio si usa Anunciador.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/doctores.php', 'texto' => 'Ir a Doctores'],
            ],
        ],
        'aranceles' => [
            'titulo' => 'Aranceles',
            'categoria' => 'Administración',
            'resumen' => 'Precios por obra social, plan y práctica; impactan órdenes nuevas y pueden propagarse a pendientes.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Uso',
                    'items' => [
                        'Mantenga actualizados los valores por cobertura y práctica (código de nomenclador).',
                        'Al cargar una orden, el sistema propone Costo paciente y Costo obra social desde el arancel correspondiente.',
                        'Los reportes de Facturación OS muestran el costo obra social guardado en cada orden.',
                    ],
                ],
                [
                    'titulo' => 'Propagar a órdenes pendientes',
                    'items' => [
                        'Al crear o editar un arancel puede marcar «Actualizar costos de órdenes pendientes en el período».',
                        'Indique desde/hasta y confirme: se actualizan órdenes con la misma cobertura, plan y práctica que sigan en estado A (a facturar).',
                        'Equivale al comportamiento del sistema de escritorio al modificar la lista de precios.',
                        'Las órdenes ya facturadas (F) no se tocan.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/aranceles.php', 'texto' => 'Ir a Aranceles'],
                ['href' => '/facturacion_ordenes.php', 'texto' => 'Facturación OS'],
            ],
        ],
        'catalogos' => [
            'titulo' => 'Tablas auxiliares',
            'categoria' => 'Administración',
            'resumen' => 'Obras sociales, prácticas, planes y otras tablas de apoyo.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Uso',
                    'items' => [
                        'Consulte y edite catálogos según permisos.',
                        'Cambios en obras sociales y prácticas afectan órdenes nuevas, no necesariamente históricas.',
                    ],
                ],
                [
                    'titulo' => 'Códigos de prácticas',
                    'items' => [
                        'Prácticas / estudios muestra el código real del nomenclador y el nombre de cada prestación.',
                        'Use el buscador del listado para filtrar por código o nombre.',
                        'El código visible aparece en Órdenes, Aranceles y Facturación OS; el ID interno se conserva para las relaciones del sistema.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/catalogos.php', 'texto' => 'Tablas auxiliares'],
            ],
        ],
        'sistema' => [
            'titulo' => 'Configuración y usuarios',
            'categoria' => 'Administración',
            'resumen' => 'Usuarios, logo de clínica, parámetros y migración desde .exe.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Usuarios',
                    'items' => [
                        'Cree usuarios con rol doctor, admin clínica o superadmin.',
                        'Cada usuario pertenece a una clínica; el nombre de login es único en todo el sistema.',
                        'Doctor debe vincularse a un profesional del listado.',
                    ],
                ],
                [
                    'titulo' => 'Identidad de la clínica',
                    'items' => [
                        'Logo e identidad: suba logo, nombre visible y dirección (menú, impresiones y WhatsApp).',
                        'Parámetros avanzados en la lista de config (solo administradores).',
                    ],
                ],
                [
                    'titulo' => 'Multi-clínica',
                    'items' => [
                        'Superadmin puede elegir clínica al editar identidad.',
                        'Admin clínica solo ve datos de su clínica.',
                        'No hay selector de clínica en sesión: cada usuario trabaja en la suya.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/sistema.php', 'texto' => 'Sistema y configuración'],
                ['href' => '/sistema.php?a=clinica_branding', 'texto' => 'Logo e identidad'],
            ],
            'capturas' => [
                ['archivo' => 'sistema-config.png', 'titulo' => 'Configuración', 'texto' => 'Usuarios, parámetros e identidad de la clínica.'],
            ],
        ],
        'laboratorio' => [
            'titulo' => 'Laboratorio',
            'categoria' => 'Módulos',
            'resumen' => 'Módulo aparte para pedidos, resultados e informes de laboratorio.',
            'roles' => ['superadmin', 'admin_clinica'],
            'secciones' => [
                [
                    'titulo' => 'Acceso',
                    'items' => [
                        'Entre desde el ítem Laboratorio en el menú lateral (si está habilitado).',
                        'Comparte pacientes y médicos con Control Salud según integración configurada.',
                        'Consulte la ayuda interna del módulo Laboratorio para flujos de pedidos e informes.',
                    ],
                ],
            ],
            'enlaces' => [
                ['href' => '/laboratorio/', 'texto' => 'Ir a Laboratorio'],
            ],
        ],
        'faq' => [
            'titulo' => 'Preguntas frecuentes',
            'categoria' => 'Introducción',
            'resumen' => 'Respuestas rápidas a dudas habituales de recepción, profesionales y administración.',
            'secciones' => [],
            'faqs' => [
                [
                    'pregunta' => '¿Por qué no veo datos de otra sucursal o clínica?',
                    'respuesta' => 'Cada usuario trabaja solo en su clínica (id_clinica). Si entró con un usuario de la clínica 2, no verá pacientes ni turnos de la clínica 1. El superadmin puede administrar varias clínicas, pero siempre con usuarios creados para cada una.',
                ],
                [
                    'pregunta' => 'Marqué «Llegó» pero el paciente no pagó. ¿Está todo bien?',
                    'respuesta' => 'Sí. «Llegó» solo indica presencia física. El cobro u orden se hace aparte con «Cobro / Orden». Revise el Control diario si necesita detectar llegados sin pago u orden.',
                ],
                [
                    'pregunta' => '¿Cuál es la diferencia entre «Llegó», «Llamar» y «Atendido»?',
                    'respuesta' => 'Llegó: el paciente está en el centro. Llamar: lo muestra en el Anunciador de sala (requiere haber marcado llegada). Atendido: el profesional finalizó la consulta. El orden habitual es Llegó → Cobro/Orden (si corresponde) → Llamar → Atendido.',
                ],
                [
                    'pregunta' => 'No puedo editar o borrar un turno. ¿Por qué?',
                    'respuesta' => 'Cuando el paciente ya llegó, el sistema limita cambios para no romper caja u órdenes vinculadas. Si hubo un error, anule el turno (si está permitido) o consulte con administración.',
                ],
                [
                    'pregunta' => '¿Cómo corrijo un error en caja?',
                    'respuesta' => 'Los movimientos de caja no se editan ni eliminan. Use «Contra movimiento» para registrar el ajuste opuesto y deje una observación clara. Así queda auditoría completa.',
                ],
                [
                    'pregunta' => 'Cerré la caja con un error en el efectivo declarado. ¿Puedo reabrirla?',
                    'respuesta' => 'No. El cierre queda como registro histórico. Registre la diferencia en un nuevo movimiento o documente el caso según política interna del centro.',
                ],
                [
                    'pregunta' => 'El paciente tiene obra social pero cobré en efectivo. ¿Qué hago?',
                    'respuesta' => 'Verifique la cobertura en la ficha del paciente. Si correspondía OS, cargue la orden y no duplique el cobro particular. Si el pago ya se registró, use contra movimiento en caja y ajuste la orden según el caso.',
                ],
                [
                    'pregunta' => '¿Dónde cargo la orden de una práctica con obra social?',
                    'respuesta' => 'Desde Agenda → «Cobro / Orden» del turno → «Cargar orden completa», o directamente en Prestaciones → Órdenes. Lo recomendado es hacerlo desde el turno para mantener el vínculo.',
                ],
                [
                    'pregunta' => '¿Qué significa estado A y F en órdenes?',
                    'respuesta' => 'A (a facturar): pendiente de presentar a la obra social. F (facturada): ya incluida en un lote. En Facturación OS seleccione cobertura y período, genere e imprima el reporte y luego confirme «Marcar seleccionadas como facturadas». Use «Ya facturadas» para reimprimir.',
                ],
                [
                    'pregunta' => 'Cambié un arancel pero las órdenes viejas siguen con otro importe. ¿Qué hago?',
                    'respuesta' => 'Al guardar el arancel puede marcar «Actualizar costos de órdenes pendientes en el período» (solo estado A, misma cobertura/plan/práctica). Si ya está en Facturación OS, use «Actualizar costos desde aranceles» para recalcular todas las pendientes del período antes de imprimir. Las órdenes facturadas (F) no se modifican.',
                ],
                [
                    'pregunta' => '¿Cómo busco una práctica por código de nomenclador?',
                    'respuesta' => 'En la orden o en Aranceles escriba el código real, por ejemplo 420101, o parte del nombre. En Sistema → Tablas auxiliares → Prácticas / estudios puede consultar el listado completo con buscador.',
                ],
                [
                    'pregunta' => '¿«Cerrada» significa que la orden está facturada?',
                    'respuesta' => 'No. «Cerrada» es un indicador de la orden. El estado de facturación de la cobertura se controla por separado: A significa pendiente y F significa facturada.',
                ],
                [
                    'pregunta' => '¿La distribución 70/30 genera una liquidación?',
                    'respuesta' => 'No. Es un cálculo informativo sobre ingresos positivos: 70% para el profesional y 30% para la clínica. No crea pagos, egresos ni asientos de caja.',
                ],
                [
                    'pregunta' => '¿Cómo imprimo solamente el detalle de un profesional?',
                    'respuesta' => 'Finanzas → Cierre de caja: seleccione fecha y turno, pulse «Ver detalle» junto al profesional y luego «Imprimir detalle». La impresión incluye sus movimientos y los totales general, 70% y 30%.',
                ],
                [
                    'pregunta' => 'Los recordatorios WhatsApp no se envían. ¿Qué reviso?',
                    'respuesta' => '1) Teléfono válido en la ficha del paciente. 2) Recordatorios habilitados en configuración. 3) Si usa Gesis: vinculación activa y webhook. 4) Bandeja de Recordatorios por mensajes con error. 5) Modo manual: debe abrir el enlace wa.me desde la bandeja.',
                ],
                [
                    'pregunta' => '¿Cómo pongo el logo de mi clínica en pantallas e impresiones?',
                    'respuesta' => 'Sistema → Configuración → Logo e identidad. Suba PNG o JPG (máx. 2 MB). El logo aparece en la barra superior, menú lateral, recibos, cierre de caja y otros comprobantes.',
                ],
                [
                    'pregunta' => 'Un doctor no ve Pacientes ni Caja. ¿Es normal?',
                    'respuesta' => 'Sí. El rol doctor tiene acceso a agenda, anunciador, pacientes (consulta), odontograma e historia según permisos, pero no a finanzas ni configuración. Para más acceso, use rol admin clínica.',
                ],
                [
                    'pregunta' => '¿Cómo doy de alta un usuario para un profesional?',
                    'respuesta' => 'Sistema → Configuración → Nuevo usuario. Rol «doctor», vincule el profesional del listado y asigne la clínica correcta. El nombre de usuario es único en todo el sistema (puede usar prefijo, ej. norte.drgarcia).',
                ],
                [
                    'pregunta' => '¿Dónde está el enlace para que el paciente saque turno solo?',
                    'respuesta' => 'En Inicio y en Ayuda aparece el recuadro «Portal del paciente» con el enlace de su clínica (también en menú Agenda → Portal pacientes). El paciente ingresa con DNI y el turno queda en Agenda diaria.',
                ],
                [
                    'pregunta' => 'Agenda Web: el paciente no encuentra turnos.',
                    'respuesta' => 'Verifique que el DNI esté cargado en su ficha, que haya horarios libres sin bloqueos, que el profesional elegido atienda ese día y que la URL use el id de clínica correcto (?clinica=).',
                ],
                [
                    'pregunta' => '¿El Anunciador no muestra nada en la TV?',
                    'respuesta' => 'Abra /anunciador.php en el navegador del monitor (pantalla completa). Desde Agenda debe usar «Llamar» con el paciente en estado Llegó. Pruebe refrescar la página del monitor.',
                ],
                [
                    'pregunta' => '¿Puedo usar el sistema desde el celular?',
                    'respuesta' => 'Las pantallas principales son responsivas para consulta básica. La operación intensiva (agenda, caja) se recomienda en PC. El Anunciador y Agenda Web sí pueden usarse en tablet o celular.',
                ],
            ],
            'capturas' => [
                ['archivo' => 'flujo-recepcion.svg', 'titulo' => 'Flujo de recepción', 'texto' => 'Secuencia recomendada: turno → llegada → cobro/orden → llamado → atendido.'],
            ],
        ],
    ];
}

function ayuda_capturas_public_dir(): string
{
    return dirname(__DIR__) . '/public/assets/ayuda';
}

function ayuda_captura_url(string $archivo): ?string
{
    $archivo = basename(str_replace('\\', '/', trim($archivo)));
    if ($archivo === '' || str_contains($archivo, '..')) {
        return null;
    }
    $full = ayuda_capturas_public_dir() . '/' . $archivo;
    if (!is_file($full)) {
        return null;
    }

    return url('/assets/ayuda/' . rawurlencode($archivo));
}

/**
 * @param list<array{archivo: string, titulo: string, texto?: string}> $capturas
 * @return list<array{url: string, titulo: string, texto: string}>
 */
function ayuda_capturas_disponibles(array $capturas): array
{
    $out = [];
    foreach ($capturas as $cap) {
        $url = ayuda_captura_url((string) ($cap['archivo'] ?? ''));
        if ($url === null) {
            continue;
        }
        $out[] = [
            'url' => $url,
            'titulo' => (string) ($cap['titulo'] ?? ''),
            'texto' => (string) ($cap['texto'] ?? ''),
        ];
    }

    return $out;
}

/**
 * @param array<string, array<string, mixed>> $modulos
 * @return array<string, array<string, mixed>>
 */
function ayuda_filtrar_por_rol(array $modulos, string $rol): array
{
    if ($rol === 'superadmin' || $rol === 'admin_clinica') {
        return $modulos;
    }

    return array_filter(
        $modulos,
        static function (array $mod) use ($rol): bool {
            $roles = $mod['roles'] ?? null;
            if ($roles === null || $roles === []) {
                return true;
            }

            return in_array($rol, $roles, true);
        }
    );
}

/**
 * @param array<string, array<string, mixed>> $modulos
 * @return array<string, list<array{id: string, titulo: string}>>
 */
function ayuda_agrupar_por_categoria(array $modulos): array
{
    $grupos = [];
    foreach ($modulos as $id => $mod) {
        $cat = (string) ($mod['categoria'] ?? 'General');
        $grupos[$cat][] = ['id' => $id, 'titulo' => (string) ($mod['titulo'] ?? $id)];
    }

    return $grupos;
}
