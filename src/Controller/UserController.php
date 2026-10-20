<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Middleware\Authenticate;
use App\Http\Request;
use App\Repository\UserRepository;
use App\Security\JwtService;

/**
 * Autenticación del usuario que opera el censo.
 * El CRUD de pacientes vive en PacienteController para mantener el MVC separado.
 */
final class UserController
{
    private const DUMMY_HASH = '$2y$10$3kDltzMggeXXWDgHszCXG.v4803wBymPCV7WssLJdfKVb1p6OLplO';

    public function __construct(
        private readonly UserRepository $users,
        private readonly JwtService $jwt,
        private readonly Authenticate $authenticate,
    ) {
    }

    /**
     * @param array<string, string> $params
     */
    public function login(Request $request, array $params = []): JsonResponse
    {
        $email = strtolower(trim((string) ($request->body['email'] ?? '')));
        $password = (string) ($request->body['password'] ?? '');
        $errors = [];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'Ingrese un correo válido.';
        }
        if ($password === '') {
            $errors['password'][] = 'Ingrese la contraseña.';
        }
        if ($errors !== []) {
            return new JsonResponse(422, [
                'message' => 'Los datos no son válidos.',
                'errors' => $errors,
            ]);
        }

        $user = $this->users->findByEmail($email);
        $hash = is_array($user) ? (string) $user['password'] : self::DUMMY_HASH;
        $valid = password_verify($password, $hash);
        if (!is_array($user) || !$valid) {
            return new JsonResponse(401, ['message' => 'Credenciales incorrectas.']);
        }

        $publicUser = [
            'id' => (int) $user['id'],
            'nombre' => (string) $user['nombre'],
            'email' => (string) $user['email'],
        ];

        return new JsonResponse(200, [
            'message' => 'Sesión iniciada.',
            'token' => $this->jwt->issue([
                'sub' => $publicUser['id'],
                'email' => $publicUser['email'],
                'nombre' => $publicUser['nombre'],
            ]),
            'user' => $publicUser,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function me(Request $request, array $params = []): JsonResponse
    {
        $user = $request->attribute('user');
        if (!is_array($user)) {
            $user = $this->authenticate->attempt($request);
            if ($user instanceof JsonResponse) {
                return $user;
            }
        }

        return new JsonResponse(200, [
            'data' => [
                'id' => (int) $user['id'],
                'nombre' => (string) $user['nombre'],
                'email' => (string) $user['email'],
            ],
        ]);
    }

    /**
     * El token JWT no se invalida en servidor: el cliente debe descartarlo.
     *
     * @param array<string, string> $params
     */
    public function logout(Request $request, array $params = []): JsonResponse
    {
        return new JsonResponse(200, ['message' => 'Sesión cerrada.']);
    }
}
