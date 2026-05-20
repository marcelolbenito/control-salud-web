<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\InformeRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ResultadoRepository;

/**
 * Vista de lectura: historial por paciente y dossier completo de un pedido.
 * Servicio "delgado": no hace transacciones ni cambia estado, solo arma
 * respuestas a partir de los repositorios existentes.
 */
final class HistorialService
{
    private const ESTADOS_VALIDOS = [
        'pendiente', 'en_proceso', 'parcial', 'completo', 'entregado', 'anulado',
    ];

    public function __construct(
        private PedidoRepository $pedidoRepo,
        private ResultadoRepository $resultadoRepo,
        private InformeRepository $informeRepo,
    ) {
    }

    /**
     * @param array<string,mixed> $filtros
     * @return array{pedidos:array<int,array<string,mixed>>, total:int, limit:int, offset:int}
     */
    public function listarPorPaciente(int $pacienteId, array $filtros = []): array
    {
        if ($pacienteId <= 0) {
            throw new ValidationException('paciente_id invalido', [
                'paciente_id' => 'Debe ser > 0',
            ]);
        }

        $errors = [];
        $filtrosLimpios = [];

        if (!empty($filtros['estado'])) {
            $estado = (string) $filtros['estado'];
            if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
                $errors['estado'] = 'estado invalido';
            } else {
                $filtrosLimpios['estado'] = $estado;
            }
        }
        foreach (['desde', 'hasta'] as $f) {
            if (!empty($filtros[$f])) {
                $valor = (string) $filtros[$f];
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                    $errors[$f] = "$f debe tener formato YYYY-MM-DD";
                } else {
                    $filtrosLimpios[$f] = $valor;
                }
            }
        }

        if (!empty($filtros['desde']) && !empty($filtros['hasta'])
            && empty($errors['desde']) && empty($errors['hasta'])
            && (string) $filtros['desde'] > (string) $filtros['hasta']) {
            $errors['rango'] = 'desde no puede ser posterior a hasta';
        }

        if ($errors !== []) {
            throw new ValidationException('Errores de validacion', $errors);
        }

        $limit = isset($filtros['limit']) ? max(1, min(200, (int) $filtros['limit'])) : 50;
        $offset = isset($filtros['offset']) ? max(0, (int) $filtros['offset']) : 0;
        $filtrosLimpios['limit'] = $limit;
        $filtrosLimpios['offset'] = $offset;

        $pedidos = $this->pedidoRepo->findByPacienteId($pacienteId, $filtrosLimpios);
        $total = $this->pedidoRepo->countByPacienteId($pacienteId, $filtrosLimpios);

        return [
            'pedidos' => $pedidos,
            'total'   => $total,
            'limit'   => $limit,
            'offset'  => $offset,
        ];
    }

    /**
     * Devuelve el dossier completo de un pedido: cabecera, items,
     * resultados (con info de determinacion y area) e informes emitidos.
     *
     * @return array<string,mixed>|null
     */
    public function dossierPedido(int $pedidoId): ?array
    {
        if ($pedidoId <= 0) {
            return null;
        }

        $pedido = $this->pedidoRepo->findById($pedidoId);
        if ($pedido === null) {
            return null;
        }

        return [
            'pedido'     => $pedido,
            'items'      => $this->pedidoRepo->findItemsByPedidoId($pedidoId),
            'resultados' => $this->resultadoRepo->findByPedidoId($pedidoId),
            'informes'   => $this->informeRepo->findByPedidoId($pedidoId),
        ];
    }
}
