<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Catalog/CatalogRegistry.php';

final class CatalogosListaRepository
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function tablaExiste(string $tabla): bool
    {
        return db_table_exists($this->pdo, $tabla);
    }

    /**
     * Defensa en profundidad: verifica que $tabla esté en el whitelist de CatalogRegistry
     * antes de interpolarlo en SQL. Los controllers ya validan esto, pero el repo también
     * lo verifica para proteger contra uso incorrecto de la clase.
     */
    private function assertTableAllowed(string $tabla): void
    {
        if (CatalogRegistry::get($tabla) === null) {
            throw new \InvalidArgumentException("Tabla de catálogo no permitida: $tabla");
        }
    }

    public function contarRegistros(string $tabla): int
    {
        $this->assertTableAllowed($tabla);
        $st = $this->pdo->query("SELECT COUNT(*) AS c FROM `$tabla`");
        $row = $st ? $st->fetch(PDO::FETCH_ASSOC) : null;

        return (int) ($row['c'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listar(string $tabla, string $orden): array
    {
        $this->assertTableAllowed($tabla);
        $orderSql = $orden === 'prioridad_id'
            ? 'prioridad IS NULL, prioridad, nombre, id'
            : 'nombre, id';
        $st = $this->pdo->query("SELECT * FROM `$tabla` ORDER BY $orderSql");

        return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $tabla, int $id): ?array
    {
        $this->assertTableAllowed($tabla);
        $st = $this->pdo->prepare("SELECT * FROM `$tabla` WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function nextId(string $tabla): int
    {
        $this->assertTableAllowed($tabla);
        $st = $this->pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS n FROM `$tabla`");
        $row = $st ? $st->fetch(PDO::FETCH_ASSOC) : null;

        return (int) ($row['n'] ?? 1);
    }

    /**
     * @param array<string, mixed> $valores column => valor normalizado (sin id)
     */
    public function insert(string $tabla, array $valores): int
    {
        $this->assertTableAllowed($tabla);
        $id = $this->nextId($tabla);
        $cols = array_merge(['id'], array_keys($valores));
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $colSql = implode(', ', array_map(static function (string $c): string {
            return "`$c`";
        }, $cols));
        $params = array_merge([$id], array_values($valores));
        $sql = "INSERT INTO `$tabla` ($colSql) VALUES ($placeholders)";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $id;
    }

    /**
     * @param array<string, mixed> $valores
     */
    public function update(string $tabla, int $id, array $valores): void
    {
        $this->assertTableAllowed($tabla);
        if ($valores === []) {
            return;
        }
        $sets = [];
        $params = [];
        foreach ($valores as $col => $v) {
            $sets[] = "`$col` = ?";
            $params[] = $v;
        }
        $params[] = $id;
        $sql = 'UPDATE `' . $tabla . '` SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function deleteById(string $tabla, int $id): void
    {
        $this->assertTableAllowed($tabla);
        $st = $this->pdo->prepare("DELETE FROM `$tabla` WHERE id = ?");
        $st->execute([$id]);
    }
}
