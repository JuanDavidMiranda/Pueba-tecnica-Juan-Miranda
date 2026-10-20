<?php

declare(strict_types=1);

namespace Tests;

use App\Bootstrap;
use App\Database\Connection;
use App\Database\Seeder;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Router;
use App\Repository\PacienteRepository;
use PDOException;
use PHPUnit\Framework\TestCase;

final class PacienteApiTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        Connection::get()->exec('DELETE FROM paciente');
        $this->router = Bootstrap::router();
    }

    public function testCatalogSeederMatchesTheBrief(): void
    {
        $pdo = Connection::get();
        self::assertSame(5, (int) $pdo->query('SELECT COUNT(*) FROM departamentos')->fetchColumn());
        self::assertSame(10, (int) $pdo->query('SELECT COUNT(*) FROM municipios')->fetchColumn());
        self::assertSame(2, (int) $pdo->query('SELECT COUNT(*) FROM tipos_documento')->fetchColumn());

        foreach ($pdo->query('SELECT COUNT(*) AS total FROM municipios GROUP BY departamento_id') as $row) {
            self::assertSame(2, (int) $row['total']);
        }

        Seeder::patients($pdo);
        self::assertSame(5, (int) $pdo->query('SELECT COUNT(*) FROM paciente')->fetchColumn());
    }

    public function testPatientsRequireAuthentication(): void
    {
        $response = $this->router->dispatch(Request::fake('GET', '/api/pacientes'));

        self::assertSame(401, $response->status);
    }

    public function testCreateValidatesEmailAndMunicipio(): void
    {
        $token = $this->token();
        $refs = $this->references();

        $invalid = $this->send('POST', '/api/pacientes', $token, $this->payload($refs, [
            'correo' => 'correo-malo',
        ]));
        self::assertSame(422, $invalid->status);
        self::assertArrayHasKey('correo', $invalid->body['errors']);

        $mismatch = $this->send('POST', '/api/pacientes', $token, $this->payload($refs, [
            'departamento_id' => $refs['otroDepartamento'],
        ]));
        self::assertSame(422, $mismatch->status);
        self::assertArrayHasKey('municipio_id', $mismatch->body['errors']);
    }

    public function testCrudSearchAndSqlInjection(): void
    {
        $token = $this->token();
        $refs = $this->references();

        $created = $this->send('POST', '/api/pacientes', $token, $this->payload($refs));
        self::assertSame(201, $created->status);
        self::assertSame('Paciente creado correctamente.', $created->body['message']);
        self::assertSame('Ana María García López', $created->body['data']['nombre_completo']);
        $id = (int) $created->body['data']['id'];

        $duplicate = $this->send('POST', '/api/pacientes', $token, $this->payload($refs, [
            'numero_documento' => '99999999',
            'correo' => 'ana.garcia@example.com',
        ]));
        self::assertSame(422, $duplicate->status);

        $updated = $this->send('PUT', '/api/pacientes/' . $id, $token, $this->payload($refs, [
            'correo' => 'ana.actualizada@example.com',
            'nombre2' => 'Lucía',
        ]));
        self::assertSame(200, $updated->status);
        self::assertSame('Paciente actualizado correctamente.', $updated->body['message']);
        self::assertSame('ana.actualizada@example.com', $updated->body['data']['correo']);

        $byName = $this->send('GET', '/api/pacientes?q=' . rawurlencode('Ana García'), $token);
        self::assertSame(200, $byName->status);
        self::assertSame(1, $byName->body['meta']['total']);

        $byEmail = $this->send('GET', '/api/pacientes?q=' . rawurlencode('ana.actualizada@'), $token);
        self::assertSame(1, $byEmail->body['meta']['total']);

        $injection = $this->send('GET', '/api/pacientes?q=' . rawurlencode("' OR 1=1 --"), $token);
        self::assertSame(0, $injection->body['meta']['total']);

        $wildcard = $this->send('GET', '/api/pacientes?q=' . rawurlencode('%'), $token);
        self::assertSame(0, $wildcard->body['meta']['total']);

        $deleted = $this->send('DELETE', '/api/pacientes/' . $id, $token);
        self::assertSame(200, $deleted->status);
        self::assertSame('Paciente eliminado correctamente.', $deleted->body['message']);

        $missing = $this->send('GET', '/api/pacientes/' . $id, $token);
        self::assertSame(404, $missing->status);
    }

    public function testPagination(): void
    {
        $token = $this->token();
        $refs = $this->references();
        $repository = new PacienteRepository(Connection::get());

        for ($index = 1; $index <= 9; $index++) {
            $repository->create([
                'tipo_documento_id' => $refs['tipo'],
                'numero_documento' => sprintf('8800000%02d', $index),
                'nombre1' => 'Paciente',
                'nombre2' => null,
                'apellido1' => 'Prueba',
                'apellido2' => null,
                'genero_id' => $refs['genero'],
                'departamento_id' => $refs['departamento'],
                'municipio_id' => $refs['municipio'],
                'correo' => "lote{$index}@example.com",
                'foto' => null,
            ]);
        }

        $pageTwo = $this->send('GET', '/api/pacientes?page=2&per_page=4', $token);
        self::assertSame(200, $pageTwo->status);
        self::assertCount(4, $pageTwo->body['data']);
        self::assertSame(9, $pageTwo->body['meta']['total']);
        self::assertSame(3, $pageTwo->body['meta']['last_page']);
        self::assertSame(2, $pageTwo->body['meta']['page']);

        $pageThree = $this->send('GET', '/api/pacientes?page=3&per_page=4', $token);
        self::assertCount(1, $pageThree->body['data']);
    }

    public function testDatabaseRejectsMunicipioFromAnotherDepartment(): void
    {
        $refs = $this->references();
        $repository = new PacienteRepository(Connection::get());

        $this->expectException(PDOException::class);
        $repository->create([
            'tipo_documento_id' => $refs['tipo'],
            'numero_documento' => '77000001',
            'nombre1' => 'Ana',
            'nombre2' => null,
            'apellido1' => 'García',
            'apellido2' => null,
            'genero_id' => $refs['genero'],
            'departamento_id' => $refs['otroDepartamento'],
            'municipio_id' => $refs['municipio'],
            'correo' => 'inconsistente@example.com',
            'foto' => null,
        ]);
    }

    private function token(): string
    {
        $response = $this->router->dispatch(Request::fake('POST', '/api/auth/login', body: [
            'email' => 'admin@sihos.local',
            'password' => '1234567890',
        ]));
        self::assertSame(200, $response->status);

        return (string) $response->body['token'];
    }

    /**
     * @return array{tipo: int, genero: int, departamento: int, municipio: int, otroDepartamento: int}
     */
    private function references(): array
    {
        $pdo = Connection::get();

        return [
            'tipo' => (int) $pdo->query("SELECT id FROM tipos_documento WHERE nombre = 'Cédula de ciudadanía'")->fetchColumn(),
            'genero' => (int) $pdo->query("SELECT id FROM genero WHERE nombre = 'Femenino'")->fetchColumn(),
            'departamento' => (int) $pdo->query("SELECT id FROM departamentos WHERE nombre = 'Antioquia'")->fetchColumn(),
            'municipio' => (int) $pdo->query("SELECT id FROM municipios WHERE nombre = 'Medellín'")->fetchColumn(),
            'otroDepartamento' => (int) $pdo->query("SELECT id FROM departamentos WHERE nombre = 'Atlántico'")->fetchColumn(),
        ];
    }

    /**
     * @param array{tipo: int, genero: int, departamento: int, municipio: int, otroDepartamento: int} $refs
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $refs, array $overrides = []): array
    {
        return $overrides + [
            'tipo_documento_id' => $refs['tipo'],
            'numero_documento' => '1023456789',
            'nombre1' => 'Ana',
            'nombre2' => 'María',
            'apellido1' => 'García',
            'apellido2' => 'López',
            'genero_id' => $refs['genero'],
            'departamento_id' => $refs['departamento'],
            'municipio_id' => $refs['municipio'],
            'correo' => 'ana.garcia@example.com',
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    private function send(string $method, string $path, string $token, array $body = []): JsonResponse
    {
        $query = [];
        $route = $path;
        if (str_contains($path, '?')) {
            [$route, $rawQuery] = explode('?', $path, 2);
            parse_str($rawQuery, $query);
        }

        return $this->router->dispatch(Request::fake($method, $route, $query, $body, bearer: $token));
    }
}
