<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class LabConfigRepository
{
    /** @var array<string,?string> */
    private array $cache = [];
    private bool $cacheCompleto = false;

    public function __construct(private PDO $db)
    {
    }

    public function get(string $clave): ?string
    {
        if (array_key_exists($clave, $this->cache)) {
            return $this->cache[$clave];
        }

        $stmt = $this->db->prepare(
            'SELECT valor FROM lab_config WHERE clave = :clave LIMIT 1'
        );
        $stmt->execute([':clave' => $clave]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $valor = $row !== false ? (string) $row['valor'] : null;

        $this->cache[$clave] = $valor;
        return $valor;
    }

    /**
     * @param string[] $claves
     * @return array<string,?string>
     */
    public function getMany(array $claves): array
    {
        $resultado = [];
        foreach ($claves as $c) {
            $resultado[$c] = $this->get($c);
        }
        return $resultado;
    }

    /**
     * @return array<string,?string>
     */
    public function getAll(): array
    {
        if ($this->cacheCompleto) {
            return $this->cache;
        }

        $stmt = $this->db->prepare('SELECT clave, valor FROM lab_config');
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->cache = [];
        foreach ($rows as $r) {
            $this->cache[(string) $r['clave']] = isset($r['valor']) ? (string) $r['valor'] : null;
        }
        $this->cacheCompleto = true;
        return $this->cache;
    }

    public function set(string $clave, ?string $valor, ?string $descripcion = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO lab_config (clave, valor, descripcion)
             VALUES (:clave, :valor, :descripcion)
             ON DUPLICATE KEY UPDATE
                valor = VALUES(valor),
                descripcion = COALESCE(VALUES(descripcion), descripcion)'
        );
        $stmt->execute([
            ':clave' => $clave,
            ':valor' => $valor,
            ':descripcion' => $descripcion,
        ]);

        $this->cache[$clave] = $valor;
    }
}
