<?php

declare(strict_types=1);

namespace Tests\Repositories;

use App\Repositories\LabConfigRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class LabConfigRepositoryTest extends TestCase
{
    public function test_get_devuelve_valor_existente(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([':clave' => 'foo']);
        $stmt->method('fetch')->willReturn(['valor' => 'bar']);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $repo = new LabConfigRepository($pdo);
        $this->assertSame('bar', $repo->get('foo'));
    }

    public function test_get_devuelve_null_si_no_existe(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $repo = new LabConfigRepository($pdo);
        $this->assertNull($repo->get('inexistente'));
    }

    public function test_get_usa_cache_de_instancia(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(['valor' => 'bar']);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $repo = new LabConfigRepository($pdo);
        $repo->get('foo');
        $repo->get('foo');
    }

    public function test_set_invalida_cache(): void
    {
        $selectStmt = $this->createMock(PDOStatement::class);
        $selectStmt->method('execute')->willReturn(true);
        $selectStmt->method('fetch')->willReturnOnConsecutiveCalls(
            ['valor' => 'old'],
            ['valor' => 'new'],
        );
        $upsertStmt = $this->createMock(PDOStatement::class);
        $upsertStmt->method('execute')->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($selectStmt, $upsertStmt) {
            return str_starts_with(trim($sql), 'SELECT') ? $selectStmt : $upsertStmt;
        });

        $repo = new LabConfigRepository($pdo);
        $this->assertSame('old', $repo->get('foo'));
        $repo->set('foo', 'new');
        $this->assertSame('new', $repo->get('foo'));
    }
}
