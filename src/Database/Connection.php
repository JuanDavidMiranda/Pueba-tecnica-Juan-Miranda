<?php

declare(strict_types=1);

namespace App\Database;

use App\Support\Env;
use PDO;
use RuntimeException;

final class Connection
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $name = Env::get('DB_NAME');
        $user = Env::get('DB_USER');
        $pass = Env::get('DB_PASS', '');

        if ($name === null || $user === null) {
            throw new RuntimeException('Faltan DB_NAME o DB_USER en el entorno.');
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if (defined('Pdo\\Mysql::ATTR_MULTI_STATEMENTS')) {
            $options[\Pdo\Mysql::ATTR_MULTI_STATEMENTS] = false;
        } elseif (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
            $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
        }

        self::$pdo = new PDO($dsn, $user, $pass, $options);

        return self::$pdo;
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
