<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Exceptions\ValidationException;
use App\Models\ItemMonto;
use App\Repositories\DeterminacionRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\PerfilRepository;
use App\Services\AranceladorService;
use App\Services\AuditoriaService;
use App\Services\PedidoService;
use PDO;
use PHPUnit\Framework\TestCase;

final class PedidoServiceTest extends TestCase
{
    /**
     * Construye un PedidoService con todos los colaboradores mockeados.
     */
    private function makeService(
        ?PedidoRepository $pedidoRepo = null,
        ?DeterminacionRepository $detRepo = null,
        ?PerfilRepository $perfilRepo = null,
    ): PedidoService {
        $db = $this->createMock(PDO::class);
        $db->method('beginTransaction')->willReturn(true);
        $db->method('commit')->willReturn(true);
        $db->method('rollBack')->willReturn(true);
        $db->method('inTransaction')->willReturn(true);

        return new PedidoService(
            db: $db,
            pedidoRepo: $pedidoRepo ?? $this->createMock(PedidoRepository::class),
            determinacionRepo: $detRepo ?? $this->createMock(DeterminacionRepository::class),
            perfilRepo: $perfilRepo ?? $this->createMock(PerfilRepository::class),
            auditoria: $this->createMock(AuditoriaService::class),
            arancelador: (function () {
                $mock = $this->createMock(AranceladorService::class);
                $mock->method('calcularMontoItem')->willReturn(ItemMonto::sinArancel());
                return $mock;
            })(),
        );
    }

    /**
     * Input minimo valido para reusar entre tests.
     *
     * @return array<string,mixed>
     */
    private function inputValido(): array
    {
        return [
            'paciente_id' => 1,
            'snapshot_paciente' => [
                'nombre'    => 'Juan Perez',
                'dni'       => '12345678',
                'sexo'      => 'M',
                'fecha_nac' => '1990-01-01',
            ],
            'prioridad' => 'rutina',
            'items' => [
                ['determinacion_id' => 1],
            ],
        ];
    }

    public function testPermiteCrearSinPacienteId(): void
    {
        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('getNextNumeroForYear')->willReturn(1);
        $pedidoRepo->method('insert')->willReturn(10);
        $pedidoRepo->method('insertItem')->willReturn(1);

        $detRepo = $this->createMock(DeterminacionRepository::class);
        $detRepo->method('findActivasByIds')->willReturn([['id' => 1, 'precio' => '100.00']]);
        $detRepo->method('findSoloFacturacionIds')->willReturn([]);

        $service = $this->makeService($pedidoRepo, $detRepo);
        $input = $this->inputValido();
        unset($input['paciente_id']);

        $result = $service->crear($input, 1);
        $this->assertSame(10, $result['id']);
    }

    public function testFallaSinSnapshotPaciente(): void
    {
        $service = $this->makeService();
        $input = $this->inputValido();
        unset($input['snapshot_paciente']);

        try {
            $service->crear($input, 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('snapshot_paciente', $e->getFields());
        }
    }

    public function testFallaConSexoInvalido(): void
    {
        $service = $this->makeService();
        $input = $this->inputValido();
        $input['snapshot_paciente']['sexo'] = 'Z';

        try {
            $service->crear($input, 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('snapshot_paciente.sexo', $e->getFields());
        }
    }

    public function testFallaSinItems(): void
    {
        $service = $this->makeService();
        $input = $this->inputValido();
        $input['items'] = [];

        try {
            $service->crear($input, 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->getFields());
        }
    }

    public function testFallaConPrioridadInvalida(): void
    {
        $service = $this->makeService();
        $input = $this->inputValido();
        $input['prioridad'] = 'inventada';

        try {
            $service->crear($input, 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('prioridad', $e->getFields());
        }
    }

    public function testFallaConItemSinDeterminacionNiPerfil(): void
    {
        $service = $this->makeService();
        $input = $this->inputValido();
        $input['items'] = [['observaciones' => 'sin id']];

        try {
            $service->crear($input, 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items[0]', $e->getFields());
        }
    }

    public function testFallaConItemConDeterminacionYPerfilJuntos(): void
    {
        $service = $this->makeService();
        $input = $this->inputValido();
        $input['items'] = [['determinacion_id' => 1, 'perfil_id' => 2]];

        try {
            $service->crear($input, 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items[0]', $e->getFields());
        }
    }

    public function testCreaPedidoConDeterminacionDirecta(): void
    {
        $det = $this->createMock(DeterminacionRepository::class);
        $det->method('findActivasByIds')->willReturn([
            ['id' => 1, 'codigo' => 'GLU', 'nombre' => 'Glucemia', 'unidad' => 'mg/dL', 'precio' => 100.00],
        ]);

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('getNextNumeroForYear')->willReturn(42);
        $pedidoRepo->method('insert')->willReturn(99);
        $pedidoRepo->expects($this->once())->method('insertItem')->willReturn(123);

        $service = $this->makeService($pedidoRepo, $det);

        $result = $service->crear($this->inputValido(), 1);

        $this->assertSame(99, $result['id']);
        $this->assertSame('42', $result['numero']);
        $this->assertSame(1, $result['items_count']);
    }

    public function testExpandePerfilEnDeterminaciones(): void
    {
        $perfilRepo = $this->createMock(PerfilRepository::class);
        $perfilRepo->method('getDeterminacionIdsByPerfilId')
            ->with(5)
            ->willReturn([10, 11, 12]);

        $det = $this->createMock(DeterminacionRepository::class);
        $det->method('findActivasByIds')->willReturn([
            ['id' => 10, 'codigo' => 'A', 'nombre' => 'A', 'unidad' => 'u', 'precio' => null],
            ['id' => 11, 'codigo' => 'B', 'nombre' => 'B', 'unidad' => 'u', 'precio' => null],
            ['id' => 12, 'codigo' => 'C', 'nombre' => 'C', 'unidad' => 'u', 'precio' => null],
        ]);

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('getNextNumeroForYear')->willReturn(1);
        $pedidoRepo->method('insert')->willReturn(50);
        $pedidoRepo->expects($this->exactly(3))->method('insertItem')->willReturn(0);

        $service = $this->makeService($pedidoRepo, $det, $perfilRepo);

        $input = $this->inputValido();
        $input['items'] = [['perfil_id' => 5]];
        $result = $service->crear($input, 1);

        $this->assertSame(3, $result['items_count']);
    }

    public function testDeduplicaDeterminacionesRepetidas(): void
    {
        $det = $this->createMock(DeterminacionRepository::class);
        $det->method('findActivasByIds')->willReturn([
            ['id' => 1, 'codigo' => 'X', 'nombre' => 'X', 'unidad' => 'u', 'precio' => null],
        ]);

        $pedidoRepo = $this->createMock(PedidoRepository::class);
        $pedidoRepo->method('getNextNumeroForYear')->willReturn(1);
        $pedidoRepo->method('insert')->willReturn(1);
        $pedidoRepo->expects($this->once())->method('insertItem')->willReturn(0);

        $service = $this->makeService($pedidoRepo, $det);

        $input = $this->inputValido();
        $input['items'] = [
            ['determinacion_id' => 1],
            ['determinacion_id' => 1],
            ['determinacion_id' => 1],
        ];

        $result = $service->crear($input, 1);
        $this->assertSame(1, $result['items_count']);
    }

    public function testFallaConDeterminacionInexistente(): void
    {
        $det = $this->createMock(DeterminacionRepository::class);
        $det->method('findActivasByIds')->willReturn([]); // ninguna existe

        $service = $this->makeService(null, $det);

        try {
            $service->crear($this->inputValido(), 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items', $e->getFields());
        }
    }

    public function testFallaConPerfilSinDeterminaciones(): void
    {
        $perfilRepo = $this->createMock(PerfilRepository::class);
        $perfilRepo->method('getDeterminacionIdsByPerfilId')->willReturn([]);

        $service = $this->makeService(null, null, $perfilRepo);

        $input = $this->inputValido();
        $input['items'] = [['perfil_id' => 99]];

        try {
            $service->crear($input, 1);
            $this->fail('Esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items[0]', $e->getFields());
        }
    }

    // =========================================================
    // Sub-proyecto 2: buscar / anular / eliminar
    // =========================================================

    public function test_buscar_pasa_filtros_saneados_y_pagina_al_repo(): void
    {
        $repo = $this->createMock(PedidoRepository::class);
        $repo->expects($this->once())
             ->method('buscar')
             ->with(
                 $this->callback(function (array $f) {
                     return $f['estado'] === 'pendiente'
                         && $f['orden']  === 'fecha_solicitud_desc'
                         && !isset($f['numero']);  // vacios se quitan
                 }),
                 50,    // por_pagina
                 50     // offset (page=2)
             )
             ->willReturn(['pedidos' => [], 'total' => 120]);

        $r = $this->makeService($repo)->buscar([
            'estado' => 'pendiente',
            'numero' => '',
            'page'   => 2,
        ]);

        $this->assertSame(120, $r['total']);
        $this->assertSame(2, $r['page']);
        $this->assertSame(50, $r['por_pagina']);
        $this->assertSame(3, $r['total_paginas']); // ceil(120/50)
    }

    public function test_buscar_estado_invalido_lanza_validation(): void
    {
        $this->expectException(ValidationException::class);
        $this->makeService()->buscar(['estado' => 'foo']);
    }

    public function test_buscar_fecha_invalida_lanza_validation(): void
    {
        $this->expectException(ValidationException::class);
        $this->makeService()->buscar(['fecha_solicitud_desde' => '01/05/2026']);
    }

    public function test_buscar_orden_no_whitelisted_se_normaliza_al_default(): void
    {
        $repo = $this->createMock(PedidoRepository::class);
        $repo->expects($this->once())
             ->method('buscar')
             ->with($this->callback(fn ($f) => $f['orden'] === 'fecha_solicitud_desc'), 50, 0)
             ->willReturn(['pedidos' => [], 'total' => 0]);

        $this->makeService($repo)->buscar(['orden' => "'; DROP TABLE"]);
    }

    public function test_buscar_page_minimo_es_1(): void
    {
        $repo = $this->createMock(PedidoRepository::class);
        $repo->expects($this->once())
             ->method('buscar')
             ->with($this->anything(), 50, 0)  // offset 0 cuando page=1
             ->willReturn(['pedidos' => [], 'total' => 0]);

        $r = $this->makeService($repo)->buscar(['page' => -5]);
        $this->assertSame(1, $r['page']);
    }

    public function test_anular_actualiza_estado_y_audita(): void
    {
        $repo = $this->createMock(PedidoRepository::class);
        $repo->method('findById')->willReturn(['id' => 1, 'estado' => 'pendiente']);
        $repo->expects($this->once())
             ->method('anular')
             ->with(1, 'Paciente no se presento', 7)
             ->willReturn(true);

        $service = $this->makeService($repo);
        $service->anular(1, 'Paciente no se presento', 7);
        // No exception = test pasa.
        $this->assertTrue(true);
    }

    public function test_anular_motivo_vacio_lanza_validation(): void
    {
        $this->expectException(ValidationException::class);
        $this->makeService()->anular(1, '   ', 7);
    }

    public function test_anular_pedido_inexistente_lanza_validation(): void
    {
        $repo = $this->createMock(PedidoRepository::class);
        $repo->method('findById')->willReturn(null);
        $this->expectException(ValidationException::class);
        $this->makeService($repo)->anular(999, 'motivo', 7);
    }

    public function test_anular_pedido_ya_entregado_lanza_validation(): void
    {
        $repo = $this->createMock(PedidoRepository::class);
        $repo->method('findById')->willReturn(['id' => 1, 'estado' => 'entregado']);
        $this->expectException(ValidationException::class);
        $this->makeService($repo)->anular(1, 'motivo', 7);
    }

    public function test_eliminar_softdeletea_y_audita(): void
    {
        $repo = $this->createMock(PedidoRepository::class);
        $repo->method('findById')->willReturn(['id' => 1, 'estado' => 'pendiente', 'numero' => '1']);
        $repo->expects($this->once())->method('softDelete')->with(1)->willReturn(true);

        $this->makeService($repo)->eliminar(1, 7);
        $this->assertTrue(true);
    }

    public function test_eliminar_pedido_inexistente_lanza_validation(): void
    {
        $repo = $this->createMock(PedidoRepository::class);
        $repo->method('findById')->willReturn(null);
        $this->expectException(ValidationException::class);
        $this->makeService($repo)->eliminar(999, 7);
    }
}
