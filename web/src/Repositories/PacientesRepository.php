<?php

declare(strict_types=1);

final class PacientesRepository
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hasExtendedColumns(): bool
    {
        return db_table_has_column($this->pdo, 'pacientes', 'apellido');
    }

    public function hasListaCoberturas(): bool
    {
        return db_table_exists($this->pdo, 'lista_coberturas');
    }

    /**
     * @param array<string, string|int> $filters q, nrohc, id, activo (''|'0'|'1')
     * @return list<array<string, mixed>>
     */
    public function listForIndex(bool $hasExt, bool $hasListaCob, array $filters = []): array
    {
        $joinCob = $hasExt && $hasListaCob;
        $selCob = $joinCob ? ', lc.nombre AS cobertura_nombre' : ', NULL AS cobertura_nombre';
        $sql = 'SELECT p.id, p.NroHC, ' . ($hasExt ? 'p.apellido' : 'NULL AS apellido') . ', p.Nombres, p.DNI, p.telefono, p.email, p.activo' . $selCob
            . ' FROM pacientes p';
        if ($joinCob) {
            $sql .= ' LEFT JOIN lista_coberturas lc ON lc.id = p.id_cobertura';
        }
        $sql .= ' WHERE 1=1';
        $params = [];

        $activo = isset($filters['activo']) ? trim((string) $filters['activo']) : '1';
        if ($activo === '1') {
            $sql .= ' AND p.activo = 1';
        } elseif ($activo === '0') {
            $sql .= ' AND (p.activo = 0 OR p.activo IS NULL)';
        }

        $nrohc = (int) ($filters['nrohc'] ?? 0);
        if ($nrohc > 0) {
            $sql .= ' AND p.NroHC = ?';
            $params[] = $nrohc;
        }
        $id = (int) ($filters['id'] ?? 0);
        if ($id > 0) {
            $sql .= ' AND p.id = ?';
            $params[] = $id;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            if ($hasExt) {
                $cols = ['apellido', 'apellido2', 'Nombres', 'DNI', 'telefono', 'email'];
                foreach (['tel_celular', 'tel_laboral', 'nro_os', 'nu_afiliado2'] as $col) {
                    if (db_table_has_column($this->pdo, 'pacientes', $col)) {
                        $cols[] = $col;
                    }
                }
                $parts = [];
                foreach ($cols as $col) {
                    $parts[] = 'p.' . $col . ' LIKE ?';
                    $params[] = $like;
                }
                $sql .= ' AND (' . implode(' OR ', $parts) . ')';
            } else {
                $sql .= ' AND (p.Nombres LIKE ? OR p.DNI LIKE ? OR p.telefono LIKE ? OR p.email LIKE ?)';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
        }

        $sql .= ' ORDER BY p.NroHC DESC LIMIT 500';

        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public function updateHistoriaClinica(int $id, string $hcText, string $antecedentes, bool $hasHcTexto, bool $hasAnteced): void
    {
        if ($hasHcTexto && $hasAnteced) {
            $u = $this->pdo->prepare('UPDATE pacientes SET hc_texto=?, antecedentes_hc=? WHERE id=?');
            $u->execute([$hcText, $antecedentes, $id]);
            return;
        }
        if ($hasHcTexto) {
            $u = $this->pdo->prepare('UPDATE pacientes SET hc_texto=? WHERE id=?');
            $u->execute([$hcText, $id]);
            return;
        }
        if ($hasAnteced) {
            $u = $this->pdo->prepare('UPDATE pacientes SET HC=?, antecedentes_hc=? WHERE id=?');
            $u->execute([$hcText, $antecedentes, $id]);
            return;
        }

        $u = $this->pdo->prepare('UPDATE pacientes SET HC=? WHERE id=?');
        $u->execute([$hcText, $id]);
    }

    public function deleteById(int $id): void
    {
        $st = $this->pdo->prepare('DELETE FROM pacientes WHERE id = ?');
        $st->execute([$id]);
    }

    public function suggestedNextNroHC(): int
    {
        return (int) $this->pdo->query('SELECT COALESCE(MAX(NroHC), 0) + 1 AS n FROM pacientes')->fetch()['n'];
    }

    public function existsOtherWithNroHC(int $nroHC, int $excludeId): bool
    {
        $st = $this->pdo->prepare('SELECT id FROM pacientes WHERE NroHC = ? AND id != ? LIMIT 1');
        $st->execute([$nroHC, $excludeId]);
        return (bool) $st->fetch();
    }

    /** @var array<string, true>|null */
    private static $columnCacheByDsn = [];

    /**
     * Columnas físicas de `pacientes` (caché por DSN de conexión).
     *
     * @return array<string, true>
     */
    private function pacienteColumnSet(): array
    {
        $key = spl_object_hash($this->pdo);
        if (!isset(self::$columnCacheByDsn[$key])) {
            $st = $this->pdo->query('SHOW COLUMNS FROM pacientes');
            $set = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $set[(string) $row['Field']] = true;
            }
            self::$columnCacheByDsn[$key] = $set;
        }

        return self::$columnCacheByDsn[$key];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function filterPayloadToExistingColumns(array $payload): array
    {
        $set = $this->pacienteColumnSet();
        $out = [];
        foreach ($payload as $k => $v) {
            if (isset($set[$k])) {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    /**
     * Alta con todas las columnas presentes en BD (backup / migration_002).
     *
     * @param array<string, mixed> $payload Sin `id`; puede incluir solo un subconjunto — el resto queda default MySQL.
     * @return int id autogenerado
     */
    public function insertPacienteFull(array $payload): int
    {
        unset($payload['id'], $payload['creado_en'], $payload['actualizado_en']);
        $payload = $this->filterPayloadToExistingColumns($payload);
        if ($payload === []) {
            throw new \InvalidArgumentException('insertPacienteFull: payload vacío tras filtrar columnas.');
        }
        $cols = array_keys($payload);
        $quoted = array_map(static function (string $c): string {
            return '`' . str_replace('`', '``', $c) . '`';
        }, $cols);
        $ph = implode(',', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO pacientes (' . implode(',', $quoted) . ') VALUES (' . $ph . ')';
        $st = $this->pdo->prepare($sql);
        $st->execute(array_values($payload));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $payload Valores a actualizar (no hace falta enviar todas las columnas).
     */
    public function updatePacienteFull(int $id, array $payload): void
    {
        unset($payload['id'], $payload['creado_en']);
        $payload = $this->filterPayloadToExistingColumns($payload);
        if ($payload === []) {
            return;
        }
        $sets = [];
        $vals = [];
        foreach ($payload as $k => $v) {
            $sets[] = '`' . str_replace('`', '``', $k) . '` = ?';
            $vals[] = $v;
        }
        $vals[] = $id;
        $sql = 'UPDATE pacientes SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $st = $this->pdo->prepare($sql);
        $st->execute($vals);
    }

    /**
     * Valores por defecto para ficha extendida (merge al crear o si falta clave).
     *
     * @return array<string, mixed>
     */
    public static function blankExtendedPatientRow(): array
    {
        return [
            'numehistoria' => '',
            'embarazo' => 0,
            'ulti_emba' => null,
            'ultima_cons' => null,
            'paciente_inactivo' => 0,
            'motivo_inactividad' => '',
            'cobertura' => null,
            'id_cobertura' => null,
            'nro_os' => '',
            'apellido' => '',
            'apellido2' => '',
            'fe_nac' => null,
            'sexo' => null,
            'dni_sin_uso' => '',
            'id_tipo_doc' => null,
            'id_ocupacion' => null,
            'detalle_ocupacion' => '',
            'tel_celular' => '',
            'tel_laboral' => '',
            'nombre_padre' => '',
            'naci_padre' => null,
            'id_ocupacion_padre' => null,
            'horas_hogar_padre' => '',
            'nombre_madre' => '',
            'naci_madre' => null,
            'id_ocupacion_madre' => null,
            'horas_hogar_madre' => '',
            'nro_hermanos' => '',
            'edad_hermanos' => '',
            'nro_hermanas' => '',
            'edad_hermanas' => '',
            'detalles_familia' => '',
            'ape1_contacto' => '',
            'ape2_contacto' => '',
            'nombre_contacto' => '',
            'id_relacion' => null,
            'tel_par_contacto' => '',
            'tel_cel_contacto' => '',
            'tel_lab_contacto' => '',
            'id_estado_civil' => null,
            'id_etnia' => null,
            'id_ciudad' => null,
            'cp' => '',
            'id_provincia' => null,
            'id_pais' => null,
            'id_estatus' => null,
            'alergias' => '',
            'grupo_sanguineo' => null,
            'factor_sanguineo' => null,
            'hc_texto' => '',
            'referente' => '',
            'id_cobertura2' => null,
            'nu_afiliado2' => '',
            'antecedentes_hc' => '',
            'id_plan' => null,
            'paga_iva' => 0,
            'alta_paci_web' => null,
            'identidad_gen' => null,
            'orientacion_sex' => null,
            'ruta_foto' => null,
        ];
    }

    public function insertPacienteBase(
        int $nroHC,
        string $nombres,
        string $dni,
        int $convenio,
        ?string $fechaNac,
        string $telefono,
        string $email,
        string $direccion,
        int $activo,
        string $notas
    ): void {
        $st = $this->pdo->prepare(
            'INSERT INTO pacientes (NroHC, Nombres, DNI, convenio, fecha_nacimiento, telefono, email, direccion, activo, notas) VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([$nroHC, $nombres, $dni, $convenio, $fechaNac, $telefono, $email, $direccion, $activo, $notas]);
    }

    public function updatePacienteBase(
        int $id,
        int $nroHC,
        string $nombres,
        string $dni,
        int $convenio,
        ?string $fechaNac,
        string $telefono,
        string $email,
        string $direccion,
        int $activo,
        string $notas
    ): void {
        $st = $this->pdo->prepare(
            'UPDATE pacientes SET NroHC=?, Nombres=?, DNI=?, convenio=?, fecha_nacimiento=?, telefono=?, email=?, direccion=?, activo=?, notas=? WHERE id=?'
        );
        $st->execute([$nroHC, $nombres, $dni, $convenio, $fechaNac, $telefono, $email, $direccion, $activo, $notas, $id]);
    }
}

