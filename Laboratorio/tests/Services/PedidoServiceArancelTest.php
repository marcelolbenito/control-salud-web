<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\ItemMonto;
use App\Models\PedidoItem;
use App\Repositories\DeterminacionRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\PerfilRepository;
use App\Services\AranceladorService;
use App\Services\AuditoriaService;
use App\Services\PedidoService;
use PDO;
use PHPUnit\Framework\TestCase;

final class PedidoServiceArancelTest extends TestCase
{
    private function inputValido(?int $obraSocialId): array
    {
        return [
            'paciente_id' => 1,
            'obra_social_id' => $obraSocialId,
            'snapshot_paciente' => [
                'nombre' => 'Juan Perez', 'dni' => '12345678',
                'sexo' => 'M', 'fecha_nac' => '1990-01-01',
            ],
            'prioridad' => 'rutina',
            'items' => [
                ['determinacion_id' => 1],
                ['determinacion_id' => 2],
            ],
        ];
    }

    private function makeService(
        AranceladorService $arancelador,
        PedidoRepository $pedidoRepo,
    ): PedidoService {
        $db = $this->createMock(PDO::class);
        $db->method('beginTransaction')->willReturn(true);
        $db->method('commit')->willReturn(true);
        $db->method('rollBack')->willReturn(true);
        $db->method('inTransaction')->willReturn(true);

        $detRepo = $this->createMock(DeterminacionRepository::class);
        $detRepo->method('findActivasByIds')->willReturn([
            ['id' => 1, 'precio' => '100.00'],
            ['id' => 2, 'precio' => '200.00'],
        ]);
        $detRepo->method('findSoloFacturacionIds')->willReturn([]);

        return new PedidoService(
            db: $db,
            pedidoRepo: $pedidoRepo,
            determinacionRepo: $detRepo,
            perfilRepo: $this->createMock(PerfilRepository::class),
            auditoria: $this->createMock(AuditoriaService::class),
            arancelador: $arancelador,
        );
    }

    public function testCrearConOsActualizaMontoSeguro(): void
    {
        $arancelador = $this->createMock(AranceladorService::class);
        $arancelador->method('calcularMontoItem')->willReturnCallback(
            function (int $detId, ?int $osId, ?string $fecha = null): ItemMonto {
                if ($detId === 1) return ItemMonto::nbuOs(2.5, 120.0);   // 300
                if ($detId === 2) return ItemMonto::nbuOs(8.0, 120.0);   // 960
                return ItemMonto::sinArancel();
            }
        );

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('getNextNumeroForYear')->willReturn(1);
        $pedidoRepo->method('insert')->willReturn(42);
        $pedidoRepo->method('insertItem')->willReturn(1);
        $pedidoRepo->method('calcularMontoSeguroUnits')->with(42)->willReturn(1260.00);

        $montosCapturados = null;
        $pedidoRepo->method('updateMontos')
            ->willReturnCallback(function (int $id, float $s, float $p) use (&$montosCapturados): void {
                $montosCapturados = ['seguro' => $s, 'paciente' => $p];
            });

        $service = $this->makeService($arancelador, $pedidoRepo);
        $result = $service->crear($this->inputValido(99), usuarioId: 1);

        $this->assertSame(42, $result['id']);
        $this->assertSame(['seguro' => 1260.00, 'paciente' => 0.00], $montosCapturados);
    }

    public function testCrearParticularSumaMontoPaciente(): void
    {
        $arancelador = $this->createMock(AranceladorService::class);
        $arancelador->method('calcularMontoItem')->willReturnCallback(
            function (int $detId, ?int $osId, ?string $fecha = null): ItemMonto {
                if ($detId === 1) return ItemMonto::precioParticular(100.0);
                if ($detId === 2) return ItemMonto::precioParticular(200.0);
                return ItemMonto::sinArancel();
            }
        );

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('getNextNumeroForYear')->willReturn(1);
        $pedidoRepo->method('insert')->willReturn(43);
        $pedidoRepo->method('insertItem')->willReturn(1);

        $montos = null;
        $pedidoRepo->method('updateMontos')
            ->willReturnCallback(function (int $id, float $s, float $p) use (&$montos): void {
                $montos = ['seguro' => $s, 'paciente' => $p];
            });

        $service = $this->makeService($arancelador, $pedidoRepo);
        $service->crear($this->inputValido(null), usuarioId: 1);

        $this->assertSame(['seguro' => 0.00, 'paciente' => 300.00], $montos);
    }

    public function testCrearConItemSinArancelLoSetEaCero(): void
    {
        $arancelador = $this->createMock(AranceladorService::class);
        $arancelador->method('calcularMontoItem')->willReturnCallback(
            function (int $detId, ?int $osId, ?string $fecha = null): ItemMonto {
                if ($detId === 1) return ItemMonto::nbuOs(2.5, 120.0);   // 300
                if ($detId === 2) return ItemMonto::sinArancel();         // 0
                return ItemMonto::sinArancel();
            }
        );

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('getNextNumeroForYear')->willReturn(1);
        $pedidoRepo->method('insert')->willReturn(44);
        $pedidoRepo->method('insertItem')->willReturn(1);
        $pedidoRepo->method('calcularMontoSeguroUnits')->with(44)->willReturn(300.00);

        $montos = null;
        $pedidoRepo->method('updateMontos')
            ->willReturnCallback(function (int $id, float $s, float $p) use (&$montos): void {
                $montos = ['seguro' => $s, 'paciente' => $p];
            });

        $service = $this->makeService($arancelador, $pedidoRepo);
        $service->crear($this->inputValido(99), usuarioId: 1);

        $this->assertSame(['seguro' => 300.00, 'paciente' => 0.00], $montos);
    }
}
