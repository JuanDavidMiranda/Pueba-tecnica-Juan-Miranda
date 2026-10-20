<?php

declare(strict_types=1);

namespace Tests;

use App\Controller\UserController;
use App\Database\Connection;
use App\Http\Middleware\Authenticate;
use App\Http\Request;
use App\Repository\UserRepository;
use App\Security\JwtService;
use App\Support\Env;
use PHPUnit\Framework\TestCase;

final class UserControllerTest extends TestCase
{
    private UserController $controller;

    protected function setUp(): void
    {
        $secret = Env::get('JWT_SECRET') ?? '';
        $users = new UserRepository(Connection::get());
        $jwt = new JwtService($secret, 3600);
        $this->controller = new UserController($users, $jwt, new Authenticate($jwt, $users));
    }

    public function testLoginRejectsMalformedEmail(): void
    {
        $response = $this->controller->login(Request::fake('POST', '/api/auth/login', body: [
            'email' => 'admin',
            'password' => '1234567890',
        ]));

        self::assertSame(422, $response->status);
        self::assertArrayHasKey('email', $response->body['errors']);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $response = $this->controller->login(Request::fake('POST', '/api/auth/login', body: [
            'email' => 'admin@sihos.local',
            'password' => 'incorrecta',
        ]));

        self::assertSame(401, $response->status);
        self::assertSame('Credenciales incorrectas.', $response->body['message']);
    }

    public function testLoginReturnsJwtAndMeReadsIt(): void
    {
        $login = $this->controller->login(Request::fake('POST', '/api/auth/login', body: [
            'email' => 'admin@sihos.local',
            'password' => '1234567890',
        ]));

        self::assertSame(200, $login->status);
        self::assertArrayHasKey('token', $login->body);
        self::assertSame('admin@sihos.local', $login->body['user']['email']);
        self::assertArrayNotHasKey('password', $login->body['user']);

        $me = $this->controller->me(Request::fake('GET', '/api/auth/me', bearer: $login->body['token']));
        self::assertSame(200, $me->status);
        self::assertSame('Administrador SIHOS', $me->body['data']['nombre']);
    }

    public function testMeWithoutTokenIsUnauthorized(): void
    {
        $response = $this->controller->me(Request::fake('GET', '/api/auth/me'));

        self::assertSame(401, $response->status);
    }
}
