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

    public function listForIndex(bool $hasExt, bool $hasListaCob): array
    {
        if ($hasExt && $hasListaCob) {
            return $this->pdo->query(
                'SELECT p.id, p.NroHC, p.apellido, p.Nombres, p.DNI, p.telefono, p.email, p.activo,
                    lc.nombre AS cobertura_nombre
                 FROM pacientes p
                 LEFT JOIN lista_coberturas lc ON lc.id = p.id_cobertura
                 ORDER BY p.NroHC DESC
                 LIMIT 500'
            )->fetchAll();
        }

        if ($hasExt) {
            return $this->pdo->query(
                'SELECT id, NroHC, apellido, Nombres, DNI, telefono, email, activo, NULL AS cobertura_nombre
                 FROM pacientes ORDER BY NroHC DESC LIMIT 500'
            )->fetchAll();
        }

        return $this->pdo->query(
            'SELECT id, NroHC, Nombres, DNI, telefono, email, activo, NULL AS cobertura_nombre, NULL AS apellido
             FROM pacientes ORDER BY NroHC DESC LIMIT 500'
        )->fetchAll();
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

    /**
     * @param array<string, mixed> $ex
     */
    public function insertPacienteExtended(
        int $nroHC,
        string $nombres,
        string $dni,
        int $convenio,
        ?string $fechaNac,
        string $telefono,
        string $email,
        string $direccion,
        int $activo,
        string $notas,
        array $ex
    ): void {
        $st = $this->pdo->prepare(
            'INSERT INTO pacientes (NroHC, Nombres, DNI, convenio, fecha_nacimiento, telefono, email, direccion, activo, notas,
            apellido, apellido2, numehistoria, id_cobertura, id_plan, nro_os, id_cobertura2, nu_afiliado2,
            tel_celular, tel_laboral, id_tipo_doc, id_ocupacion, detalle_ocupacion, sexo, cp,
            id_pais, id_provincia, id_ciudad, id_estado_civil, id_etnia, alergias)
            VALUES (?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $nroHC, $nombres, $dni, $convenio, $fechaNac, $telefono, $email, $direccion, $activo, $notas,
            $ex['apellido'], $ex['apellido2'], $ex['numehistoria'], $ex['id_cobertura'], $ex['id_plan'], $ex['nro_os'],
            $ex['id_cobertura2'], $ex['nu_afiliado2'], $ex['tel_celular'], $ex['tel_laboral'], $ex['id_tipo_doc'],
            $ex['id_ocupacion'], $ex['detalle_ocupacion'], $ex['sexo'], $ex['cp'],
            $ex['id_pais'], $ex['id_provincia'], $ex['id_ciudad'], $ex['id_estado_civil'], $ex['id_etnia'], $ex['alergias'],
        ]);
    }

    /**
     * @param array<string, mixed> $ex
     */
    public function updatePacienteExtended(
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
        string $notas,
        array $ex
    ): void {
        $st = $this->pdo->prepare(
            'UPDATE pacientes SET NroHC=?, Nombres=?, DNI=?, convenio=?, fecha_nacimiento=?, telefono=?, email=?, direccion=?, activo=?, notas=?,
            apellido=?, apellido2=?, numehistoria=?, id_cobertura=?, id_plan=?, nro_os=?, id_cobertura2=?, nu_afiliado2=?,
            tel_celular=?, tel_laboral=?, id_tipo_doc=?, id_ocupacion=?, detalle_ocupacion=?, sexo=?, cp=?,
            id_pais=?, id_provincia=?, id_ciudad=?, id_estado_civil=?, id_etnia=?, alergias=?
            WHERE id=?'
        );
        $st->execute([
            $nroHC, $nombres, $dni, $convenio, $fechaNac, $telefono, $email, $direccion, $activo, $notas,
            $ex['apellido'], $ex['apellido2'], $ex['numehistoria'], $ex['id_cobertura'], $ex['id_plan'], $ex['nro_os'],
            $ex['id_cobertura2'], $ex['nu_afiliado2'], $ex['tel_celular'], $ex['tel_laboral'], $ex['id_tipo_doc'],
            $ex['id_ocupacion'], $ex['detalle_ocupacion'], $ex['sexo'], $ex['cp'],
            $ex['id_pais'], $ex['id_provincia'], $ex['id_ciudad'], $ex['id_estado_civil'], $ex['id_etnia'], $ex['alergias'],
            $id,
        ]);
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

