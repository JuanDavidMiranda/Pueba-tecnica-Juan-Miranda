<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class Seeder
{
    public static function run(PDO $pdo, bool $resetAdminPassword = false): void
    {
        self::catalogs($pdo);
        self::admin($pdo, $resetAdminPassword);
        self::patients($pdo);
    }

    public static function catalogs(PDO $pdo): void
    {
        if ((int) $pdo->query('SELECT COUNT(*) FROM departamentos')->fetchColumn() === 0) {
            $insert = $pdo->prepare('INSERT INTO departamentos (nombre) VALUES (:nombre)');
            foreach (['Antioquia', 'Atlántico', 'Cundinamarca', 'Santander', 'Valle del Cauca'] as $nombre) {
                $insert->execute(['nombre' => $nombre]);
            }
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM municipios')->fetchColumn() === 0) {
            $departamentos = [];
            foreach ($pdo->query('SELECT id, nombre FROM departamentos') as $row) {
                $departamentos[$row['nombre']] = (int) $row['id'];
            }

            $municipios = [
                'Antioquia' => ['Medellín', 'Envigado'],
                'Atlántico' => ['Barranquilla', 'Soledad'],
                'Cundinamarca' => ['Soacha', 'Fusagasugá'],
                'Santander' => ['Bucaramanga', 'Floridablanca'],
                'Valle del Cauca' => ['Cali', 'Palmira'],
            ];

            $insert = $pdo->prepare(
                'INSERT INTO municipios (departamento_id, nombre) VALUES (:departamento_id, :nombre)'
            );
            foreach ($municipios as $departamento => $nombres) {
                foreach ($nombres as $nombre) {
                    $insert->execute([
                        'departamento_id' => $departamentos[$departamento],
                        'nombre' => $nombre,
                    ]);
                }
            }
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM tipos_documento')->fetchColumn() === 0) {
            $insert = $pdo->prepare('INSERT INTO tipos_documento (nombre) VALUES (:nombre)');
            $insert->execute(['nombre' => 'Cédula de ciudadanía']);
            $insert->execute(['nombre' => 'Tarjeta de identidad']);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM genero')->fetchColumn() === 0) {
            $insert = $pdo->prepare('INSERT INTO genero (nombre) VALUES (:nombre)');
            $insert->execute(['nombre' => 'Masculino']);
            $insert->execute(['nombre' => 'Femenino']);
        }
    }

    public static function admin(PDO $pdo, bool $resetPassword = false): void
    {
        $email = 'admin@sihos.local';
        $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $existing->execute(['email' => $email]);
        $id = $existing->fetchColumn();

        if ($id === false) {
            $insert = $pdo->prepare(
                'INSERT INTO users (nombre, email, password) VALUES (:nombre, :email, :password)'
            );
            $insert->execute([
                'nombre' => 'Administrador SIHOS',
                'email' => $email,
                'password' => self::adminPassword(),
            ]);
            return;
        }

        if ($resetPassword) {
            $update = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $update->execute(['password' => self::adminPassword(), 'id' => $id]);
        }
    }

    public static function patients(PDO $pdo): void
    {
        if ((int) $pdo->query('SELECT COUNT(*) FROM paciente')->fetchColumn() > 0) {
            return;
        }

        $tipo = self::idByName($pdo, 'tipos_documento');
        $genero = self::idByName($pdo, 'genero');
        $departamento = self::idByName($pdo, 'departamentos');
        $municipio = self::municipioIds($pdo);

        $rows = [
            ['CC', '1023456789', 'Ana', 'María', 'García', 'López', 'Femenino', 'Antioquia', 'Medellín', 'ana.garcia@example.com'],
            ['CC', '80123456', 'Carlos', 'Andrés', 'Pérez', 'Díaz', 'Masculino', 'Atlántico', 'Barranquilla', 'carlos.perez@example.com'],
            ['TI', '1098765432', 'Luisa', 'Fernanda', 'Ramírez', 'Soto', 'Femenino', 'Valle del Cauca', 'Cali', 'luisa.ramirez@example.com'],
            ['CC', '91555012', 'Jorge', null, 'Muñoz', 'Castro', 'Masculino', 'Santander', 'Bucaramanga', 'jorge.munoz@example.com'],
            ['CC', '1033445566', 'Sofía', 'Alejandra', 'Herrera', null, 'Femenino', 'Cundinamarca', 'Soacha', 'sofia.herrera@example.com'],
        ];

        $tipoCodigo = [
            'CC' => 'Cédula de ciudadanía',
            'TI' => 'Tarjeta de identidad',
        ];

        $insert = $pdo->prepare(
            'INSERT INTO paciente (
                tipo_documento_id, numero_documento, nombre1, nombre2, apellido1, apellido2,
                genero_id, departamento_id, municipio_id, correo
            ) VALUES (
                :tipo_documento_id, :numero_documento, :nombre1, :nombre2, :apellido1, :apellido2,
                :genero_id, :departamento_id, :municipio_id, :correo
            )'
        );

        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                [$codigo, $documento, $nombre1, $nombre2, $apellido1, $apellido2, $generoNombre, $departamentoNombre, $municipioNombre, $correo] = $row;
                $insert->execute([
                    'tipo_documento_id' => $tipo[$tipoCodigo[$codigo]],
                    'numero_documento' => $documento,
                    'nombre1' => $nombre1,
                    'nombre2' => $nombre2,
                    'apellido1' => $apellido1,
                    'apellido2' => $apellido2,
                    'genero_id' => $genero[$generoNombre],
                    'departamento_id' => $departamento[$departamentoNombre],
                    'municipio_id' => $municipio[$departamentoNombre . '|' . $municipioNombre],
                    'correo' => $correo,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private static function adminPassword(): string
    {
        return password_hash('1234567890', PASSWORD_DEFAULT);
    }

    /**
     * @return array<string, int>
     */
    private static function idByName(PDO $pdo, string $table): array
    {
        $allowed = ['tipos_documento', 'genero', 'departamentos'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('Tabla no permitida.');
        }

        $map = [];
        foreach ($pdo->query("SELECT id, nombre FROM {$table}") as $row) {
            $map[$row['nombre']] = (int) $row['id'];
        }

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private static function municipioIds(PDO $pdo): array
    {
        $sql = 'SELECT m.id, m.nombre, d.nombre AS departamento
                FROM municipios m
                INNER JOIN departamentos d ON d.id = m.departamento_id';
        $map = [];
        foreach ($pdo->query($sql) as $row) {
            $map[$row['departamento'] . '|' . $row['nombre']] = (int) $row['id'];
        }

        return $map;
    }
}
