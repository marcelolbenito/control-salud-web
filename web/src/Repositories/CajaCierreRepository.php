<?php

declare(strict_types=1);

final class CajaCierreRepository
{
    private const TABLE = 'caja_cierres';

    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    public function cierresTableExists(): bool
    {
        return db_table_exists($this->pdo, self::TABLE);
    }

    public function cajaTableExists(): bool
    {
        return db_table_exists($this->pdo, 'caja');
    }

    private function cajaTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'caja', 'id_clinica');
    }

    private function cierresTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, self::TABLE, 'id_clinica');
    }

    /**
     * @return array{ingresos:float,egresos:float,total:float,cantidad:int}
     */
    public function resumenMovimientos(string $fecha, string $turno): array
    {
        if (!$this->cajaTableExists() || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return ['ingresos' => 0.0, 'egresos' => 0.0, 'total' => 0.0, 'cantidad' => 0];
        }

        $sql = 'SELECT
                COALESCE(SUM(CASE WHEN importecaja >= 0 THEN importecaja ELSE 0 END), 0) AS ingresos,
                COALESCE(SUM(CASE WHEN importecaja < 0 THEN ABS(importecaja) ELSE 0 END), 0) AS egresos,
                COALESCE(SUM(importecaja), 0) AS total,
                COUNT(*) AS cantidad
            FROM caja
            WHERE fechacaja = ?';
        $params = [$fecha];
        if ($this->cajaTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        if ($turno !== 'dia' && db_table_has_column($this->pdo, 'caja', 'turnocaja')) {
            $sql .= ' AND LOWER(COALESCE(turnocaja, \'\')) = ?';
            $params[] = $turno;
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $r = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'ingresos' => (float) ($r['ingresos'] ?? 0),
            'egresos' => (float) ($r['egresos'] ?? 0),
            'total' => (float) ($r['total'] ?? 0),
            'cantidad' => (int) ($r['cantidad'] ?? 0),
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listMovimientos(string $fecha, string $turno): array
    {
        if (!$this->cajaTableExists() || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return [];
        }

        $joinCob = db_table_exists($this->pdo, 'lista_coberturas');
        $selCob = $joinCob ? ', lc.nombre AS cobertura_nombre' : ', NULL AS cobertura_nombre';
        $hasTurno = db_table_has_column($this->pdo, 'caja', 'turnocaja');
        $hasObs = db_table_has_column($this->pdo, 'caja', 'observaciones');
        $selTurno = $hasTurno ? 'c.turnocaja' : 'NULL AS turnocaja';
        $selObs = $hasObs ? 'c.observaciones' : 'NULL AS observaciones';
        $joinDoc = 'd.id = c.doctor';
        if ($this->cajaTieneClinica() && db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $joinDoc .= ' AND d.id_clinica = c.id_clinica';
        }

        $sql = 'SELECT c.id, c.doctor, c.fechacaja, c.importecaja, c.idcoberturacaja, '
            . $selTurno . ', ' . $selObs . ', d.nombre AS doctor_nombre' . $selCob . '
            FROM caja c
            LEFT JOIN lista_doctores d ON ' . $joinDoc;
        if ($joinCob) {
            $sql .= ' LEFT JOIN lista_coberturas lc ON lc.id = c.idcoberturacaja';
        }
        $sql .= ' WHERE c.fechacaja = ?';
        $params = [$fecha];
        if ($this->cajaTieneClinica()) {
            $sql .= ' AND c.id_clinica = ?';
            $params[] = $this->idClinica;
        }
        if ($turno !== 'dia' && $hasTurno) {
            $sql .= ' AND LOWER(COALESCE(c.turnocaja, \'\')) = ?';
            $params[] = $turno;
        }
        $sql .= ' ORDER BY c.id ASC';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findCierre(string $fecha, string $turno): ?array
    {
        if (!$this->cierresTableExists()) {
            return null;
        }

        $sql = 'SELECT * FROM ' . self::TABLE . ' WHERE fecha = ? AND turno = ?';
        $params = [$fecha, $turno];
        if ($this->cierresTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function ultimosCierres(int $limit = 15): array
    {
        if (!$this->cierresTableExists()) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $sql = 'SELECT c.*, u.usuario AS usuario_cierre
            FROM ' . self::TABLE . ' c
            LEFT JOIN usuarios u ON u.id = c.id_usuario_cierre';
        $params = [];
        if ($this->cierresTieneClinica()) {
            $sql .= ' WHERE c.id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $sql .= ' ORDER BY c.fecha DESC, c.cerrado_en DESC LIMIT ' . $limit;
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string,mixed> $values
     */
    public function guardarCierre(array $values): void
    {
        if (!$this->cierresTableExists()) {
            throw new RuntimeException('Falta la tabla caja_cierres.');
        }

        $values['id_clinica'] = $this->idClinica;
        $cols = [
            'id_clinica', 'fecha', 'turno', 'total_ingresos', 'total_egresos',
            'total_sistema', 'efectivo_declarado', 'diferencia', 'estado',
            'observaciones', 'id_usuario_cierre',
        ];
        $insertCols = [];
        $insertVals = [];
        foreach ($cols as $c) {
            if (!array_key_exists($c, $values) || !db_table_has_column($this->pdo, self::TABLE, $c)) {
                continue;
            }
            $insertCols[] = $c;
            $insertVals[] = $values[$c];
        }

        $updates = [];
        foreach ($insertCols as $c) {
            if (in_array($c, ['id_clinica', 'fecha', 'turno'], true)) {
                continue;
            }
            $updates[] = '`' . $c . '` = VALUES(`' . $c . '`)';
        }
        if (db_table_has_column($this->pdo, self::TABLE, 'cerrado_en')) {
            $updates[] = 'cerrado_en = CURRENT_TIMESTAMP';
        }

        $sql = 'INSERT INTO ' . self::TABLE . ' (`' . implode('`, `', $insertCols) . '`) VALUES ('
            . implode(', ', array_fill(0, count($insertCols), '?')) . ')
            ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
        $st = $this->pdo->prepare($sql);
        $st->execute($insertVals);
    }
}
