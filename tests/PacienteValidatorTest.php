<?php

declare(strict_types=1);

namespace Tests;

use App\Validation\PacienteValidator;
use PHPUnit\Framework\TestCase;

final class PacienteValidatorTest extends TestCase
{
    private PacienteValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PacienteValidator();
    }

    public function testAcceptsAccentedNamesAndNormalizesEmail(): void
    {
        $result = $this->validator->validate($this->payload());

        self::assertSame([], $result['errors']);
        self::assertSame('José', $result['data']['nombre1']);
        self::assertNull($result['data']['nombre2']);
        self::assertSame("D'Amico", $result['data']['apellido2']);
        self::assertSame('jose.nunez@example.com', $result['data']['correo']);
        self::assertSame('1023456789', $result['data']['numero_documento']);
    }

    public function testRejectsInvalidEmailShortNameAndDocument(): void
    {
        $payload = $this->payload([
            'correo' => 'no-es-correo',
            'nombre1' => 'A',
            'numero_documento' => '12',
            'departamento_id' => '',
        ]);

        $result = $this->validator->validate($payload);

        self::assertArrayHasKey('correo', $result['errors']);
        self::assertArrayHasKey('nombre1', $result['errors']);
        self::assertArrayHasKey('numero_documento', $result['errors']);
        self::assertArrayHasKey('departamento_id', $result['errors']);
    }

    public function testOptionalSecondNameMayBeEmpty(): void
    {
        $result = $this->validator->validate($this->payload([
            'nombre2' => '   ',
            'apellido2' => '',
        ]));

        self::assertSame([], $result['errors']);
        self::assertNull($result['data']['nombre2']);
        self::assertNull($result['data']['apellido2']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'tipo_documento_id' => '1',
            'numero_documento' => '1023456789',
            'nombre1' => 'José',
            'nombre2' => '',
            'apellido1' => 'Núñez',
            'apellido2' => "D'Amico",
            'genero_id' => 2,
            'departamento_id' => 1,
            'municipio_id' => 3,
            'correo' => 'Jose.Nunez@Example.com',
        ];
    }
}
