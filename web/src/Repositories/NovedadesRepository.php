<?php

declare(strict_types=1);

final class NovedadesRepository
{
    private PDO $pdo;
    private int $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = $idClinica > 0 ? $idClinica : 1;
    }

    public function tableExists(): bool
    {
        return db_table_exists($this->pdo, 'novedades_archivos');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listar(int $limit = 200): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $joinUsuarios = db_table_exists($this->pdo, 'usuarios');
        $sql = 'SELECT n.id, n.id_clinica, n.id_usuario, n.titulo, n.descripcion,
            n.nombre_original, n.ruta_relativa, n.mime, n.tamano_bytes, n.creado_en';
        if ($joinUsuarios) {
            $sql .= ', COALESCE(u.nombre, u.usuario, CONCAT(\'Usuario #\', n.id_usuario)) AS usuario_nombre';
        } else {
            $sql .= ', CONCAT(\'Usuario #\', n.id_usuario) AS usuario_nombre';
        }
        $sql .= ' FROM novedades_archivos n';
        if ($joinUsuarios) {
            $sql .= ' LEFT JOIN usuarios u ON u.id = n.id_usuario';
        }
        $sql .= ' WHERE n.id_clinica = ? ORDER BY n.creado_en DESC, n.id DESC LIMIT ' . $limit;
        $st = $this->pdo->prepare($sql);
        $st->execute([$this->idClinica]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        if ($id < 1 || !$this->tableExists()) {
            return null;
        }
        $st = $this->pdo->prepare(
            'SELECT id, id_clinica, id_usuario, titulo, descripcion, nombre_original,
                ruta_relativa, mime, tamano_bytes, creado_en
             FROM novedades_archivos
             WHERE id = ? AND id_clinica = ?
             LIMIT 1'
        );
        $st->execute([$id, $this->idClinica]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function insert(
        string $titulo,
        ?string $descripcion,
        string $nombreOriginal,
        string $rutaRelativa,
        ?string $mime,
        ?int $tamanoBytes,
        ?int $idUsuario
    ): int {
        if (!$this->tableExists()) {
            return 0;
        }
        $st = $this->pdo->prepare(
            'INSERT INTO novedades_archivos
                (id_clinica, id_usuario, titulo, descripcion, nombre_original, ruta_relativa, mime, tamano_bytes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $this->idClinica,
            ($idUsuario !== null && $idUsuario > 0) ? $idUsuario : null,
            $titulo,
            $descripcion !== null && trim($descripcion) !== '' ? trim($descripcion) : null,
            $nombreOriginal,
            $rutaRelativa,
            $mime,
            $tamanoBytes,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function deleteById(int $id): bool
    {
        if ($id < 1 || !$this->tableExists()) {
            return false;
        }
        $st = $this->pdo->prepare('DELETE FROM novedades_archivos WHERE id = ? AND id_clinica = ?');
        $st->execute([$id, $this->idClinica]);

        return $st->rowCount() > 0;
    }
}
