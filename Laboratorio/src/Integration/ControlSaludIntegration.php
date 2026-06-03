<?php

declare(strict_types=1);

namespace App\Integration;

use PDO;

/**
 * Lectura de pacientes / profesionales sobre el esquema Control Salud Web.
 * Activar con LAB_INTEGRATION=control_salud en Laboratorio/.env
 * Desactivar (o borrar la variable) para volver al contrato original del módulo.
 */
final class ControlSaludIntegration
{
    private static ?PDO $db = null;

    /** @var array<string, true>|null */
    private static ?array $pacienteColumns = null;

    public static function bindDb(PDO $db): void
    {
        self::$db = $db;
        self::$pacienteColumns = null;
    }

    public static function enabled(): bool
    {
        return strtolower(trim((string) ($_ENV['LAB_INTEGRATION'] ?? ''))) === 'control_salud';
    }

    public static function clinicaId(): int
    {
        if (isset($_SESSION['lab_id_clinica'])) {
            $n = (int) $_SESSION['lab_id_clinica'];

            return $n > 0 ? $n : 1;
        }

        $n = (int) ($_ENV['LAB_CLINICA_ID'] ?? 1);

        return $n > 0 ? $n : 1;
    }

    public static function pacienteHasColumn(string $column): bool
    {
        self::loadPacienteColumns();

        return isset(self::$pacienteColumns[strtolower($column)]);
    }

    /** @return array<string, string> */
    public static function pacienteOrdenes(): array
    {
        $apellido = self::pacienteHasColumn('apellido') ? 'p.apellido' : "''";

        return [
            'apellido_asc'  => "{$apellido} ASC, p.Nombres ASC",
            'apellido_desc' => "{$apellido} DESC, p.Nombres DESC",
            'nombres_asc'   => 'p.Nombres ASC, ' . ($apellido === "''" ? 'p.Nombres' : 'p.apellido') . ' ASC',
            'nombres_desc'  => 'p.Nombres DESC, ' . ($apellido === "''" ? 'p.Nombres' : 'p.apellido') . ' DESC',
            'nro_hc_asc'    => 'p.NroHC ASC',
            'nro_hc_desc'   => 'p.NroHC DESC',
            'dni_asc'       => 'p.DNI ASC',
            'dni_desc'      => 'p.DNI DESC',
        ];
    }

    public static function pacienteSelectSql(): string
    {
        $apellido = self::pacienteHasColumn('apellido')
            ? "COALESCE(NULLIF(TRIM(p.apellido), ''), '')"
            : "''";

        $sexo = self::pacienteHasColumn('sexo')
            ? <<<'SQL'
                CASE UPPER(LEFT(TRIM(COALESCE(CAST(p.sexo AS CHAR), '')), 1))
                    WHEN 'M' THEN 'M'
                    WHEN 'F' THEN 'F'
                    ELSE 'X'
                END
                SQL
            : "'X'";

        if (self::pacienteHasColumn('id_cobertura')) {
            $obraId = 'NULLIF(p.id_cobertura, 0)';
            $nroAfiliado = self::pacienteHasColumn('nro_os')
                ? "NULLIF(TRIM(p.nro_os), '')"
                : 'NULL';
            $obraNombre = 'os.nombre';
        } else {
            $obraId = 'NULL';
            $nroAfiliado = 'NULL';
            $obraNombre = 'NULL';
        }

        return <<<SQL
            SELECT p.id,
                   CAST(p.NroHC AS CHAR) AS nro_hc,
                   COALESCE(NULLIF(TRIM(p.DNI), ''), '') AS dni,
                   {$apellido} AS apellido,
                   COALESCE(NULLIF(TRIM(p.Nombres), ''), '') AS nombres,
                   NULLIF(TRIM(p.telefono), '') AS telefono,
                   DATE(p.fecha_nacimiento) AS fecha_nacimiento,
                   {$sexo} AS sexo,
                   {$obraId} AS obra_social_id,
                   {$nroAfiliado} AS nro_afiliado,
                   {$obraNombre} AS obra_social_nombre

            SQL;
    }

    public static function pacienteFromSql(): string
    {
        $cid = self::clinicaId();
        $join = self::pacienteHasColumn('id_cobertura')
            ? 'LEFT JOIN lista_coberturas os ON os.id = p.id_cobertura'
            : '';

        return <<<SQL

            FROM pacientes p
            {$join}
            WHERE p.id_clinica = {$cid}
              AND COALESCE(p.activo, 1) = 1
            SQL;
    }

    /** @return list<string> */
    public static function pacienteBusquedaRapidaClauses(): array
    {
        $parts = [
            'p.DNI LIKE :q1',
            'p.Nombres LIKE :q3',
            'CAST(p.NroHC AS CHAR) LIKE :q4',
        ];
        if (self::pacienteHasColumn('apellido')) {
            array_splice($parts, 1, 0, ['p.apellido LIKE :q2']);
        }

        return $parts;
    }

    public static function medicoSelectSql(): string
    {
        return <<<'SQL'
            SELECT d.id,
                   TRIM(SUBSTRING_INDEX(CONCAT(TRIM(COALESCE(d.nombre, '')), ' '), ' ', 1)) AS apellido,
                   TRIM(
                       IF(
                           LOCATE(' ', TRIM(COALESCE(d.nombre, ''))) > 0,
                           SUBSTRING(TRIM(d.nombre), LOCATE(' ', TRIM(d.nombre)) + 1),
                           ''
                       )
                   ) AS nombres,
                   COALESCE(NULLIF(TRIM(d.matricula), ''), CONCAT('ID-', d.id)) AS matricula,
                   NULLIF(TRIM(d.especialidad), '') AS especialidad,
                   NULLIF(TRIM(d.telefono), '') AS telefono,
                   NULL AS email,
                   COALESCE(d.activo, 1) AS activo
            SQL . "\n";
    }

    public static function medicoFromSql(): string
    {
        $cid = self::clinicaId();

        return <<<SQL

            FROM lista_doctores d
            WHERE d.id_clinica = {$cid}
            SQL;
    }

    /** JOINs para listado de lab_pedidos (paciente + cobertura). */
    public static function pedidoListJoinsSql(): string
    {
        if (!self::enabled()) {
            return <<<'SQL'
                LEFT JOIN pacientes pac ON pac.id = p.paciente_id
                LEFT JOIN obras_sociales os ON os.id = p.obra_social_id
                SQL;
        }

        $cid = self::clinicaId();

        return <<<SQL
            LEFT JOIN pacientes pac ON pac.id = p.paciente_id AND pac.id_clinica = {$cid}
            LEFT JOIN lista_coberturas os ON os.id = p.obra_social_id
            SQL;
    }

    /** Columnas de paciente en SELECT del listado de pedidos. */
    public static function pedidoListPacienteSelectSql(string $pacAlias = 'pac'): string
    {
        if (!self::enabled()) {
            return "{$pacAlias}.nro_hc AS paciente_nro_hc,
                    {$pacAlias}.apellido AS paciente_apellido,
                    {$pacAlias}.nombres AS paciente_nombres";
        }

        $apellido = self::pacienteHasColumn('apellido')
            ? "COALESCE(NULLIF(TRIM({$pacAlias}.apellido), ''), '')"
            : "''";

        return "CAST({$pacAlias}.NroHC AS CHAR) AS paciente_nro_hc,
                {$apellido} AS paciente_apellido,
                COALESCE(NULLIF(TRIM({$pacAlias}.Nombres), ''), '') AS paciente_nombres";
    }

    /**
     * Nombre/DNI visibles en listado: prioriza snapshot del pedido.
     */
    public static function pedidoListSnapshotSelectSql(string $pacAlias = 'pac'): string
    {
        if (!self::enabled()) {
            return "COALESCE(
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente, '$.nombre')), ''),
                    NULLIF(TRIM(CONCAT_WS(', ', {$pacAlias}.apellido, {$pacAlias}.nombres)), '')
                ) AS paciente_nombre,
                JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente, '$.dni')) AS paciente_dni";
        }

        $apellido = self::pacienteHasColumn('apellido')
            ? "COALESCE(NULLIF(TRIM({$pacAlias}.apellido), ''), '')"
            : "''";
        $nombres = "COALESCE(NULLIF(TRIM({$pacAlias}.Nombres), ''), '')";

        return "COALESCE(
                    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente, '$.nombre')), ''),
                    NULLIF(TRIM(CONCAT_WS(', ', {$apellido}, {$nombres})), '')
                ) AS paciente_nombre,
                JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente, '$.dni')) AS paciente_dni";
    }

    /** Cláusulas LIKE de búsqueda rápida sobre snapshot (además de paciente join). */
    public static function pedidoBuscarSnapshotClauses(): array
    {
        return [
            "JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente, '$.nombre')) LIKE :q_snap_nom",
            "JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente, '$.dni')) LIKE :q_snap_dni",
        ];
    }

    /** Nombre de obra social en JOIN de pedidos. */
    public static function pedidoObraSocialNombreSql(): string
    {
        return self::enabled() ? 'os.nombre' : 'os.nombre';
    }

    private static function loadPacienteColumns(): void
    {
        if (self::$pacienteColumns !== null) {
            return;
        }

        self::$pacienteColumns = [];
        if (self::$db === null) {
            return;
        }

        $stmt = self::$db->query('SHOW COLUMNS FROM pacientes');
        if ($stmt === false) {
            return;
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $field = $row['Field'] ?? '';
            if ($field !== '') {
                self::$pacienteColumns[strtolower((string) $field)] = true;
            }
        }
    }
}
