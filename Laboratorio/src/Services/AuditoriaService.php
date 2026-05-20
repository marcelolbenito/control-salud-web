<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Servicio para escribir en lab_auditoria.
 * Cumple regla 6.
 *
 * Uso tipico desde otros services:
 *   $this->auditoria->log(
 *       usuarioId: $userId,
 *       accion: 'crear',
 *       tablaAfectada: 'lab_pedidos',
 *       registroId: $pedidoId,
 *       valorNuevo: ['numero' => $numero],
 *   );
 */
final class AuditoriaService
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @param array<string,mixed>|null $valorAnterior
     * @param array<string,mixed>|null $valorNuevo
     */
    public function log(
        ?int $usuarioId,
        string $accion,
        string $tablaAfectada,
        ?int $registroId = null,
        ?array $valorAnterior = null,
        ?array $valorNuevo = null,
        ?string $contexto = null,
    ): void {
        $sql = "INSERT INTO lab_auditoria
                (usuario_id, accion, tabla_afectada, registro_id,
                 valor_anterior, valor_nuevo, ip, user_agent, contexto)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $usuarioId,
            $accion,
            $tablaAfectada,
            $registroId,
            $valorAnterior !== null
                ? json_encode($valorAnterior, JSON_UNESCAPED_UNICODE)
                : null,
            $valorNuevo !== null
                ? json_encode($valorNuevo, JSON_UNESCAPED_UNICODE)
                : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            isset($_SERVER['HTTP_USER_AGENT'])
                ? mb_substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500)
                : null,
            $contexto,
        ]);
    }
}
