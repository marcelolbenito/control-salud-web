<?php

declare(strict_types=1);

require_once __DIR__ . '/OrdenesRepository.php';

final class OdontogramaRepository
{
    private const TABLE = 'pacientes_odontograma';

    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    private function odoTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, self::TABLE, 'id_clinica');
    }

    private function supTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, self::TABLE_SUPERFICIES, 'id_clinica');
    }

    private function ordenesTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'Pacientes Ordenes', 'id_clinica');
    }

    public static function tableName(): string
    {
        return self::TABLE;
    }

    private const TABLE_SUPERFICIES = 'pacientes_odontograma_superficies';

    public function tablaExiste(): bool
    {
        return db_table_exists($this->pdo, self::TABLE);
    }

    public function tablaSuperficiesExiste(): bool
    {
        return db_table_exists($this->pdo, self::TABLE_SUPERFICIES);
    }

    /** Columnas de migration_015 (vínculo orden + anulación). */
    public function tieneExtensionV2(): bool
    {
        return db_table_has_column($this->pdo, self::TABLE, 'id_orden');
    }

    /**
     * @return list<int>
     */
    public static function piezasFdiPermitidas(): array
    {
        $out = [];
        foreach ([[11, 18], [21, 28], [31, 38], [41, 48], [51, 55], [61, 65], [71, 75], [81, 85]] as $r) {
            for ($i = $r[0]; $i <= $r[1]; $i++) {
                $out[] = $i;
            }
        }

        return $out;
    }

    public static function piezaFdiValida(int $pieza): bool
    {
        return in_array($pieza, self::piezasFdiPermitidas(), true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listCodigos(): array
    {
        if (!db_table_exists($this->pdo, 'lista_odontograma_codigos')) {
            return [];
        }
        try {
            $hasColor = db_table_has_column($this->pdo, 'lista_odontograma_codigos', 'color_hex');
            $hasOverlay = db_table_has_column($this->pdo, 'lista_odontograma_codigos', 'mapa_overlay');
            $extra = ($hasColor ? ', color_hex' : ', NULL AS color_hex') . ($hasOverlay ? ', mapa_overlay' : ', NULL AS mapa_overlay');
            $st = $this->pdo->query(
                'SELECT id, prioridad, codigo, nombre' . $extra . ' FROM lista_odontograma_codigos ORDER BY prioridad IS NULL, prioridad, nombre, id'
            );

            return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return list<array{pieza_fdi:int, cara:string, id_codigo:int, color_hex:?string, mapa_overlay:?string}>
     */
    public function listSuperficiesParaMapa(int $nroHC): array
    {
        if ($nroHC < 1 || !$this->tablaSuperficiesExiste()) {
            return [];
        }
        $hasColor = db_table_has_column($this->pdo, 'lista_odontograma_codigos', 'color_hex');
        $hasOverlay = db_table_has_column($this->pdo, 'lista_odontograma_codigos', 'mapa_overlay');
        $col = $hasColor ? 'c.color_hex' : 'NULL AS color_hex';
        $ov = $hasOverlay ? 'c.mapa_overlay' : 'NULL AS mapa_overlay';
        $sql = 'SELECT s.pieza_fdi, s.cara, s.id_codigo, ' . $col . ', ' . $ov . '
            FROM ' . self::TABLE_SUPERFICIES . ' s
            INNER JOIN lista_odontograma_codigos c ON c.id = s.id_codigo
            WHERE s.NroHC = ?';
        $par = [$nroHC];
        if ($this->supTieneClinica()) {
            $sql .= ' AND s.id_clinica = ?';
            $par[] = $this->idClinica;
        }

        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($par);

            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param list<array{pieza_fdi:int, cara:string, id_codigo:int}> $celdas
     */
    public function guardarSuperficiesMapa(int $nroHC, array $celdas, ?int $idUsuarioWeb): void
    {
        if ($nroHC < 1 || !$this->tablaSuperficiesExiste()) {
            throw new InvalidArgumentException('Mapa de superficies no disponible.');
        }
        $permitidas = array_flip(self::piezasFdiPermitidas());
        $carasOk = ['M' => true, 'O' => true, 'D' => true, 'V' => true, 'L' => true, 'P' => true];
        $this->pdo->beginTransaction();
        try {
            if ($this->supTieneClinica()) {
                $del = $this->pdo->prepare('DELETE FROM ' . self::TABLE_SUPERFICIES . ' WHERE NroHC = ? AND id_clinica = ?');
                $del->execute([$nroHC, $this->idClinica]);
                $ins = $this->pdo->prepare(
                    'INSERT INTO ' . self::TABLE_SUPERFICIES . ' (id_clinica, NroHC, pieza_fdi, cara, id_codigo, idusuario_web) VALUES (?,?,?,?,?,?)'
                );
            } else {
                $del = $this->pdo->prepare('DELETE FROM ' . self::TABLE_SUPERFICIES . ' WHERE NroHC = ?');
                $del->execute([$nroHC]);
                $ins = $this->pdo->prepare(
                    'INSERT INTO ' . self::TABLE_SUPERFICIES . ' (NroHC, pieza_fdi, cara, id_codigo, idusuario_web) VALUES (?,?,?,?,?)'
                );
            }
            foreach ($celdas as $c) {
                $pieza = (int) ($c['pieza_fdi'] ?? 0);
                $cara = strtoupper(trim((string) ($c['cara'] ?? '')));
                $idc = (int) ($c['id_codigo'] ?? 0);
                if (!isset($permitidas[$pieza]) || !isset($carasOk[$cara]) || $idc < 1) {
                    continue;
                }
                if ($this->supTieneClinica()) {
                    $ins->execute([$this->idClinica, $nroHC, $pieza, $cara, $idc, $idUsuarioWeb]);
                } else {
                    $ins->execute([$nroHC, $pieza, $cara, $idc, $idUsuarioWeb]);
                }
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByNroHC(int $nroHC): array
    {
        $ext = $this->tieneExtensionV2();
        $sel = 'o.id, o.NroHC, o.pieza_fdi, o.cara, o.id_codigo, o.notas, o.iddoctor, o.idusuario_web, o.creado_en,
            c.codigo AS codigo_simbolo, c.nombre AS codigo_nombre,
            d.nombre AS doctor_nombre,
            u.usuario AS usuario_web';
        $join = '';
        if ($ext) {
            $sel .= ', o.id_orden, o.anulado, o.anulado_motivo, o.anulado_en, o.anulado_por_usuario';
            if (db_table_exists($this->pdo, OrdenesRepository::tableSqlName())) {
                $sel .= ', DATE(ord.fecha) AS orden_fecha';
                $joinOrd = 'ord.id = o.id_orden';
                if ($this->odoTieneClinica() && $this->ordenesTieneClinica()) {
                    $joinOrd .= ' AND ord.id_clinica = o.id_clinica';
                }
                $join .= ' LEFT JOIN `Pacientes Ordenes` ord ON ' . $joinOrd;
            } else {
                $sel .= ', NULL AS orden_fecha';
            }
            $sel .= ', ua.usuario AS anulado_usuario';
            $join .= ' LEFT JOIN usuarios ua ON ua.id = o.anulado_por_usuario';
        } else {
            $sel .= ', NULL AS id_orden, NULL AS orden_fecha, 0 AS anulado, NULL AS anulado_motivo, NULL AS anulado_en, NULL AS anulado_por_usuario, NULL AS anulado_usuario';
        }

        $joinDoc = 'd.id = o.iddoctor';
        if ($this->odoTieneClinica() && db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $joinDoc .= ' AND d.id_clinica = o.id_clinica';
        }
        $sql = 'SELECT ' . $sel . '
            FROM ' . self::TABLE . ' o
            LEFT JOIN lista_odontograma_codigos c ON c.id = o.id_codigo
            LEFT JOIN lista_doctores d ON ' . $joinDoc . '
            LEFT JOIN usuarios u ON u.id = o.idusuario_web'
            . $join . '
            WHERE o.NroHC = ?';
        $par = [$nroHC];
        if ($this->odoTieneClinica()) {
            $sql .= ' AND o.id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= '
            ORDER BY o.creado_en DESC, o.id DESC';

        $st = $this->pdo->prepare($sql);
        $st->execute($par);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . self::TABLE . ' WHERE id = ?';
        $par = [$id];
        if ($this->odoTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    public function insert(
        int $nroHC,
        int $piezaFdi,
        ?string $cara,
        ?int $idCodigo,
        string $notas,
        ?int $iddoctor,
        ?int $idUsuarioWeb,
        ?int $idOrden
    ): void {
        $cols = ['NroHC', 'pieza_fdi', 'cara', 'id_codigo', 'notas', 'iddoctor', 'idusuario_web'];
        $vals = [$nroHC, $piezaFdi, $cara, $idCodigo, $notas !== '' ? $notas : null, $iddoctor, $idUsuarioWeb];
        if ($this->odoTieneClinica()) {
            array_unshift($cols, 'id_clinica');
            array_unshift($vals, $this->idClinica);
        }

        if ($this->tieneExtensionV2()) {
            $cols[] = 'id_orden';
            $vals[] = $idOrden !== null && $idOrden > 0 ? $idOrden : null;
        }

        $colSql = implode(', ', array_map(static function (string $c): string {
            return '`' . str_replace('`', '', $c) . '`';
        }, $cols));
        $ph = implode(', ', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO ' . self::TABLE . ' (' . $colSql . ') VALUES (' . $ph . ')';
        $st = $this->pdo->prepare($sql);
        $st->execute($vals);
    }

    public function anular(int $id, int $nroHC, string $motivo, int $idUsuarioWeb): bool
    {
        if (!$this->tieneExtensionV2() || $id < 1 || $nroHC < 1 || trim($motivo) === '' || $idUsuarioWeb < 1) {
            return false;
        }
        $sql = 'UPDATE ' . self::TABLE . ' SET anulado = 1, anulado_motivo = ?, anulado_en = NOW(), anulado_por_usuario = ?
            WHERE id = ? AND NroHC = ? AND (anulado = 0 OR anulado IS NULL)';
        $par = [$motivo, $idUsuarioWeb, $id, $nroHC];
        if ($this->odoTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);

        return $st->execute($par) && $st->rowCount() > 0;
    }

    /**
     * @return array<string, string> clave "pieza-cara" => #hex
     */
    public function mapaSuperficiesColoresPorNroHC(int $nroHC): array
    {
        $out = [];
        foreach ($this->listSuperficiesParaMapa($nroHC) as $r) {
            $p = (int) ($r['pieza_fdi'] ?? 0);
            $c = strtoupper(trim((string) ($r['cara'] ?? '')));
            $hex = trim((string) ($r['color_hex'] ?? ''));
            if ($p < 1 || $c === '') {
                continue;
            }
            if ($hex === '' || !preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
                $hex = '#e2e8f0';
            }
            $out[$p . '-' . $c] = $hex;
        }

        return $out;
    }
}
