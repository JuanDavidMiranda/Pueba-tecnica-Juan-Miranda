<?php

declare(strict_types=1);

namespace App;

use App\Database\Connection;
use App\Database\Migrator;
use App\Database\Seeder;
use App\Http\Kernel;
use App\Http\Router;
use App\Support\Env;

final class Bootstrap
{
    private static string $basePath = '';

    private static bool $ready = false;

    public static function init(string $basePath): void
    {
        self::$basePath = $basePath;
        Env::load($basePath . '/.env');
        if (Env::get('DB_HOST') === null) {
            Env::load($basePath . '/.env.example');
        }

        date_default_timezone_set('America/Bogota');
        mb_internal_encoding('UTF-8');
    }

    /**
     * Aplica migraciones pendientes y completa catálogos vacíos.
     * Dentro del mismo proceso solo corre una vez.
     */
    public static function prepareDatabase(): void
    {
        if (self::$ready) {
            return;
        }

        $pdo = Connection::get();
        Migrator::migrate($pdo);
        Seeder::run($pdo, Env::get('APP_ENV') === 'testing');
        self::$ready = true;
    }

    public static function router(): Router
    {
        return (new Kernel(self::$basePath))->router();
    }
}
