<?php

declare(strict_types=1);

/**
 * Tabla física: `Pacientes Ordenes` (nombre del backup / exe).
 */
final class OrdenesRepository
{
    private const TABLE = '`Pacientes Ordenes`';

    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function tableSqlName(): string
    {
        return 'Pacientes Ordenes';
    }

    /**
     * @param array<string, mixed> $f Filtros desde GET (ver OrdenesController::collectFiltrosOrdenes)
     * @return list<array<string, mixed>>
     */
    public function listForIndex(array $f): array
    {
        $hasApellido = db_table_has_column($this->pdo, 'pacientes', 'apellido');
        $colApellido = $hasApellido ? 'p.apellido AS paciente_apellido' : 'NULL AS paciente_apellido';
        $joinCob = db_table_exists($this->pdo, 'lista_coberturas');
        $selCob = $joinCob ? ', lc.nombre AS cobertura_nombre' : ', NULL AS cobertura_nombre';

        $sql = 'SELECT o.id, o.NroPaci, o.numero, o.iddoctor,
            DATE(o.fecha) AS fecha_orden,
            o.fecha AS fecha_hora,
            o.autorizada, o.entregada, o.observaciones,
            o.costo, o.pago, o.costo_os, o.sesiones, o.liquidada,
            o.estado, o.estado_os, o.sucursal, o.idobrasocial, o.idpractica,
            o.idderivado, o.idplan,
            d.nombre AS doctor_nombre,
            p.Nombres AS paciente_nombres, ' . $colApellido . $selCob . '
            FROM ' . self::TABLE . ' o
            LEFT JOIN lista_doctores d ON d.id = o.iddoctor
            LEFT JOIN pacientes p ON p.NroHC = o.NroPaci';
        if ($joinCob) {
            $sql .= ' LEFT JOIN lista_coberturas lc ON lc.id = o.idobrasocial';
        }
        $sql .= ' WHERE 1=1';
        $params = [];

        $nro = (int) ($f['nrohc'] ?? 0);
        if ($nro > 0) {
            $sql .= ' AND o.NroPaci = ?';
            $params[] = $nro;
        }
        $doc = (int) ($f['doctor'] ?? 0);
        if ($doc > 0) {
            $sql .= ' AND o.iddoctor = ?';
            $params[] = $doc;
        }
        $idD = (int) ($f['id_desde'] ?? 0);
        if ($idD > 0) {
            $sql .= ' AND o.id >= ?';
            $params[] = $idD;
        }
        $idH = (int) ($f['id_hasta'] ?? 0);
        if ($idH > 0) {
            $sql .= ' AND o.id <= ?';
            $params[] = $idH;
        }
        $fd = trim((string) ($f['fecha_desde'] ?? ''));
        if ($fd !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fd)) {
            $sql .= ' AND o.fecha >= ?';
            $params[] = $fd . ' 00:00:00';
        }
        $fh = trim((string) ($f['fecha_hasta'] ?? ''));
        if ($fh !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fh)) {
            $sql .= ' AND o.fecha <= ?';
            $params[] = $fh . ' 23:59:59';
        }
        foreach (
            [
                'sucursal' => 'sucursal',
                'idobrasocial' => 'idobrasocial',
                'idpractica' => 'idpractica',
                'idderivado' => 'idderivado',
                'idplan' => 'idplan',
            ] as $key => $col
        ) {
            $v = (int) ($f[$key] ?? 0);
            if ($v > 0) {
                $sql .= ' AND o.' . $col . ' = ?';
                $params[] = $v;
            }
        }
        $est = trim((string) ($f['estado'] ?? ''));
        if ($est !== '') {
            $sql .= ' AND o.estado <=> ?';
            $params[] = substr($est, 0, 1);
        }
        $estOs = trim((string) ($f['estado_os'] ?? ''));
        if ($estOs !== '') {
            $sql .= ' AND o.estado_os <=> ?';
            $params[] = substr($estOs, 0, 1);
        }
        self::appendTriState($sql, $params, 'o.autorizada', (string) ($f['autorizada'] ?? ''));
        self::appendTriState($sql, $params, 'o.entregada', (string) ($f['entregada'] ?? ''));
        self::appendTriState($sql, $params, 'o.liquidada', (string) ($f['liquidada'] ?? ''));

        $sql .= ' ORDER BY o.fecha IS NULL, o.fecha DESC, o.id DESC LIMIT 500';

        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    private static function appendTriState(string &$sql, array &$params, string $colExpr, string $v): void
    {
        if ($v === '' || $v === 'all') {
            return;
        }
        if ($v === '1') {
            $sql .= ' AND (' . $colExpr . ' = 1)';
            return;
        }
        if ($v === '0') {
            $sql .= ' AND (' . $colExpr . ' = 0 OR ' . $colExpr . ' IS NULL)';
        }
    }

    public function findById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch();

        return $r ?: null;
    }

    public function nroHcExists(int $nroHC): bool
    {
        $st = $this->pdo->prepare('SELECT id FROM pacientes WHERE NroHC = ? LIMIT 1');
        $st->execute([$nroHC]);
        return (bool) $st->fetch();
    }

    public function doctorExists(int $id): bool
    {
        $st = $this->pdo->prepare('SELECT id FROM lista_doctores WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return (bool) $st->fetch();
    }

    public function countSesionesByOrden(int $idOrden): int
    {
        if (!db_table_exists($this->pdo, 'pacientes_sesiones')) {
            return 0;
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) c FROM pacientes_sesiones WHERE idorden = ?');
        $st->execute([$idOrden]);

        return (int) $st->fetch()['c'];
    }

    public function insert(
        int $nroPaci,
        int $iddoctor,
        ?string $fechaOrden,
        int $autorizada,
        int $entregada,
        string $observaciones
    ): void {
        $fecha = self::fechaToDatetime($fechaOrden);
        $st = $this->pdo->prepare(
            'INSERT INTO ' . self::TABLE . ' (NroPaci, iddoctor, fecha, autorizada, entregada, observaciones)
            VALUES (?,?,?,?,?,?)'
        );
        $st->execute([$nroPaci, $iddoctor, $fecha, $autorizada, $entregada, $observaciones]);
    }

    public function update(
        int $id,
        int $nroPaci,
        int $iddoctor,
        ?string $fechaOrden,
        int $autorizada,
        int $entregada,
        string $observaciones
    ): void {
        $fecha = self::fechaToDatetime($fechaOrden);
        $st = $this->pdo->prepare(
            'UPDATE ' . self::TABLE . ' SET NroPaci=?, iddoctor=?, fecha=?, autorizada=?, entregada=?, observaciones=? WHERE id=?'
        );
        $st->execute([$nroPaci, $iddoctor, $fecha, $autorizada, $entregada, $observaciones, $id]);
    }

    public function deleteById(int $id): void
    {
        $st = $this->pdo->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = ?');
        $st->execute([$id]);
    }

    private static function fechaToDatetime(?string $fechaYmd): ?string
    {
        if ($fechaYmd === null || $fechaYmd === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaYmd)) {
            return $fechaYmd . ' 00:00:00';
        }

        return $fechaYmd;
    }
}
