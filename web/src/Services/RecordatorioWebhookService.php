<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/RecordatorioRepository.php';

final class RecordatorioWebhookService
{
    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;
    /** @var RecordatorioRepository */
    private $repo;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
        $this->repo = new RecordatorioRepository($pdo, $this->idClinica);
    }

    /**
     * @param array<string, mixed> $payload Evento WAHA (message)
     * @return array{ok:bool,message:string}
     */
    public function procesarMensajeEntrante(array $payload): array
    {
        if (!$this->repo->tableExists()) {
            return ['ok' => false, 'message' => 'Tabla agenda_recordatorios no existe.'];
        }
        if (!empty($payload['fromMe'])) {
            return ['ok' => true, 'message' => 'Ignorado (fromMe).'];
        }
        $body = trim((string) ($payload['body'] ?? $payload['text'] ?? ''));
        if ($body === '') {
            return ['ok' => true, 'message' => 'Sin texto.'];
        }
        $from = (string) ($payload['from'] ?? $payload['chatId'] ?? '');
        $tel = recordatorio_normalizar_telefono(preg_replace('/@.+$/', '', $from) ?: $from);
        if ($tel === null) {
            return ['ok' => false, 'message' => 'Teléfono no reconocido.'];
        }

        $row = $this->repo->findRecientePorTelefono($tel);
        if (!$row) {
            return ['ok' => true, 'message' => 'Sin recordatorio reciente para ese número.'];
        }

        $codigo = $this->interpretarRespuesta($body);
        $id = (int) ($row['id'] ?? 0);
        $this->repo->registrarRespuesta($id, $body, $codigo);
        $idTurno = (int) ($row['id_turno'] ?? 0);
        if ($codigo === 'confirmado') {
            $this->repo->marcarTurnoConfirmado($idTurno);
        } elseif ($codigo === 'cancelado') {
            $this->repo->marcarTurnoCanceladoPorPaciente($idTurno);
        }

        return ['ok' => true, 'message' => 'Respuesta ' . $codigo . ' registrada.'];
    }

    /**
     * Evento de la pasarela Gesis (WHATSAPP_INTEGRATION.md §4).
     *
     * @param array<string, mixed> $evento
     * @return array{ok:bool,message:string}
     */
    public function procesarEventoGesis(array $evento): array
    {
        $event = strtolower(trim((string) ($evento['event'] ?? '')));
        if ($event === 'session' || $event === 'status') {
            return ['ok' => true, 'message' => 'Evento ' . $event . ' recibido.'];
        }
        if ($event !== 'inbound') {
            return ['ok' => true, 'message' => 'Evento ignorado.'];
        }

        $data = $evento['data'] ?? null;
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Payload inbound sin data.'];
        }

        return $this->procesarMensajeEntrante([
            'from' => $data['from'] ?? '',
            'body' => $data['text'] ?? '',
            'text' => $data['text'] ?? '',
            'fromMe' => false,
        ]);
    }

    private function interpretarRespuesta(string $body): string
    {
        $norm = mb_strtoupper(trim($body), 'UTF-8');
        $norm = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $norm);
        if (preg_match('/^(SI|SÍ|OK|CONFIRMO|CONFIRMA|1)\b/u', $norm)) {
            return 'confirmado';
        }
        if (preg_match('/^(NO|CANCELO|CANCELAR|2)\b/u', $norm)) {
            return 'cancelado';
        }

        return 'otro';
    }
}
