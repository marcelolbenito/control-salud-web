<?php

declare(strict_types=1);

final class AgendaBloqueosRepository
{
    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    public function tableExists(): bool
    {
        return db_table_exists($this->pdo, 'agenda_bloqueos');
    }

    private function bloqueosTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'agenda_bloqueos', 'id_clinica');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForIndex(?string $desde, ?string $hasta, int $doctor): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $desde = $desde !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ? $desde : date('Y-m-d', strtotime('-7 days'));
        $hasta = $hasta !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) ? $hasta : date('Y-m-d', strtotime('+60 days'));

        $sql = 'SELECT b.id, b.doctor, b.fecha_desde, b.fecha_hasta, b.hora_desde, b.hora_hasta, b.motivo, b.creado_en,
                       d.nombre AS doctor_nombre
                FROM agenda_bloqueos b
                INNER JOIN lista_doctores d ON d.id = b.doctor
                WHERE b.fecha_hasta >= ? AND b.fecha_desde <= ?';
        $par = [$desde, $hasta];
        if ($this->bloqueosTieneClinica()) {
            $sql .= ' AND b.id_clinica = ?';
            $par[] = $this->idClinica;
        }
        if ($doctor > 0) {
            $sql .= ' AND b.doctor = ?';
            $par[] = $doctor;
        }
        if (db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $sql .= ' AND d.id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' ORDER BY b.fecha_desde ASC, b.doctor ASC, b.id ASC';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        if (!$this->tableExists() || $id < 1) {
            return null;
        }
        $sql = 'SELECT * FROM agenda_bloqueos WHERE id = ?';
        $par = [$id];
        if ($this->bloqueosTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    public function insert(int $doctor, string $fd, string $fh, ?string $horaDesde, ?string $horaHasta, ?string $motivo): void
    {
        if ($this->bloqueosTieneClinica()) {
            $st = $this->pdo->prepare(
                'INSERT INTO agenda_bloqueos (id_clinica, doctor, fecha_desde, fecha_hasta, hora_desde, hora_hasta, motivo)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $st->execute([$this->idClinica, $doctor, $fd, $fh, $horaDesde, $horaHasta, $motivo !== '' ? $motivo : null]);

            return;
        }
        $st = $this->pdo->prepare(
            'INSERT INTO agenda_bloqueos (doctor, fecha_desde, fecha_hasta, hora_desde, hora_hasta, motivo)
             VALUES (?,?,?,?,?,?)'
        );
        $st->execute([$doctor, $fd, $fh, $horaDesde, $horaHasta, $motivo !== '' ? $motivo : null]);
    }

    public function update(
        int $id,
        int $doctor,
        string $fd,
        string $fh,
        ?string $horaDesde,
        ?string $horaHasta,
        ?string $motivo
    ): void {
        $sql = 'UPDATE agenda_bloqueos SET doctor=?, fecha_desde=?, fecha_hasta=?, hora_desde=?, hora_hasta=?, motivo=? WHERE id=?';
        $par = [$doctor, $fd, $fh, $horaDesde, $horaHasta, $motivo !== '' ? $motivo : null, $id];
        if ($this->bloqueosTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public function deleteById(int $id): void
    {
        if ($id < 1) {
            return;
        }
        $sql = 'DELETE FROM agenda_bloqueos WHERE id = ?';
        $par = [$id];
        if ($this->bloqueosTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }
}
