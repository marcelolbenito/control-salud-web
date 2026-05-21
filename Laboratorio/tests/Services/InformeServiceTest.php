<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Repositories\InformeRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ResultadoRepository;
use App\Services\AuditoriaService;
use App\Services\InformePdfRenderer;
use App\Services\InformeService;
use PDO;
use PHPUnit\Framework\TestCase;

final class InformeServiceTest extends TestCase
{
    private string $tempDir = '';

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lab_informes_test_' . uniqid();
        mkdir($this->tempDir, 0775, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            foreach (glob($this->tempDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($this->tempDir);
        }
    }

    /**
     * @param array<string,mixed> $opts
     */
    private function makeService(array $opts = []): InformeService
    {
        $db = $this->createMock(PDO::class);
        $db->method('beginTransaction')->willReturn(true);
        $db->method('commit')->willReturn(true);
        $db->method('rollBack')->willReturn(true);
        $db->method('inTransaction')->willReturn(true);

        $renderer = $opts['renderer'] ?? $this->createMock(InformePdfRenderer::class);
        if (!isset($opts['renderer'])) {
            $renderer->method('render')->willReturn('%PDF-fake-bytes');
        }

        $writes = [];
        $writer = function (string $path, string $bytes) use (&$writes): bool {
            $writes[] = ['path' => $path, 'bytes' => $bytes];
            return file_put_contents($path, $bytes) !== false;
        };

        $service = new InformeService(
            db: $db,
            informeRepo: $opts['informeRepo'] ?? $this->createMock(InformeRepository::class),
            resultadoRepo: $opts['resultadoRepo'] ?? $this->createMock(ResultadoRepository::class),
            pedidoRepo: $opts['pedidoRepo'] ?? $this->createMock(PedidoRepository::class),
            renderer: $renderer,
            auditoria: $this->createMock(AuditoriaService::class),
            storageDir: $this->tempDir,
            fileWriter: $writer,
        );

        return $service;
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function pedidoFake(array $overrides = []): array
    {
        return array_merge([
            'id'                => 1,
            'numero'            => '1',
            'estado'            => 'completo',
            'fecha_solicitud'   => '2026-05-06 09:00:00',
            'medico_externo'    => 'Dra. Lopez',
            'snapshot_paciente' => [
                'nombre'    => 'Juan Perez',
                'dni'       => '12345678',
                'sexo'      => 'M',
                'fecha_nac' => '1990-01-01',
            ],
        ], $overrides);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function resultadoFake(array $overrides = []): array
    {
        return array_merge([
            'id'                   => 100,
            'pedido_item_id'       => 10,
            'valor_numerico'       => 95,
            'valor_texto'          => null,
            'unidad'               => 'mg/dL',
            'es_anormal'           => 0,
            'es_critico'           => 0,
            'estado'               => 'validado',
            'version'              => 1,
            'valor_referencia_min' => 70,
            'valor_referencia_max' => 110,
            'texto_referencia'     => null,
            'observaciones'        => null,
            'usuario_carga_id'     => 1,
            'usuario_validacion_id' => 2,
            'fecha_carga'          => '2026-05-06 10:00:00',
            'fecha_validacion'     => '2026-05-06 11:00:00',
            'determinacion_id'     => 5,
            'determinacion_codigo' => 'GLU',
            'determinacion_nombre' => 'Glucemia',
            'tipo_resultado'       => 'numerico',
            'decimales'            => 2,
            'area_id'              => 1,
            'area_codigo'          => 'QUIM',
            'area_nombre'          => 'Quimica clinica',
            'area_orden'           => 1,
        ], $overrides);
    }

    public function testGenerarFallaSiPedidoNoExiste(): void
    {
        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('findById')->willReturn(null);

        $service = $this->makeService(['pedidoRepo' => $pedidoRepo]);

        try {
            $service->generar(99, 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pedido_id', $e->getFields());
        }
    }

    public function testGenerarFallaSiPedidoAnulado(): void
    {
        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('findById')->willReturn(
            $this->pedidoFake(['estado' => 'anulado'])
        );

        $service = $this->makeService(['pedidoRepo' => $pedidoRepo]);

        $this->expectException(DomainException::class);
        $service->generar(1, 1);
    }

    public function testGenerarFallaSinResultadosValidados(): void
    {
        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('findById')->willReturn($this->pedidoFake());

        $resRepo = $this->createMock(ResultadoRepository::class);
        $resRepo->method('findByPedidoId')->willReturn([
            $this->resultadoFake(['estado' => 'cargado']),
        ]);

        $service = $this->makeService([
            'pedidoRepo'    => $pedidoRepo,
            'resultadoRepo' => $resRepo,
        ]);

        $this->expectException(DomainException::class);
        $service->generar(1, 1);
    }

    public function testGenerarCompletoFallaSiAlgunoNoEstaValidado(): void
    {
        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('findById')->willReturn($this->pedidoFake());

        $resRepo = $this->createMock(ResultadoRepository::class);
        $resRepo->method('findByPedidoId')->willReturn([
            $this->resultadoFake(['id' => 100, 'estado' => 'validado']),
            $this->resultadoFake(['id' => 101, 'estado' => 'cargado']),
        ]);

        $service = $this->makeService([
            'pedidoRepo'    => $pedidoRepo,
            'resultadoRepo' => $resRepo,
        ]);

        $this->expectException(DomainException::class);
        $service->generar(1, 1);
    }

    public function testGenerarCompletoExitosoTransicionaPedidoYGuardaPdf(): void
    {
        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('findById')->willReturn($this->pedidoFake());
        $pedidoRepo->expects($this->once())
            ->method('updateEstado')
            ->with(1, 'entregado')
            ->willReturn(true);
        $pedidoRepo->expects($this->once())
            ->method('updateFechaEntrega')
            ->willReturn(true);

        $resRepo = $this->createMock(ResultadoRepository::class);
        $resRepo->method('findByPedidoId')->willReturn([$this->resultadoFake()]);

        $informeRepo = $this->createMock(InformeRepository::class);
        $informeRepo->method('getNextNumeroForYear')->willReturn(1);
        $informeRepo->expects($this->once())->method('insert')->willReturn(500);

        $renderer = $this->createMock(InformePdfRenderer::class);
        $renderer->expects($this->once())
            ->method('render')
            ->willReturn('%PDF-1.4-binary-bytes');

        $service = $this->makeService([
            'pedidoRepo'    => $pedidoRepo,
            'resultadoRepo' => $resRepo,
            'informeRepo'   => $informeRepo,
            'renderer'      => $renderer,
        ]);

        $r = $service->generar(1, 1);

        $this->assertSame(500, $r['id']);
        $this->assertMatchesRegularExpression('/^I-\d{4}-\d{5}$/', $r['numero']);
        $this->assertFalse($r['es_parcial']);
        $this->assertSame(64, strlen($r['hash_pdf']), 'SHA-256 debe medir 64 hex chars');
        $this->assertFileExists($this->tempDir . DIRECTORY_SEPARATOR . $r['ruta_pdf']);
    }

    public function testGenerarParcialNoTransicionaPedido(): void
    {
        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('findById')->willReturn(
            $this->pedidoFake(['estado' => 'en_proceso'])
        );
        $pedidoRepo->expects($this->never())->method('updateEstado');
        $pedidoRepo->expects($this->never())->method('updateFechaEntrega');

        $resRepo = $this->createMock(ResultadoRepository::class);
        $resRepo->method('findByPedidoId')->willReturn([
            $this->resultadoFake(['id' => 100, 'estado' => 'validado']),
            $this->resultadoFake(['id' => 101, 'estado' => 'cargado']),
        ]);

        $informeRepo = $this->createMock(InformeRepository::class);
        $informeRepo->method('getNextNumeroForYear')->willReturn(7);
        $informeRepo->method('insert')->willReturn(501);

        $service = $this->makeService([
            'pedidoRepo'    => $pedidoRepo,
            'resultadoRepo' => $resRepo,
            'informeRepo'   => $informeRepo,
        ]);

        $r = $service->generar(1, 1, ['es_parcial' => true]);

        $this->assertTrue($r['es_parcial']);
    }

    public function testMarcarEntregadoFallaSiYaEntregado(): void
    {
        $informeRepo = $this->createMock(InformeRepository::class);
        $informeRepo->method('findById')->willReturn([
            'id' => 1, 'entregado' => 1,
        ]);

        $service = $this->makeService(['informeRepo' => $informeRepo]);

        $this->expectException(DomainException::class);
        $service->marcarEntregado(1, 1, 'Paciente');
    }

    public function testMarcarEntregadoExitoso(): void
    {
        $informeRepo = $this->createMock(InformeRepository::class);
        $informeRepo->method('findById')->willReturn([
            'id' => 1, 'entregado' => 0,
        ]);
        $informeRepo->expects($this->once())
            ->method('marcarEntregado')
            ->willReturn(true);

        $service = $this->makeService(['informeRepo' => $informeRepo]);

        $r = $service->marcarEntregado(1, 5, 'Familiar');

        $this->assertTrue($r['entregado']);
        $this->assertSame('Familiar', $r['destinatario']);
    }
}
