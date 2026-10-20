<?php

declare(strict_types=1);

namespace Tests;

use App\Security\JwtService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    private JwtService $jwt;

    protected function setUp(): void
    {
        $this->jwt = new JwtService('sihos-prueba-tecnica-jwt-secret-cambiar-en-produccion-2026', 3600);
    }

    public function testIssueAndParseRoundTrip(): void
    {
        $token = $this->jwt->issue(['sub' => 7, 'email' => 'admin@sihos.local']);
        $payload = $this->jwt->parse($token);

        self::assertSame(7, $payload['sub']);
        self::assertSame('admin@sihos.local', $payload['email']);
        self::assertSame('sihos', $payload['iss']);
        self::assertArrayHasKey('exp', $payload);
    }

    public function testRejectsTamperedToken(): void
    {
        $token = $this->jwt->issue(['sub' => 1]);
        $parts = explode('.', $token);
        $parts[2] = ($parts[2][0] === 'a' ? 'b' : 'a') . substr($parts[2], 1);
        $tampered = implode('.', $parts);

        $this->expectException(InvalidArgumentException::class);
        $this->jwt->parse($tampered);
    }

    public function testRejectsExpiredToken(): void
    {
        $token = $this->jwt->issue(['sub' => 1], -30);

        $this->expectException(InvalidArgumentException::class);
        $this->jwt->parse($token);
    }

    public function testRejectsTokenSignedWithAnotherSecret(): void
    {
        $other = new JwtService(str_repeat('x', 40), 3600);
        $token = $other->issue(['sub' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->jwt->parse($token);
    }
}
