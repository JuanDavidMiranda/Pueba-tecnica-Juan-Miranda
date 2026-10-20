<?php

declare(strict_types=1);

namespace App\Http;

use App\Controller\CatalogController;
use App\Controller\PacienteController;
use App\Controller\UserController;
use App\Database\Connection;
use App\Http\Middleware\Authenticate;
use App\Presenter\PacientePresenter;
use App\Repository\CatalogRepository;
use App\Repository\PacienteRepository;
use App\Repository\UserRepository;
use App\Security\ImageUploader;
use App\Security\JwtService;
use App\Support\Env;
use App\Validation\PacienteValidator;
use RuntimeException;

/**
 * Compone dependencias y registra las rutas.
 * El arranque del entorno y de la base queda en Bootstrap.
 */
final class Kernel
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function router(): Router
    {
        $secret = Env::get('JWT_SECRET') ?? '';
        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET debe tener al menos 32 caracteres.');
        }

        $pdo = Connection::get();
        $users = new UserRepository($pdo);
        $jwt = new JwtService($secret, (int) (Env::get('JWT_TTL', '28800') ?? '28800'));
        $authenticate = new Authenticate($jwt, $users);
        $catalogs = new CatalogRepository($pdo);

        $pacientes = new PacienteController(
            new PacienteRepository($pdo),
            $catalogs,
            new PacienteValidator(),
            new ImageUploader($this->basePath . '/public/uploads'),
            new PacientePresenter(),
        );
        $catalog = new CatalogController($catalogs);
        $accounts = new UserController($users, $jwt, $authenticate);

        $router = new Router($authenticate);
        $router->add('POST', '/api/auth/login', $accounts->login(...));
        $router->add('POST', '/api/auth/logout', $accounts->logout(...), true);
        $router->add('GET', '/api/auth/me', $accounts->me(...), true);
        $router->add('GET', '/api/pacientes', $pacientes->index(...), true);
        $router->add('POST', '/api/pacientes', $pacientes->store(...), true);
        $router->add('GET', '/api/pacientes/{id}', $pacientes->show(...), true);
        $router->add('PUT', '/api/pacientes/{id}', $pacientes->update(...), true);
        $router->add('POST', '/api/pacientes/{id}', $pacientes->update(...), true);
        $router->add('DELETE', '/api/pacientes/{id}', $pacientes->destroy(...), true);
        $router->add('GET', '/api/departamentos', $catalog->departamentos(...), true);
        $router->add('GET', '/api/municipios', $catalog->municipios(...), true);
        $router->add('GET', '/api/tipos-documento', $catalog->tiposDocumento(...), true);
        $router->add('GET', '/api/generos', $catalog->generos(...), true);

        return $router;
    }
}
