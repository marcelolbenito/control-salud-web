<?php

declare(strict_types=1);

namespace ControlSalud\FacturacionElectronica;

use PDO;

final class FacturaElectronicaRepository
{
    private PDO $pdo;
    private int $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = $idClinica > 0 ? $idClinica : 1;
    }

    public function tablesOk(): bool
    {
        return db_table_exists($this->pdo, 'fe_parametros')
            && db_table_exists($this->pdo, 'fe_comprobantes');
    }

    /**
     * @return array<string, mixed>
     */
    public function getParametros(): array
    {
        $defaults = [
            'id_clinica' => $this->idClinica,
            'gesis_url' => 'https://servicios.gesis2.com',
            'gesis_email' => '',
            'gesis_password' => '',
            'cuit_emisor' => '',
            'razon_social' => '',
            'domicilio_comercial' => '',
            'condicion_iva_emisor' => 'monotributo',
            'punto_venta' => 1,
            'cbte_tipo' => 11,
            'concepto' => 2,
            'production' => 0,
        ];
        if (!db_table_exists($this->pdo, 'fe_parametros')) {
            return $defaults;
        }
        $st = $this->pdo->prepare('SELECT * FROM fe_parametros WHERE id_clinica = ? LIMIT 1');
        $st->execute([$this->idClinica]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $this->pdo->prepare('INSERT INTO fe_parametros (id_clinica) VALUES (?)')->execute([$this->idClinica]);
            $st->execute([$this->idClinica]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        return array_merge($defaults, $row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveParametros(array $data): void
    {
        $this->getParametros();
        $st = $this->pdo->prepare(
            'UPDATE fe_parametros SET
                gesis_url = ?, gesis_email = ?, gesis_password = ?,
                cuit_emisor = ?, razon_social = ?, domicilio_comercial = ?,
                condicion_iva_emisor = ?, punto_venta = ?, cbte_tipo = ?,
                concepto = ?, production = ?
             WHERE id_clinica = ?'
        );
        $pass = (string) ($data['gesis_password'] ?? '');
        if ($pass === '') {
            $cur = $this->getParametros();
            $pass = (string) ($cur['gesis_password'] ?? '');
        }
        $st->execute([
            rtrim((string) ($data['gesis_url'] ?? 'https://servicios.gesis2.com'), '/'),
            trim((string) ($data['gesis_email'] ?? '')),
            $pass,
            preg_replace('/\D/', '', (string) ($data['cuit_emisor'] ?? '')) ?: null,
            trim((string) ($data['razon_social'] ?? '')),
            trim((string) ($data['domicilio_comercial'] ?? '')),
            (string) ($data['condicion_iva_emisor'] ?? 'monotributo'),
            max(1, (int) ($data['punto_venta'] ?? 1)),
            max(1, (int) ($data['cbte_tipo'] ?? 11)),
            max(1, min(3, (int) ($data['concepto'] ?? 2))),
            !empty($data['production']) ? 1 : 0,
            $this->idClinica,
        ]);
    }

    /**
     * @return array{base_url:string,email:string,password:string,cuit_emisor:?string,production:bool,punto_venta:int,cbte_tipo:int,concepto:int}
     */
    public function gesisConfig(): array
    {
        $p = $this->getParametros();

        return [
            'base_url' => (string) ($p['gesis_url'] ?: 'https://servicios.gesis2.com'),
            'email' => (string) ($p['gesis_email'] ?? ''),
            'password' => (string) ($p['gesis_password'] ?? ''),
            'cuit_emisor' => ($p['cuit_emisor'] ?? '') !== '' ? (string) $p['cuit_emisor'] : null,
            'production' => !empty($p['production']),
            'punto_venta' => max(1, (int) ($p['punto_venta'] ?? 1)),
            'cbte_tipo' => max(1, (int) ($p['cbte_tipo'] ?? 11)),
            'concepto' => max(1, min(3, (int) ($p['concepto'] ?? 2))),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listar(int $limit = 50): array
    {
        if (!db_table_exists($this->pdo, 'fe_comprobantes')) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $st = $this->pdo->prepare(
            'SELECT id, id_paciente, paciente_nombre, paciente_doc, concepto_texto, letra,
                    punto_venta, numero, cbte_tipo, fecha_emision, importe_total, cae, cae_vencimiento,
                    estado, production, creado_en
             FROM fe_comprobantes
             WHERE id_clinica = ?
             ORDER BY fecha_emision DESC, id DESC
             LIMIT ' . $limit
        );
        $st->execute([$this->idClinica]);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        if ($id < 1 || !db_table_exists($this->pdo, 'fe_comprobantes')) {
            return null;
        }
        $st = $this->pdo->prepare('SELECT * FROM fe_comprobantes WHERE id = ? AND id_clinica = ? LIMIT 1');
        $st->execute([$id, $this->idClinica]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insertAutorizado(array $row): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO fe_comprobantes (
                id_clinica, id_paciente, id_usuario, paciente_nombre, paciente_doc, concepto_texto,
                letra, punto_venta, numero, cbte_tipo, fecha_emision, importe_total,
                cae, cae_vencimiento, estado, request_json, response_json, production
             ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $this->idClinica,
            $row['id_paciente'] ?? null,
            $row['id_usuario'] ?? null,
            $row['paciente_nombre'],
            $row['paciente_doc'] ?? null,
            $row['concepto_texto'],
            $row['letra'],
            $row['punto_venta'],
            $row['numero'],
            $row['cbte_tipo'],
            $row['fecha_emision'],
            $row['importe_total'],
            $row['cae'] ?? null,
            $row['cae_vencimiento'] ?? null,
            $row['estado'] ?? 'autorizado',
            $row['request_json'] ?? null,
            $row['response_json'] ?? null,
            !empty($row['production']) ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
