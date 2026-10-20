<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\Authenticate;
use InvalidArgumentException;

final class Router
{
    /**
     * @var list<array{method: string, regex: string, handler: callable, auth: bool}>
     */
    private array $routes = [];

    public function __construct(private readonly Authenticate $authenticate)
    {
    }

    public function add(string $method, string $path, callable $handler, bool $auth = false): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[0-9]+)', $path);
        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Ruta inválida.');
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'auth' => $auth,
        ];
    }

    public function dispatch(Request $request): JsonResponse
    {
        $matches = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $captured) !== 1) {
                continue;
            }

            $params = [];
            foreach ($captured as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = (string) $value;
                }
            }
            $matches[] = [$route, $params];
        }

        if ($matches === []) {
            return new JsonResponse(404, ['message' => 'Ruta no encontrada.']);
        }

        foreach ($matches as [$route, $params]) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            if ($route['auth']) {
                $user = $this->authenticate->attempt($request);
                if ($user instanceof JsonResponse) {
                    return $user;
                }
                $request = $request->withAttribute('user', $user);
            }

            return ($route['handler'])($request, $params);
        }

        return new JsonResponse(405, ['message' => 'Método no permitido.']);
    }
}
