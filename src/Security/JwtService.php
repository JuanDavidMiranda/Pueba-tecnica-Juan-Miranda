<?php

declare(strict_types=1);

namespace App\Security;

use InvalidArgumentException;

final class JwtService
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 28800,
    ) {
        if (strlen($this->secret) < 32) {
            throw new InvalidArgumentException('El secreto JWT es demasiado corto.');
        }
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function issue(array $claims, ?int $ttl = null): string
    {
        $now = time();
        $payload = $claims;
        $payload['iss'] = 'sihos';
        $payload['iat'] = $now;
        $payload['exp'] = $now + ($ttl ?? $this->ttl);

        $header = $this->encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $body = $this->encode($payload);
        $signature = $this->sign($header . '.' . $body);

        return $header . '.' . $body . '.' . $signature;
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
            throw new InvalidArgumentException('Token malformado.');
        }

        [$headerPart, $bodyPart, $signature] = $parts;
        $expected = $this->sign($headerPart . '.' . $bodyPart);
        if (!hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('Firma inválida.');
        }

        $header = $this->decode($headerPart);
        if (($header['alg'] ?? null) !== 'HS256') {
            throw new InvalidArgumentException('Algoritmo no permitido.');
        }

        $payload = $this->decode($bodyPart);
        if (($payload['iss'] ?? null) !== 'sihos') {
            throw new InvalidArgumentException('Emisor no válido.');
        }
        if (!isset($payload['exp']) || (int) $payload['exp'] < time()) {
            throw new InvalidArgumentException('Token expirado.');
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encode(array $data): string
    {
        return $this->base64(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $value): array
    {
        $decoded = json_decode($this->base64Decode($value), true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Token malformado.');
        }

        return $decoded;
    }

    private function sign(string $data): string
    {
        return $this->base64(hash_hmac('sha256', $data, $this->secret, true));
    }

    private function base64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function base64Decode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $raw = base64_decode(strtr($value, '-_', '+/'), true);
        if ($raw === false) {
            throw new InvalidArgumentException('Token malformado.');
        }

        return $raw;
    }
}
