<?php

declare(strict_types=1);

/**
 * Columnas de la tabla Config del .exe (backup legacy en MySQL) → claves legibles para `config` web.
 *
 * Los RTF (encabezadogeneral, encabezadopaciente, encabezadoticket) se guardan tal cual por si hace falta migrarlos a HTML más adelante.
 */
final class ConfigExeFieldMap
{
    /**
     * @return array<string, array{clave: string, descripcion: string, sembrar: bool}>
     */
    public static function porColumna(): array
    {
        return [
            'NomDoc' => [
                'clave' => 'clinica.nombre_profesional',
                'descripcion' => 'Nombre del profesional titular (como en impresiones del exe).',
                'sembrar' => true,
            ],
            'Iniciales' => [
                'clave' => 'clinica.iniciales_profesional',
                'descripcion' => 'Iniciales (hasta 3 caracteres en el modelo original).',
                'sembrar' => true,
            ],
            'Matricula' => [
                'clave' => 'clinica.matricula_texto',
                'descripcion' => 'Leyenda de matrícula / colegio (texto libre).',
                'sembrar' => true,
            ],
            'ResInscrip' => [
                'clave' => 'exe.flag.res_inscripcion',
                'descripcion' => 'Flag “resolución inscripción” (0/1 en el exe).',
                'sembrar' => true,
            ],
            'Cuit' => [
                'clave' => 'clinica.cuit',
                'descripcion' => 'CUIT / identificación fiscal.',
                'sembrar' => true,
            ],
            'TelSinUso' => [
                'clave' => 'clinica.telefono_legacy',
                'descripcion' => 'Teléfono en campo legacy (TelSinUso); a veces coincide con Tel.',
                'sembrar' => false,
            ],
            'Domi' => [
                'clave' => 'clinica.domicilio',
                'descripcion' => 'Domicilio del consultorio / institución.',
                'sembrar' => true,
            ],
            'Locali' => [
                'clave' => 'clinica.localidad',
                'descripcion' => 'Localidad.',
                'sembrar' => true,
            ],
            'UltimoPaci' => [
                'clave' => 'exe._ultimo_id_paciente',
                'descripcion' => 'ID interno de último paciente en el exe (operativo; no suele usarse en web).',
                'sembrar' => false,
            ],
            'Especialidad' => [
                'clave' => 'clinica.especialidad_leyenda',
                'descripcion' => 'Texto de especialidad para encabezados.',
                'sembrar' => true,
            ],
            'docactivo' => [
                'clave' => 'exe.doc_activo',
                'descripcion' => 'Valor numérico interno del exe (contexto “doctor activo” / lista).',
                'sembrar' => false,
            ],
            'pasarahc' => [
                'clave' => 'exe.flag.pasar_a_hc',
                'descripcion' => 'Comportamiento al pasar a historia clínica (0/1).',
                'sembrar' => true,
            ],
            'Encabezado' => [
                'clave' => 'clinica.encabezado_impresion_texto_plano',
                'descripcion' => 'Encabezado de impreso en texto plano (saltos de línea).',
                'sembrar' => true,
            ],
            'cie10' => [
                'clave' => 'exe.flag.cie10',
                'descripcion' => 'Uso / visibilidad CIE-10 (0/1).',
                'sembrar' => true,
            ],
            'tipoespecialidadactiva' => [
                'clave' => 'exe.tipo_especialidad_activa',
                'descripcion' => 'Código de tipo de especialidad activa.',
                'sembrar' => false,
            ],
            'Tel' => [
                'clave' => 'clinica.telefono',
                'descripcion' => 'Teléfono principal.',
                'sembrar' => true,
            ],
            'encabezadocli' => [
                'clave' => 'clinica.encabezado_clinica_texto',
                'descripcion' => 'Encabezado institucional (ej. nombre fantasía + domicilio + tel).',
                'sembrar' => true,
            ],
            'licenciado' => [
                'clave' => 'exe.licenciado',
                'descripcion' => 'Campo “licenciado” del exe (entero).',
                'sembrar' => false,
            ],
            'ventanainicio' => [
                'clave' => 'exe.ventana_inicio',
                'descripcion' => 'Ventana o modo de inicio del programa.',
                'sembrar' => false,
            ],
            'agendarmotivo' => [
                'clave' => 'exe.flag.agendar_motivo',
                'descripcion' => 'Exigir motivo al agendar turno (0/1).',
                'sembrar' => true,
            ],
            'anuncio' => [
                'clave' => 'clinica.anuncio_texto',
                'descripcion' => 'Texto de anuncio (si el exe lo usa).',
                'sembrar' => true,
            ],
            'encabezadogeneral' => [
                'clave' => 'clinica.encabezado_general_rtf',
                'descripcion' => 'Encabezado general en RTF (WordPad). Revisar o convertir a HTML si se muestra en web.',
                'sembrar' => true,
            ],
            'encabezadopaciente' => [
                'clave' => 'clinica.encabezado_ficha_paciente_rtf',
                'descripcion' => 'Plantilla de ficha paciente en RTF (marcadores tipo <hc>).',
                'sembrar' => true,
            ],
            'hacerbackupautomatico' => [
                'clave' => 'exe.flag.backup_automatico',
                'descripcion' => 'Backup automático en el exe (0/1).',
                'sembrar' => true,
            ],
            'fondoaplicacion' => [
                'clave' => 'exe.color_fondo_aplicacion',
                'descripcion' => 'Color de fondo VB6 (Long); no aplica directo a web.',
                'sembrar' => false,
            ],
            'verotrasconsultas' => [
                'clave' => 'exe.flag.ver_otras_consultas',
                'descripcion' => 'Ver consultas de otros (0/1).',
                'sembrar' => true,
            ],
            'verfichareducida' => [
                'clave' => 'exe.flag.ver_ficha_reducida',
                'descripcion' => 'Ficha reducida (0/1).',
                'sembrar' => true,
            ],
            'verestadisticareducida' => [
                'clave' => 'exe.flag.ver_estadistica_reducida',
                'descripcion' => 'Estadística reducida (0/1).',
                'sembrar' => true,
            ],
            'forzarguardarpaci' => [
                'clave' => 'exe.flag.forzar_guardar_paciente',
                'descripcion' => 'Forzar guardado de paciente (entero pequeño).',
                'sembrar' => true,
            ],
            'tipoformunewpaci' => [
                'clave' => 'exe.tipo_form_nuevo_paciente',
                'descripcion' => 'Variante de formulario de alta de paciente.',
                'sembrar' => false,
            ],
            'forzarcantidadsesiones' => [
                'clave' => 'exe.flag.forzar_cantidad_sesiones',
                'descripcion' => 'Forzar cantidad de sesiones órdenes.',
                'sembrar' => true,
            ],
            'forzarpractica' => [
                'clave' => 'exe.flag.forzar_practica',
                'descripcion' => 'Forzar selección de práctica en órdenes.',
                'sembrar' => true,
            ],
            'sumfpp' => [
                'clave' => 'exe.sum_fpp_dias',
                'descripcion' => 'Suma FPP (días; campo clínico/config).',
                'sembrar' => true,
            ],
            'bloquearcostos' => [
                'clave' => 'exe.flag.bloquear_costos',
                'descripcion' => 'Bloquear edición de costos.',
                'sembrar' => true,
            ],
            'versionprograma' => [
                'clave' => 'exe.version_programa',
                'descripcion' => 'Versión del programa original (ej. 1.83.2). Solo referencia.',
                'sembrar' => true,
            ],
            'encabezadoimagen' => [
                'clave' => 'exe.flag.encabezado_imagen',
                'descripcion' => 'Encabezado con imagen (0/1).',
                'sembrar' => true,
            ],
            'forzarderivador' => [
                'clave' => 'exe.flag.forzar_derivador',
                'descripcion' => 'Forzar derivador en órdenes.',
                'sembrar' => true,
            ],
            'forzarmotivo' => [
                'clave' => 'exe.flag.forzar_motivo',
                'descripcion' => 'Forzar motivo (agenda/consulta).',
                'sembrar' => true,
            ],
            'primerdiaagendaturnos' => [
                'clave' => 'exe.primer_dia_agenda_turnos',
                'descripcion' => 'Día inicial de grilla de agenda.',
                'sembrar' => false,
            ],
            'pagocaja' => [
                'clave' => 'exe.flag.pago_caja',
                'descripcion' => 'Comportamiento de pago en caja.',
                'sembrar' => true,
            ],
            'versaldoanioactual' => [
                'clave' => 'exe.flag.ver_saldo_anio_actual',
                'descripcion' => 'Ver saldo del año actual.',
                'sembrar' => true,
            ],
            'forzarusuario' => [
                'clave' => 'exe.flag.forzar_usuario',
                'descripcion' => 'Forzar usuario en operaciones.',
                'sembrar' => true,
            ],
            'imprimirturnos' => [
                'clave' => 'exe.flag.imprimir_turnos',
                'descripcion' => 'Imprimir turnos.',
                'sembrar' => true,
            ],
            'nuidhc' => [
                'clave' => 'exe.nuidhc',
                'descripcion' => 'Parámetro interno números HC/ID.',
                'sembrar' => false,
            ],
            'selecusuario' => [
                'clave' => 'exe.flag.seleccionar_usuario',
                'descripcion' => 'Selección de usuario al iniciar.',
                'sembrar' => true,
            ],
            'actualizarstock' => [
                'clave' => 'exe.flag.actualizar_stock',
                'descripcion' => 'Actualización de stock.',
                'sembrar' => true,
            ],
            'forzarnumeorden' => [
                'clave' => 'exe.flag.forzar_numero_orden',
                'descripcion' => 'Forzar número de orden.',
                'sembrar' => true,
            ],
            'reemplazoid' => [
                'clave' => 'exe.reemplazo_id',
                'descripcion' => 'Texto de reemplazo de ID (legacy).',
                'sembrar' => false,
            ],
            'plantillaagenda' => [
                'clave' => 'exe.flag.plantilla_agenda',
                'descripcion' => 'Uso de plantilla en agenda.',
                'sembrar' => true,
            ],
            'prefijohc' => [
                'clave' => 'clinica.prefijo_hc',
                'descripcion' => 'Prefijo para número de historia clínica.',
                'sembrar' => true,
            ],
            'numerohc' => [
                'clave' => 'clinica.contador_hc_texto',
                'descripcion' => 'Contador / formato HC en el exe (texto).',
                'sembrar' => true,
            ],
            'encabezadoticket' => [
                'clave' => 'clinica.encabezado_ticket_rtf',
                'descripcion' => 'Ticket / recibo en RTF.',
                'sembrar' => true,
            ],
            'forzarguion' => [
                'clave' => 'exe.flag.forzar_guion',
                'descripcion' => 'Regla de guión en HC u órdenes.',
                'sembrar' => true,
            ],
            'forzarnopuntos' => [
                'clave' => 'exe.flag.forzar_no_puntos',
                'descripcion' => 'Restricción de puntos en texto (ej. DNI).',
                'sembrar' => true,
            ],
            'forzarusuarioclave' => [
                'clave' => 'exe.flag.forzar_usuario_clave',
                'descripcion' => 'Forzar usuario/clave.',
                'sembrar' => true,
            ],
            'tipohojarecibo' => [
                'clave' => 'exe.tipo_hoja_recibo',
                'descripcion' => 'Tipo de hoja de recibo.',
                'sembrar' => true,
            ],
            'imprimirturnostel' => [
                'clave' => 'exe.flag.imprimir_turnos_telefono',
                'descripcion' => 'Imprimir turnos con teléfono.',
                'sembrar' => true,
            ],
            'unidadtalla' => [
                'clave' => 'exe.unidad_talla',
                'descripcion' => 'Unidad de talla (0/1 codificado).',
                'sembrar' => false,
            ],
            'atenderconsesiones' => [
                'clave' => 'exe.flag.atender_con_sesiones',
                'descripcion' => 'Atender ligado a sesiones.',
                'sembrar' => true,
            ],
            'nomostrarsaldos' => [
                'clave' => 'exe.flag.no_mostrar_saldos',
                'descripcion' => 'Ocultar saldos.',
                'sembrar' => true,
            ],
            'titulomatricula' => [
                'clave' => 'clinica.titulo_matricula_etiqueta',
                'descripcion' => 'Etiqueta para matrícula (ej. “Matrícula”).',
                'sembrar' => true,
            ],
            'llegosolosecretarias' => [
                'clave' => 'exe.flag.llegada_solo_secretarias',
                'descripcion' => 'Marca de llegada solo secretaría.',
                'sembrar' => true,
            ],
            'impresoraticket' => [
                'clave' => 'hardware.impresora_ticket_windows',
                'descripcion' => 'Ruta UNC de impresora de tickets (Windows).',
                'sembrar' => true,
            ],
            'nomodificarhc' => [
                'clave' => 'exe.flag.no_modificar_hc',
                'descripcion' => 'Bloquear modificación de HC.',
                'sembrar' => true,
            ],
            'ExaFisiHC' => [
                'clave' => 'exe.flag.examen_fisico_hc',
                'descripcion' => 'Examen físico en HC.',
                'sembrar' => true,
            ],
            'imprimirturnosdomi' => [
                'clave' => 'exe.flag.imprimir_turnos_domicilio',
                'descripcion' => 'Imprimir turnos con domicilio.',
                'sembrar' => true,
            ],
            'forzarsucursalcaja' => [
                'clave' => 'exe.flag.forzar_sucursal_caja',
                'descripcion' => 'Forzar sucursal en caja.',
                'sembrar' => true,
            ],
            'altapaciweb' => [
                'clave' => 'exe.flag.alta_paciente_web',
                'descripcion' => 'Alta de paciente vía web (flag exe).',
                'sembrar' => true,
            ],
            'controlausencias' => [
                'clave' => 'exe.control_ausencias',
                'descripcion' => 'Días/modo de control de ausencias.',
                'sembrar' => true,
            ],
            'controldias' => [
                'clave' => 'exe.control_dias',
                'descripcion' => 'Ventana de días para control.',
                'sembrar' => true,
            ],
            'autoconfirwhatsapp' => [
                'clave' => 'exe.flag.auto_confirmar_whatsapp',
                'descripcion' => 'Auto-confirmación WhatsApp.',
                'sembrar' => true,
            ],
            'valoresdefecto' => [
                'clave' => 'exe.flag.valores_defecto',
                'descripcion' => 'Cargar valores por defecto.',
                'sembrar' => true,
            ],
            'honorcalcu' => [
                'clave' => 'exe.flag.honorarios_calculo',
                'descripcion' => 'Cálculo de honorarios.',
                'sembrar' => true,
            ],
            'noborraratendidos' => [
                'clave' => 'exe.flag.no_borrar_atendidos',
                'descripcion' => 'No borrar turnos atendidos.',
                'sembrar' => true,
            ],
            'atenderconhc' => [
                'clave' => 'exe.flag.atender_con_hc',
                'descripcion' => 'Atender exigiendo HC.',
                'sembrar' => true,
            ],
            'recargocredito' => [
                'clave' => 'finanzas.recargo_tarjeta_credito_pct',
                'descripcion' => 'Recargo % tarjeta crédito (exe).',
                'sembrar' => true,
            ],
            'recargodebito' => [
                'clave' => 'finanzas.recargo_tarjeta_debito_pct',
                'descripcion' => 'Recargo % tarjeta débito (exe).',
                'sembrar' => true,
            ],
            'recargomercadopago' => [
                'clave' => 'finanzas.recargo_mercadopago_pct',
                'descripcion' => 'Recargo % MercadoPago (exe).',
                'sembrar' => true,
            ],
            'calcuhonorsolocaja' => [
                'clave' => 'exe.flag.calcular_honorarios_solo_caja',
                'descripcion' => 'Calcular honorarios solo desde caja.',
                'sembrar' => true,
            ],
            'indicarcobercaja' => [
                'clave' => 'exe.flag.indicar_cobertura_caja',
                'descripcion' => 'Indicar cobertura en caja.',
                'sembrar' => true,
            ],
            'unidadpeso' => [
                'clave' => 'exe.unidad_peso',
                'descripcion' => 'Unidad de peso (codificado).',
                'sembrar' => false,
            ],
            'dondedesconstock' => [
                'clave' => 'exe.donde_descontar_stock',
                'descripcion' => 'Origen/dónde descontar stock.',
                'sembrar' => false,
            ],
        ];
    }

    public static function detectarTablaBackup(PDO $pdo): ?string
    {
        $st = $pdo->query(
            "SELECT table_name FROM information_schema.tables "
            . "WHERE table_schema = DATABASE() AND table_name LIKE 'backup_legacy_Config%' ORDER BY table_name LIMIT 1"
        );
        if (!$st) {
            return null;
        }
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ? (string) $r['table_name'] : null;
    }

    /**
     * Copia valores no vacíos desde la primera fila del backup legacy a `config` (upsert por clave).
     *
     * @return int Cantidad de claves escritas
     */
    public static function aplicarSembradoConfig(PDO $pdo): int
    {
        if (!db_table_exists($pdo, 'config')) {
            return 0;
        }
        $tabla = self::detectarTablaBackup($pdo);
        if ($tabla === null) {
            return 0;
        }
        $st = $pdo->query('SELECT * FROM `' . str_replace('`', '', $tabla) . '` LIMIT 1');
        if (!$st) {
            return 0;
        }
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 0;
        }
        $map = self::porColumna();
        $ins = $pdo->prepare(
            'INSERT INTO config (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        $n = 0;
        foreach ($map as $col => $meta) {
            if (empty($meta['sembrar']) || !array_key_exists($col, $row)) {
                continue;
            }
            $v = $row[$col];
            if ($v === null) {
                continue;
            }
            if (is_string($v) && trim($v) === '') {
                continue;
            }
            $str = is_scalar($v) ? (string) $v : '';
            $ins->execute([$meta['clave'], $str]);
            $n++;
        }

        return $n;
    }

    /**
     * @return array<string, string|null> primera fila legacy (columna => valor corto para vista)
     */
    public static function vistaPreviaLegacy(PDO $pdo, int $maxLen = 72): array
    {
        $tabla = self::detectarTablaBackup($pdo);
        if ($tabla === null) {
            return [];
        }
        $st = $pdo->query('SELECT * FROM `' . str_replace('`', '', $tabla) . '` LIMIT 1');
        if (!$st) {
            return [];
        }
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [];
        }
        $out = [];
        foreach ($row as $k => $v) {
            if ($v === null) {
                $out[$k] = null;
                continue;
            }
            $s = is_scalar($v) ? (string) $v : '';
            if (strlen($s) > $maxLen) {
                $s = substr($s, 0, $maxLen) . '…';
            }
            $out[$k] = $s;
        }

        return $out;
    }
}
