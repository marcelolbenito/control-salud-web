<?php

declare(strict_types=1);

final class DoctoresRepository
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hasExtendedColumns(): bool
    {
        return db_table_has_column($this->pdo, 'lista_doctores', 'especialidad');
    }

    public function listForIndex(bool $extDoc): array
    {
        $sel = $extDoc
            ? 'SELECT id, nombre, especialidad, matricula, telefono, medicoconvenio, activo FROM lista_doctores ORDER BY nombre ASC LIMIT 500'
            : 'SELECT id, nombre, medicoconvenio, activo FROM lista_doctores ORDER BY nombre ASC LIMIT 500';

        return $this->pdo->query($sel)->fetchAll();
    }

    public function listActivos(): array
    {
        return $this->pdo->query(
            'SELECT id, nombre FROM lista_doctores WHERE activo = 1 ORDER BY nombre ASC'
        )->fetchAll();
    }

    public function listAllOrdered(): array
    {
        return $this->pdo->query('SELECT id, nombre FROM lista_doctores ORDER BY nombre ASC')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM lista_doctores WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function deleteById(int $id): void
    {
        $st = $this->pdo->prepare('DELETE FROM lista_doctores WHERE id = ?');
        $st->execute([$id]);
    }

    public function insertExtended(
        string $nombre,
        int $medicoconvenio,
        int $bloquearmisconsultas,
        int $activo,
        string $notas,
        string $especialidad,
        string $matricula,
        string $telefono,
        string $domicilio,
        string $localidad,
        string $consultorio
    ): void {
        $st = $this->pdo->prepare(
            'INSERT INTO lista_doctores (nombre, medicoconvenio, bloquearmisconsultas, activo, notas,
            especialidad, matricula, telefono, domicilio, localidad, consultorio)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas,
            $especialidad, $matricula, $telefono, $domicilio, $localidad, $consultorio,
        ]);
    }

    public function updateExtended(
        int $id,
        string $nombre,
        int $medicoconvenio,
        int $bloquearmisconsultas,
        int $activo,
        string $notas,
        string $especialidad,
        string $matricula,
        string $telefono,
        string $domicilio,
        string $localidad,
        string $consultorio
    ): void {
        $st = $this->pdo->prepare(
            'UPDATE lista_doctores SET nombre=?, medicoconvenio=?, bloquearmisconsultas=?, activo=?, notas=?,
            especialidad=?, matricula=?, telefono=?, domicilio=?, localidad=?, consultorio=?
            WHERE id=?'
        );
        $st->execute([
            $nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas,
            $especialidad, $matricula, $telefono, $domicilio, $localidad, $consultorio,
            $id,
        ]);
    }

    public function insertBase(
        string $nombre,
        int $medicoconvenio,
        int $bloquearmisconsultas,
        int $activo,
        string $notas
    ): void {
        $st = $this->pdo->prepare(
            'INSERT INTO lista_doctores (nombre, medicoconvenio, bloquearmisconsultas, activo, notas) VALUES (?,?,?,?,?)'
        );
        $st->execute([$nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas]);
    }

    public function updateBase(
        int $id,
        string $nombre,
        int $medicoconvenio,
        int $bloquearmisconsultas,
        int $activo,
        string $notas
    ): void {
        $st = $this->pdo->prepare(
            'UPDATE lista_doctores SET nombre=?, medicoconvenio=?, bloquearmisconsultas=?, activo=?, notas=? WHERE id=?'
        );
        $st->execute([$nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas, $id]);
    }
}

