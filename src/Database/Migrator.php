<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Migrator
{
    public static function migrate(PDO $pdo): void
    {
        try {
            self::ensureLedger($pdo);
            $root = dirname(__DIR__, 2) . '/database/migrations/';
            self::apply($pdo, '001_schema', $root . '001_schema.sql');
            self::apply($pdo, '002_municipio_departamento', $root . '002_municipio_departamento.sql');
        } catch (PDOException $exception) {
            error_log('[sihos] migration: ' . $exception->getMessage());
            throw new RuntimeException('No se pudieron aplicar las migraciones.', 0, $exception);
        }
    }

    public static function runFile(PDO $pdo, string $path): void
    {
        foreach (self::statements(self::read($path)) as $statement) {
            self::execute($pdo, $statement);
        }
    }

    private static function apply(PDO $pdo, string $id, string $path): void
    {
        if (self::applied($pdo, $id)) {
            return;
        }

        foreach (self::statements(self::read($path)) as $statement) {
            try {
                self::execute($pdo, $statement);
            } catch (PDOException $exception) {
                if (!self::alreadyPresent($exception)) {
                    throw $exception;
                }
            }
        }

        $insert = $pdo->prepare('INSERT IGNORE INTO schema_migrations (id) VALUES (:id)');
        $insert->execute(['id' => $id]);
    }

    private static function ensureLedger(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id VARCHAR(190) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function applied(PDO $pdo, string $id): bool
    {
        $statement = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }

    private static function read(string $path): string
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('No se encontró el script SQL: ' . $path);
        }

        return $sql;
    }

    private static function execute(PDO $pdo, string $statement): void
    {
        if (preg_match('/^(SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', ltrim($statement)) === 1) {
            $query = $pdo->query($statement);
            if ($query !== false) {
                $query->fetchAll();
                $query->closeCursor();
            }

            return;
        }

        $pdo->exec($statement);
    }

    /**
     * Un índice o una clave foránea que ya existe no debe frenar el arranque.
     * 1062 (dato duplicado) sí se propaga: indica filas que violan la regla.
     */
    private static function alreadyPresent(PDOException $exception): bool
    {
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        if (in_array($driverCode, [1022, 1061, 121, 1826], true)) {
            return true;
        }

        $message = $exception->getMessage();

        return str_contains($message, 'errno: 121')
            || str_contains($message, 'Duplicate key name')
            || str_contains($message, 'Duplicate foreign key constraint name');
    }

    /**
     * @return list<string>
     */
    private static function statements(string $sql): array
    {
        $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;
        $parts = array_map('trim', explode(';', $sql));

        return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
    }
}
