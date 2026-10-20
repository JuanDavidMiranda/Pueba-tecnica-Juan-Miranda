<?php

declare(strict_types=1);

namespace App\Repository;

use InvalidArgumentException;
use PDO;

final class CatalogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function departamentos(): array
    {
        return $this->listTable('departamentos');
    }

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function tiposDocumento(): array
    {
        return $this->listTable('tipos_documento');
    }

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function generos(): array
    {
        return $this->listTable('genero');
    }

    /**
     * @return list<array{id: int, nombre: string, departamento_id: int}>
     */
    public function municipios(?int $departamentoId): array
    {
        $sql = 'SELECT id, nombre, departamento_id FROM municipios';
        $params = [];
        if ($departamentoId !== null) {
            $sql .= ' WHERE departamento_id = :departamento_id';
            $params['departamento_id'] = $departamentoId;
        }
        $sql .= ' ORDER BY nombre';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'nombre' => (string) $row['nombre'],
                'departamento_id' => (int) $row['departamento_id'],
            ];
        }, $statement->fetchAll());
    }

    /**
     * @return array{id: int, nombre: string, departamento_id: int}|null
     */
    public function findMunicipio(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nombre, departamento_id FROM municipios WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if ($row === false) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'nombre' => (string) $row['nombre'],
            'departamento_id' => (int) $row['departamento_id'],
        ];
    }

    public function exists(string $table, int $id): bool
    {
        $allowed = ['tipos_documento', 'genero', 'departamentos', 'municipios'];
        if (!in_array($table, $allowed, true)) {
            throw new InvalidArgumentException('Tabla no permitida.');
        }

        $statement = $this->pdo->prepare("SELECT 1 FROM {$table} WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return list<array{id: int, nombre: string}>
     */
    private function listTable(string $table): array
    {
        $allowed = ['departamentos', 'tipos_documento', 'genero'];
        if (!in_array($table, $allowed, true)) {
            throw new InvalidArgumentException('Tabla no permitida.');
        }

        $statement = $this->pdo->query("SELECT id, nombre FROM {$table} ORDER BY nombre");
        if ($statement === false) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'nombre' => (string) $row['nombre'],
            ];
        }, $statement->fetchAll());
    }
}
