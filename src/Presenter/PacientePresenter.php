<?php

declare(strict_types=1);

namespace App\Presenter;

use App\Security\ImageUploader;

final class PacientePresenter
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function present(array $row): array
    {
        $foto = $row['foto'] ?? null;
        $completo = trim(preg_replace(
            '/\s+/u',
            ' ',
            trim(
                (string) $row['nombre1'] . ' '
                . (string) ($row['nombre2'] ?? '') . ' '
                . (string) $row['apellido1'] . ' '
                . (string) ($row['apellido2'] ?? '')
            )
        ) ?? '');

        return [
            'id' => (int) $row['id'],
            'tipo_documento_id' => (int) $row['tipo_documento_id'],
            'tipo_documento' => (string) $row['tipo_documento'],
            'numero_documento' => (string) $row['numero_documento'],
            'nombre1' => (string) $row['nombre1'],
            'nombre2' => $row['nombre2'],
            'apellido1' => (string) $row['apellido1'],
            'apellido2' => $row['apellido2'],
            'nombre_completo' => $completo,
            'genero_id' => (int) $row['genero_id'],
            'genero' => (string) $row['genero'],
            'departamento_id' => (int) $row['departamento_id'],
            'departamento' => (string) $row['departamento'],
            'municipio_id' => (int) $row['municipio_id'],
            'municipio' => (string) $row['municipio'],
            'correo' => (string) $row['correo'],
            'foto_url' => ImageUploader::publicUrl(is_string($foto) ? $foto : null),
        ];
    }
}
