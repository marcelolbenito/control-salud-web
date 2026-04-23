<?php

declare(strict_types=1);

final class PagosRepository
{
    private const TABLE = 'pacientes_pagos';

    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    private function pagosTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, self::TABLE, 'id_clinica');
    }

    private function ordenesTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'Pacientes Ordenes', 'id_clinica');
    }

    public function tableExists(): bool
    {
        return db_table_exists($this->pdo, self::TABLE);
    }

    /**
     * @param array<string, string|int> $f
     * @return list<array<string, mixed>>
     */
    public function listForIndex(array $f): array
    {
        $joinOrd = 'o.id = p.idorden';
        if ($this->pagosTieneClinica() && $this->ordenesTieneClinica()) {
            $joinOrd .= ' AND o.id_clinica = p.id_clinica';
        }
        $joinDoc = 'd.id = o.iddoctor';
        if ($this->pagosTieneClinica() && db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $joinDoc .= ' AND d.id_clinica = p.id_clinica';
        }
        $sql = 'SELECT p.*,
            o.iddoctor AS orden_iddoctor,
            d.nombre AS doctor_nombre
            FROM ' . self::TABLE . ' p
            LEFT JOIN `Pacientes Ordenes` o ON ' . $joinOrd . '
            LEFT JOIN lista_doctores d ON ' . $joinDoc . '
            WHERE 1=1';
        $params = [];
        if ($this->pagosTieneClinica()) {
            $sql .= ' AND p.id_clinica = ?';
            $params[] = $this->idClinica;
        }

        $nro = (int) ($f['nrohc'] ?? 0);
        if ($nro > 0) {
            $sql .= ' AND p.NroPaci = ?';
            $params[] = $nro;
        }
        $idOrd = (int) ($f['idorden'] ?? 0);
        if ($idOrd > 0) {
            $sql .= ' AND p.idorden = ?';
            $params[] = $idOrd;
        }
        $quien = trim((string) ($f['quien'] ?? ''));
        if (in_array($quien, ['P', 'C', 'O'], true)) {
            $sql .= ' AND p.quien = ?';
            $params[] = $quien;
        }
        $fd = trim((string) ($f['fecha_desde'] ?? ''));
        if ($fd !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fd)) {
            $sql .= ' AND p.fecha >= ?';
            $params[] = $fd;
        }
        $fh = trim((string) ($f['fecha_hasta'] ?? ''));
        if ($fh !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fh)) {
            $sql .= ' AND p.fecha <= ?';
            $params[] = $fh;
        }

        $sql .= ' ORDER BY p.fecha DESC, p.id DESC LIMIT 1000';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . self::TABLE . ' WHERE id = ?';
        $par = [$id];
        if ($this->pagosTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    public function insert(array $vals): int
    {
        if ($this->pagosTieneClinica()) {
            $sql = 'INSERT INTO ' . self::TABLE . ' (id_clinica, quien, NroPaci, idorden, importe, fecha, forma_pago, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $st = $this->pdo->prepare($sql);
            $st->execute([
                $this->idClinica,
                $vals['quien'],
                $vals['NroPaci'],
                $vals['idorden'],
                $vals['importe'],
                $vals['fecha'],
                $vals['forma_pago'],
                $vals['observaciones'],
            ]);

            return (int) $this->pdo->lastInsertId();
        }
        $sql = 'INSERT INTO ' . self::TABLE . ' (quien, NroPaci, idorden, importe, fecha, forma_pago, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            $vals['quien'],
            $vals['NroPaci'],
            $vals['idorden'],
            $vals['importe'],
            $vals['fecha'],
            $vals['forma_pago'],
            $vals['observaciones'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $vals): void
    {
        $sql = 'UPDATE ' . self::TABLE . '
            SET quien = ?, NroPaci = ?, idorden = ?, importe = ?, fecha = ?, forma_pago = ?, observaciones = ?
            WHERE id = ?';
        $par = [
            $vals['quien'],
            $vals['NroPaci'],
            $vals['idorden'],
            $vals['importe'],
            $vals['fecha'],
            $vals['forma_pago'],
            $vals['observaciones'],
            $id,
        ];
        if ($this->pagosTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE id = ?';
        $par = [$id];
        if ($this->pagosTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public function pacienteExists(int $nroHc): bool
    {
        $sql = 'SELECT id FROM pacientes WHERE NroHC = ?';
        $par = [$nroHc];
        if (db_table_has_column($this->pdo, 'pacientes', 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);

        return (bool) $st->fetch();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findOrdenById(int $idOrden): ?array
    {
        $sql = 'SELECT id, NroPaci, iddoctor, idobrasocial FROM `Pacientes Ordenes` WHERE id = ?';
        $par = [$idOrden];
        if ($this->ordenesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    public function sumPagosPacienteByOrden(int $idOrden): float
    {
        $sql = "SELECT COALESCE(SUM(importe),0) AS t FROM " . self::TABLE . " WHERE idorden = ? AND quien = 'P'";
        $par = [$idOrden];
        if ($this->pagosTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return (float) ($r['t'] ?? 0);
    }

    public function updatePagoOrden(int $idOrden, float $nuevoPago): void
    {
        $sql = 'UPDATE `Pacientes Ordenes` SET pago = ? WHERE id = ?';
        $par = [$nuevoPago, $idOrden];
        if ($this->ordenesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }
}

