<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Exceptions\ValidationException;
use App\Models\ObraSocial;
use App\Models\Paciente;
use App\Repositories\PacienteRepository;
use App\Services\PacienteService;
use PHPUnit\Framework\TestCase;

final class PacienteServiceTest extends TestCase
{
    private function makeService(?PacienteRepository $repo = null): PacienteService
    {
        return new PacienteService(
            pacienteRepo: $repo ?? $this->createMock(PacienteRepository::class),
        );
    }

    private function makePaciente(): Paciente
    {
        return Paciente::fromRow([
            'id' => 1, 'nro_hc' => '00001', 'dni' => '30111111',
            'apellido' => 'Perez', 'nombres' => 'Juan',
            'telefono' => null, 'fecha_nacimiento' => '1985-03-12',
            'sexo' => 'M', 'obra_social_id' => 1,
            'obra_social_nombre' => 'OSDE', 'nro_afiliado' => 'OSD-1001',
        ]);
    }

    public function test_buscar_pasa_filtros_saneados_al_repo(): void
    {
        $repo = $this->createMock(PacienteRepository::class);
        $repo->expects($this->once())
             ->method('buscar')
             ->with(
                 $this->callback(function (array $f) {
                     return $f['apellido'] === 'Perez'
                         && $f['orden']    === 'apellido_asc'
                         && !isset($f['dni']); // vacios se quitan
                 }),
                 100
             )
             ->willReturn(['pacientes' => [$this->makePaciente()], 'total_encontrados' => 1]);

        $service = $this->makeService($repo);
        $r = $service->buscar(['apellido' => '  Perez  ', 'dni' => '', 'orden' => '']);

        $this->assertSame(1, $r['total_encontrados']);
        $this->assertFalse($r['truncado']);
        $this->assertCount(1, $r['pacientes']);
    }

    public function test_buscar_marca_truncado_true_cuando_total_supera_limite(): void
    {
        $repo = $this->createMock(PacienteRepository::class);
        $repo->method('buscar')->willReturn([
            'pacientes' => array_fill(0, 100, $this->makePaciente()),
            'total_encontrados' => 250,
        ]);

        $r = $this->makeService($repo)->buscar([]);

        $this->assertTrue($r['truncado']);
        $this->assertSame(250, $r['total_encontrados']);
    }

    public function test_fecha_nacimiento_invalida_lanza_validation_exception(): void
    {
        $service = $this->makeService();

        $this->expectException(ValidationException::class);
        try {
            $service->buscar(['fecha_nacimiento' => '12/03/1985']);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fecha_nacimiento', $e->getFields());
            throw $e;
        }
    }

    public function test_sexo_invalido_lanza_validation_exception(): void
    {
        $service = $this->makeService();

        $this->expectException(ValidationException::class);
        try {
            $service->buscar(['sexo' => 'Z']);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sexo', $e->getFields());
            throw $e;
        }
    }

    public function test_obra_social_id_no_numerico_lanza_validation_exception(): void
    {
        $service = $this->makeService();

        $this->expectException(ValidationException::class);
        $service->buscar(['obra_social_id' => 'abc']);
    }

    public function test_orden_no_whitelisted_se_normaliza_al_default(): void
    {
        $repo = $this->createMock(PacienteRepository::class);
        $repo->expects($this->once())
             ->method('buscar')
             ->with($this->callback(fn ($f) => $f['orden'] === 'apellido_asc'), 100)
             ->willReturn(['pacientes' => [], 'total_encontrados' => 0]);

        $this->makeService($repo)->buscar(['orden' => 'malicioso; DROP TABLE']);
    }

    public function test_limite_se_clampa_entre_1_y_100(): void
    {
        $repo = $this->createMock(PacienteRepository::class);
        $repo->expects($this->once())
             ->method('buscar')
             ->with($this->anything(), 100)
             ->willReturn(['pacientes' => [], 'total_encontrados' => 0]);

        $this->makeService($repo)->buscar(['limite' => 9999]);
    }

    public function test_obtener_devuelve_paciente_o_null(): void
    {
        $repo = $this->createMock(PacienteRepository::class);
        $repo->method('obtener')->willReturnMap([
            [1, $this->makePaciente()],
            [999, null],
        ]);

        $service = $this->makeService($repo);
        $this->assertNotNull($service->obtener(1));
        $this->assertNull($service->obtener(999));
    }

    public function test_obtener_id_invalido_lanza_validation_exception(): void
    {
        $this->expectException(ValidationException::class);
        $this->makeService()->obtener(0);
    }

    public function test_listar_obras_sociales_pasa_a_repo(): void
    {
        $repo = $this->createMock(PacienteRepository::class);
        $repo->expects($this->once())
             ->method('listarObrasSociales')
             ->willReturn([new ObraSocial(1, 'OSDE', true)]);

        $r = $this->makeService($repo)->listarObrasSociales();
        $this->assertCount(1, $r);
        $this->assertSame('OSDE', $r[0]->nombre);
    }
}
