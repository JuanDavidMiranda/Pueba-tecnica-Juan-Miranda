<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class PacienteRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int, page: int}
     */
    public function paginate(string $term, int $page, int $perPage): array
    {
        [$where, $params] = $this->filter($term);

        $count = $this->pdo->prepare('SELECT COUNT(*) FROM paciente p ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->joinedSelect() . '
                ' . $where . '
                ORDER BY p.apellido1 ASC, p.apellido2 ASC, p.nombre1 ASC, p.id ASC
                LIMIT :limit OFFSET :offset';

        $statement = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'rows' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $sql = $this->joinedSelect() . '
                WHERE p.id = :id
                LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO paciente (
                    tipo_documento_id, numero_documento, nombre1, nombre2, apellido1, apellido2,
                    genero_id, departamento_id, municipio_id, correo, foto
                ) VALUES (
                    :tipo_documento_id, :numero_documento, :nombre1, :nombre2, :apellido1, :apellido2,
                    :genero_id, :departamento_id, :municipio_id, :correo, :foto
                )';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'tipo_documento_id' => $data['tipo_documento_id'],
            'numero_documento' => $data['numero_documento'],
            'nombre1' => $data['nombre1'],
            'nombre2' => $data['nombre2'],
            'apellido1' => $data['apellido1'],
            'apellido2' => $data['apellido2'],
            'genero_id' => $data['genero_id'],
            'departamento_id' => $data['departamento_id'],
            'municipio_id' => $data['municipio_id'],
            'correo' => $data['correo'],
            'foto' => $data['foto'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $fotoSql = array_key_exists('foto', $data) ? ', foto = :foto' : '';
        $sql = 'UPDATE paciente SET
                    tipo_documento_id = :tipo_documento_id,
                    numero_documento = :numero_documento,
                    nombre1 = :nombre1,
                    nombre2 = :nombre2,
                    apellido1 = :apellido1,
                    apellido2 = :apellido2,
                    genero_id = :genero_id,
                    departamento_id = :departamento_id,
                    municipio_id = :municipio_id,
                    correo = :correo' . $fotoSql . '
                WHERE id = :id';

        $params = [
            'tipo_documento_id' => $data['tipo_documento_id'],
            'numero_documento' => $data['numero_documento'],
            'nombre1' => $data['nombre1'],
            'nombre2' => $data['nombre2'],
            'apellido1' => $data['apellido1'],
            'apellido2' => $data['apellido2'],
            'genero_id' => $data['genero_id'],
            'departamento_id' => $data['departamento_id'],
            'municipio_id' => $data['municipio_id'],
            'correo' => $data['correo'],
            'id' => $id,
        ];
        if (array_key_exists('foto', $data)) {
            $params['foto'] = $data['foto'];
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM paciente WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function emailTaken(string $email, ?int $ignoreId): bool
    {
        if ($ignoreId === null) {
            $statement = $this->pdo->prepare('SELECT 1 FROM paciente WHERE correo = :correo LIMIT 1');
            $statement->execute(['correo' => $email]);
        } else {
            $statement = $this->pdo->prepare(
                'SELECT 1 FROM paciente WHERE correo = :correo AND id <> :id LIMIT 1'
            );
            $statement->execute(['correo' => $email, 'id' => $ignoreId]);
        }

        return $statement->fetchColumn() !== false;
    }

    public function documentTaken(int $tipoDocumentoId, string $numero, ?int $ignoreId): bool
    {
        if ($ignoreId === null) {
            $statement = $this->pdo->prepare(
                'SELECT 1 FROM paciente WHERE tipo_documento_id = :tipo AND numero_documento = :numero LIMIT 1'
            );
            $statement->execute(['tipo' => $tipoDocumentoId, 'numero' => $numero]);
        } else {
            $statement = $this->pdo->prepare(
                'SELECT 1 FROM paciente
                 WHERE tipo_documento_id = :tipo AND numero_documento = :numero AND id <> :id
                 LIMIT 1'
            );
            $statement->execute(['tipo' => $tipoDocumentoId, 'numero' => $numero, 'id' => $ignoreId]);
        }

        return $statement->fetchColumn() !== false;
    }

    private function joinedSelect(): string
    {
        return 'SELECT
                    p.id, p.tipo_documento_id, td.nombre AS tipo_documento,
                    p.numero_documento, p.nombre1, p.nombre2, p.apellido1, p.apellido2,
                    p.genero_id, g.nombre AS genero,
                    p.departamento_id, d.nombre AS departamento,
                    p.municipio_id, m.nombre AS municipio,
                    p.correo, p.foto, p.created_at, p.updated_at
                FROM paciente p
                INNER JOIN tipos_documento td ON td.id = p.tipo_documento_id
                INNER JOIN genero g ON g.id = p.genero_id
                INNER JOIN departamentos d ON d.id = p.departamento_id
                INNER JOIN municipios m ON m.id = p.municipio_id';
    }

    /**
     * Cada palabra debe aparecer en el nombre, el documento o el correo.
     * Así "Ana García" encuentra a Ana María García aunque haya un segundo nombre.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function filter(string $term): array
    {
        $tokens = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY);
        if ($tokens === false || $tokens === []) {
            return ['', []];
        }

        $groups = [];
        $params = [];
        foreach (array_slice($tokens, 0, 8) as $index => $token) {
            $like = $this->like($token);
            $keys = [];
            for ($column = 1; $column <= 7; $column++) {
                $key = 't' . $index . 'c' . $column;
                $keys[] = $key;
                $params[$key] = $like;
            }

            $groups[] = "(
                p.nombre1 LIKE :{$keys[0]} ESCAPE '\\\\'
                OR COALESCE(p.nombre2, '') LIKE :{$keys[1]} ESCAPE '\\\\'
                OR p.apellido1 LIKE :{$keys[2]} ESCAPE '\\\\'
                OR COALESCE(p.apellido2, '') LIKE :{$keys[3]} ESCAPE '\\\\'
                OR p.correo LIKE :{$keys[4]} ESCAPE '\\\\'
                OR p.numero_documento LIKE :{$keys[5]} ESCAPE '\\\\'
                OR CONCAT_WS(' ', p.nombre1, p.nombre2, p.apellido1, p.apellido2) LIKE :{$keys[6]} ESCAPE '\\\\'
            )";
        }

        return ['WHERE ' . implode(' AND ', $groups), $params];
    }

    private function like(string $term): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return '%' . $escaped . '%';
    }
}
