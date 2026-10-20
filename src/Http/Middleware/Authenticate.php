<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Repository\UserRepository;
use App\Security\JwtService;
use InvalidArgumentException;

final class Authenticate
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * @return array{id: int, nombre: string, email: string}|JsonResponse
     */
    public function attempt(Request $request): array|JsonResponse
    {
        if ($request->bearer === null || $request->bearer === '') {
            return new JsonResponse(401, ['message' => 'Sesión no válida o expirada.']);
        }

        try {
            $payload = $this->jwt->parse($request->bearer);
        } catch (InvalidArgumentException) {
            return new JsonResponse(401, ['message' => 'Sesión no válida o expirada.']);
        }

        $user = $this->users->findPublicById((int) ($payload['sub'] ?? 0));
        if ($user === null) {
            return new JsonResponse(401, ['message' => 'Sesión no válida o expirada.']);
        }

        return $user;
    }
}
