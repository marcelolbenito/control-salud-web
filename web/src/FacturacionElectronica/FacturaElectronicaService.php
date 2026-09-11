<?php

declare(strict_types=1);

namespace ControlSalud\FacturacionElectronica;

use PDO;

require_once dirname(__DIR__) . '/Repositories/FacturaElectronicaRepository.php';
require_once __DIR__ . '/GesisArcaClient.php';

final class FacturaElectronicaService
{
    private PDO $pdo;
    private FacturaElectronicaRepository $repo;

    public function __construct(PDO $pdo, int $idClinica)
    {
        $this->pdo = $pdo;
        $this->repo = new FacturaElectronicaRepository($pdo, $idClinica);
    }

    public function repo(): FacturaElectronicaRepository
    {
        return $this->repo;
    }

    /**
     * @param array{id_paciente:?int,paciente_nombre:string,paciente_doc:string,concepto:string,importe:float,id_usuario:?int} $input
     * @return array{ok:bool,msg:string,id?:int,cae?:string,numero?:int,punto_venta?:int}
     */
    public function emitir(array $input): array
    {
        if (!$this->repo->tablesOk()) {
            return ['ok' => false, 'msg' => 'Falta sql/migration_040_factura_electronica.sql.'];
        }

        $nombre = trim((string) ($input['paciente_nombre'] ?? ''));
        $concepto = trim((string) ($input['concepto'] ?? ''));
        $importe = round((float) ($input['importe'] ?? 0), 2);
        $doc = preg_replace('/\D/', '', (string) ($input['paciente_doc'] ?? '')) ?? '';

        if ($nombre === '') {
            return ['ok' => false, 'msg' => 'Indicá el paciente / cliente.'];
        }
        if ($concepto === '') {
            return ['ok' => false, 'msg' => 'Indicá el texto / concepto de la factura.'];
        }
        if ($importe <= 0) {
            return ['ok' => false, 'msg' => 'El importe debe ser mayor a cero.'];
        }

        $cfg = $this->repo->gesisConfig();
        $client = new GesisArcaClient($cfg);
        if (!$client->isConfigured()) {
            return ['ok' => false, 'msg' => 'Configure Gesis en Facturación electrónica → Parámetros.'];
        }

        $voucher = $this->armarVoucher($cfg, $doc, $importe);
        $production = (bool) $cfg['production'];

        try {
            $resp = $client->crearProximoComprobante($voucher, $production);
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }

        $cae = isset($resp['CAE']) ? (string) $resp['CAE'] : '';
        if ($cae === '') {
            $msg = GesisArcaClient::extraerError($resp);

            return ['ok' => false, 'msg' => $msg !== '' ? $msg : 'La API no devolvió CAE.'];
        }

        $numero = (int) ($resp['voucherNumber'] ?? $resp['CbteDesde'] ?? 0);
        if ($numero < 1) {
            return ['ok' => false, 'msg' => 'Gesis no devolvió el número de comprobante.'];
        }

        $cbteTipo = (int) $cfg['cbte_tipo'];
        $letra = self::letraDesdeTipo($cbteTipo);
        $pto = (int) $cfg['punto_venta'];
        $fecha = date('Y-m-d H:i:s');
        $vto = self::parseCaeVto($resp);

        $id = $this->repo->insertAutorizado([
            'id_paciente' => !empty($input['id_paciente']) ? (int) $input['id_paciente'] : null,
            'id_usuario' => !empty($input['id_usuario']) ? (int) $input['id_usuario'] : null,
            'paciente_nombre' => mb_substr($nombre, 0, 200),
            'paciente_doc' => $doc !== '' ? mb_substr($doc, 0, 30) : null,
            'concepto_texto' => mb_substr($concepto, 0, 500),
            'letra' => $letra,
            'punto_venta' => $pto,
            'numero' => $numero,
            'cbte_tipo' => $cbteTipo,
            'fecha_emision' => $fecha,
            'importe_total' => $importe,
            'cae' => $cae,
            'cae_vencimiento' => $vto,
            'estado' => 'autorizado',
            'request_json' => json_encode($voucher, JSON_UNESCAPED_UNICODE),
            'response_json' => json_encode($resp, JSON_UNESCAPED_UNICODE),
            'production' => $production ? 1 : 0,
        ]);

        return [
            'ok' => true,
            'msg' => 'Factura autorizada.',
            'id' => $id,
            'cae' => $cae,
            'numero' => $numero,
            'punto_venta' => $pto,
        ];
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array<string, mixed>
     */
    private function armarVoucher(array $cfg, string $docDigits, float $total): array
    {
        $pto = (int) $cfg['punto_venta'];
        $cbteTipo = (int) $cfg['cbte_tipo'];
        $concepto = (int) $cfg['concepto'];
        $cbteFch = (int) date('Ymd');
        $mesIni = (int) date('Ym01');
        $mesFin = (int) date('Ymt');

        $docTipo = 99;
        $docNro = 0;
        if (strlen($docDigits) === 11) {
            $docTipo = 80;
            $docNro = (int) $docDigits;
        } elseif (strlen($docDigits) >= 7 && strlen($docDigits) <= 8) {
            $docTipo = 96;
            $docNro = (int) $docDigits;
        }

        $voucher = [
            'CantReg' => 1,
            'PtoVta' => $pto,
            'CbteTipo' => $cbteTipo,
            'Concepto' => $concepto,
            'DocTipo' => $docTipo,
            'DocNro' => $docNro,
            'CbteDesde' => 1,
            'CbteHasta' => 1,
            'CbteFch' => $cbteFch,
            'ImpTotal' => $total,
            'ImpTotConc' => 0.0,
            'ImpNeto' => $total,
            'ImpOpEx' => 0.0,
            'ImpIVA' => 0.0,
            'ImpTrib' => 0.0,
            'MonId' => 'PES',
            'MonCotiz' => 1.0,
            'CondicionIVAReceptorId' => 5, // consumidor final
        ];

        if ($concepto === 2 || $concepto === 3) {
            $voucher['FchServDesde'] = $mesIni;
            $voucher['FchServHasta'] = $mesFin;
            $voucher['FchVtoPago'] = $mesFin;
        }

        if ($cbteTipo === 6) {
            $neto = round($total / 1.21, 2);
            $iva = round($total - $neto, 2);
            $voucher['ImpNeto'] = $neto;
            $voucher['ImpIVA'] = $iva;
            $voucher['Iva'] = [['Id' => 5, 'BaseImp' => $neto, 'Importe' => $iva]];
        }

        return $voucher;
    }

    public static function letraDesdeTipo(int $cbteTipo): string
    {
        if (in_array($cbteTipo, [1, 3], true)) {
            return 'A';
        }
        if (in_array($cbteTipo, [6, 8], true)) {
            return 'B';
        }
        if ($cbteTipo === 11) {
            return 'C';
        }

        return 'C';
    }

    /**
     * @param array<string, mixed> $resp
     */
    public static function parseCaeVto(array $resp): ?string
    {
        foreach (['CAEFchVto', 'CaefchVto', 'caeFchVto'] as $key) {
            if (!isset($resp[$key]) || $resp[$key] === '' || $resp[$key] === null) {
                continue;
            }
            $digits = preg_replace('/\D/', '', (string) $resp[$key]) ?? '';
            if (strlen($digits) === 8) {
                return substr($digits, 0, 4) . '-' . substr($digits, 4, 2) . '-' . substr($digits, 6, 2);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $comp
     * @param array<string, mixed> $param
     */
    public static function renderPrintHtml(array $comp, array $param): string
    {
        $pto = (int) ($comp['punto_venta'] ?? 0);
        $num = (int) ($comp['numero'] ?? 0);
        $letra = (string) ($comp['letra'] ?? 'C');
        $cae = (string) ($comp['cae'] ?? '');
        $vto = (string) ($comp['cae_vencimiento'] ?? '');
        $total = number_format((float) ($comp['importe_total'] ?? 0), 2, ',', '.');
        $fecha = (string) ($comp['fecha_emision'] ?? '');
        if ($fecha !== '') {
            $ts = strtotime($fecha);
            $fecha = $ts ? date('d/m/Y H:i', $ts) : $fecha;
        }
        $razon = trim((string) ($param['razon_social'] ?? '')) ?: 'Clínica';
        $cuit = preg_replace('/\D/', '', (string) ($param['cuit_emisor'] ?? '')) ?: '';
        $dom = trim((string) ($param['domicilio_comercial'] ?? ''));
        $cliente = h((string) ($comp['paciente_nombre'] ?? ''));
        $docCli = h((string) ($comp['paciente_doc'] ?? ''));
        $concepto = h((string) ($comp['concepto_texto'] ?? ''));
        $prod = !empty($comp['production']) ? 'PRODUCCIÓN' : 'HOMOLOGACIÓN';

        $voucher = [];
        if (!empty($comp['request_json'])) {
            $decoded = json_decode((string) $comp['request_json'], true);
            if (is_array($decoded)) {
                $voucher = $decoded;
            }
        }
        $docTipo = (int) ($voucher['DocTipo'] ?? 99);
        $docNro = (int) ($voucher['DocNro'] ?? 0);
        $cbteTipo = (int) ($comp['cbte_tipo'] ?? 11);
        $importe = (float) ($comp['importe_total'] ?? 0);
        $fechaQr = !empty($comp['fecha_emision'])
            ? date('Y-m-d', strtotime((string) $comp['fecha_emision']) ?: time())
            : date('Y-m-d');
        $cuitInt = (int) ($cuit !== '' ? $cuit : '0');
        $qrPayload = [
            'ver' => 1,
            'fecha' => $fechaQr,
            'cuit' => $cuitInt,
            'ptoVta' => $pto,
            'tipoCmp' => $cbteTipo,
            'nroCmp' => $num,
            'importe' => round($importe, 2),
            'moneda' => 'PES',
            'ctz' => 1,
            'tipoDocRec' => $docTipo,
            'nroDocRec' => $docNro,
            'tipoCodAut' => 'E',
            'codAut' => (int) (preg_replace('/\D/', '', $cae) ?: '0'),
        ];
        $qrAfip = 'https://www.afip.gob.ar/fe/qr/?p=' . base64_encode((string) json_encode($qrPayload));
        $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . rawurlencode($qrAfip);

        $pvNum = sprintf('%05d-%08d', $pto, $num);

        return '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Factura ' . h($letra) . ' ' . h($pvNum) . '</title>
<style>
body{font-family:Segoe UI,Arial,sans-serif;margin:24px;color:#111}
.toolbar{margin-bottom:16px}
.box{border:1px solid #333;padding:16px;max-width:800px}
.row{display:flex;justify-content:space-between;gap:16px}
h1{font-size:1.25rem;margin:0 0 8px}
.muted{color:#555;font-size:0.9rem}
table{width:100%;border-collapse:collapse;margin-top:16px}
td,th{border-top:1px solid #ccc;padding:8px;text-align:left}
.num{text-align:right}
.cae{margin-top:16px;font-size:0.95rem}
@media print{.toolbar{display:none}}
</style></head><body>
<div class="toolbar no-print">
  <button onclick="window.print()">Imprimir / PDF</button>
  <a href="/factura_electronica.php">Volver</a>
  <span class="muted">Entorno: ' . h($prod) . '</span>
</div>
<div class="box">
  <div class="row">
    <div>
      <h1>' . h($razon) . '</h1>
      ' . ($cuit !== '' ? '<div class="muted">CUIT ' . h($cuit) . '</div>' : '') . '
      ' . ($dom !== '' ? '<div class="muted">' . h($dom) . '</div>' : '') . '
    </div>
    <div style="text-align:right">
      <div><strong>Factura ' . h($letra) . '</strong></div>
      <div>' . h($pvNum) . '</div>
      <div class="muted">' . h($fecha) . '</div>
    </div>
  </div>
  <hr>
  <p><strong>Cliente:</strong> ' . $cliente . ($docCli !== '' ? ' · Doc. ' . $docCli : '') . '</p>
  <table>
    <thead><tr><th>Concepto</th><th class="num">Importe</th></tr></thead>
    <tbody><tr><td>' . $concepto . '</td><td class="num">$ ' . h($total) . '</td></tr></tbody>
    <tfoot><tr><th>Total</th><th class="num">$ ' . h($total) . '</th></tr></tfoot>
  </table>
  <div class="row cae">
    <div>
      <div><strong>CAE:</strong> ' . h($cae) . '</div>
      <div><strong>Vto. CAE:</strong> ' . h($vto) . '</div>
    </div>
    <div>' . ($qrImg !== '' ? '<img src="' . h($qrImg) . '" alt="QR AFIP" width="140" height="140">' : '') . '</div>
  </div>
</div>
</body></html>';
    }
}
