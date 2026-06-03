<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Repositories\InformeRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ResultadoRepository;
use App\Repositories\ValorReferenciaRepository;
use DateTimeImmutable;

/**
 * Vista de lectura: historial por paciente y dossier completo de un pedido.
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
        private ValorReferenciaRepository $rangoRepo,
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

        $items = $this->pedidoRepo->findItemsByPedidoId($pedidoId);
        $items = $this->agregarReferencia($items, $pedido);
        $items = $this->agregarAnteriores($items, $pedido, $pedidoId);

        return [
            'pedido'     => $pedido,
            'items'      => $items,
            'resultados' => $this->resultadoRepo->findByPedidoId($pedidoId),
            'informes'   => $this->informeRepo->findByPedidoId($pedidoId),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @param array<string,mixed> $pedido
     * @return array<int,array<string,mixed>>
     */
    private function agregarAnteriores(array $items, array $pedido, int $pedidoId): array
    {
        if ($items === []) {
            return $items;
        }

        $pacienteId = isset($pedido['paciente_id']) && $pedido['paciente_id'] !== null
            ? (int) $pedido['paciente_id']
            : null;

        $snap = $pedido['snapshot_paciente'] ?? null;
        if (is_string($snap)) {
            $snap = json_decode($snap, true) ?: [];
        }
        $snap = is_array($snap) ? $snap : [];
        $dni = isset($snap['dni']) ? (string) $snap['dni'] : null;

        $detIds = [];
        foreach ($items as $it) {
            $d = (int) ($it['determinacion_id'] ?? 0);
            if ($d > 0) {
                $detIds[$d] = $d;
            }
        }

        $mapa = $this->resultadoRepo->findAnterioresPorPaciente(
            $pacienteId,
            $dni,
            array_values($detIds),
            $pedidoId,
        );

        foreach ($items as &$it) {
            $d = (int) ($it['determinacion_id'] ?? 0);
            $it['anteriores'] = $mapa[$d] ?? [];
        }
        unset($it);

        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @param array<string,mixed> $pedido
     * @return array<int,array<string,mixed>>
     */
    private function agregarReferencia(array $items, array $pedido): array
    {
        $snap = $pedido['snapshot_paciente'] ?? null;
        if (is_string($snap)) {
            $snap = json_decode($snap, true) ?: [];
        }
        $snap = is_array($snap) ? $snap : [];
        $sexo = (string) ($snap['sexo'] ?? '');
        $fechaNac = (string) ($snap['fecha_nac'] ?? '');

        if ($sexo === '' || $fechaNac === '') {
            return $items;
        }

        $edadDias = $this->calcularEdadDias($fechaNac, (string) ($pedido['fecha_extraccion'] ?? ''));

        foreach ($items as &$it) {
            $detId = (int) ($it['determinacion_id'] ?? 0);
            if ($detId <= 0) {
                continue;
            }
            $rango = $this->rangoRepo->findRangoAplicable($detId, $sexo, $edadDias);
            if ($rango !== null) {
                $it['valor_referencia_min'] = $rango['valor_min'] ?? null;
                $it['valor_referencia_max'] = $rango['valor_max'] ?? null;
                $it['texto_referencia']     = $rango['texto_referencia'] ?? null;
            }
        }
        unset($it);

        return $items;
    }

    private function calcularEdadDias(string $fechaNac, string $fechaExtraccion): int
    {
        if ($fechaNac === '') {
            return 0;
        }
        try {
            $nac = new DateTimeImmutable($fechaNac);
            $ref = $fechaExtraccion !== '' ? new DateTimeImmutable($fechaExtraccion) : new DateTimeImmutable();
        } catch (\Throwable) {
            return 0;
        }
        $diff = $nac->diff($ref);
        return $diff->invert === 1 ? 0 : (int) $diff->days;
    }

    /**
     * Variante del dossier que busca por el numero visible del pedido.
     *
     * @return array<string,mixed>|null
     */
    public function dossierPedidoByNumero(string $numero): ?array
    {
        $numero = trim($numero);
        if ($numero === '') {
            return null;
        }
        $pedido = $this->pedidoRepo->findByNumero($numero);
        if ($pedido === null) {
            return null;
        }
        return $this->dossierPedido((int) $pedido['id']);
    }
}
