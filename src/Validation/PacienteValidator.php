<?php

declare(strict_types=1);

namespace App\Validation;

final class PacienteValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{errors: array<string, list<string>>, data: array<string, mixed>}
     */
    public function validate(array $input): array
    {
        $errors = [];
        $data = [];

        $data['tipo_documento_id'] = $this->requiredId($input, 'tipo_documento_id', 'el tipo de documento', $errors);
        $data['genero_id'] = $this->requiredId($input, 'genero_id', 'el género', $errors);
        $data['departamento_id'] = $this->requiredId($input, 'departamento_id', 'el departamento', $errors);
        $data['municipio_id'] = $this->requiredId($input, 'municipio_id', 'el municipio', $errors);

        $documento = strtoupper(trim((string) ($input['numero_documento'] ?? '')));
        if ($documento === '') {
            $errors['numero_documento'][] = 'Ingrese el número de documento.';
        } elseif (preg_match('/^[A-Z0-9]{5,20}$/', $documento) !== 1) {
            $errors['numero_documento'][] = 'El documento debe tener entre 5 y 20 letras o números.';
        }
        $data['numero_documento'] = $documento;

        $data['nombre1'] = $this->personName($input['nombre1'] ?? '', 'nombre1', 'el primer nombre', true, $errors);
        $data['nombre2'] = $this->personName($input['nombre2'] ?? '', 'nombre2', 'el segundo nombre', false, $errors);
        $data['apellido1'] = $this->personName($input['apellido1'] ?? '', 'apellido1', 'el primer apellido', true, $errors);
        $data['apellido2'] = $this->personName($input['apellido2'] ?? '', 'apellido2', 'el segundo apellido', false, $errors);

        $correo = strtolower(trim((string) ($input['correo'] ?? '')));
        if ($correo === '') {
            $errors['correo'][] = 'Ingrese el correo electrónico.';
        } elseif (
            mb_strlen($correo) > 180
            || filter_var($correo, FILTER_VALIDATE_EMAIL) === false
            || preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $correo) !== 1
        ) {
            $errors['correo'][] = 'Ingrese un correo electrónico válido.';
        }
        $data['correo'] = $correo;

        return ['errors' => $errors, 'data' => $data];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, list<string>> $errors
     */
    private function requiredId(array $input, string $field, string $label, array &$errors): ?int
    {
        if (!isset($input[$field]) || $input[$field] === '') {
            $errors[$field][] = 'Seleccione ' . $label . '.';
            return null;
        }

        $value = filter_var($input[$field], FILTER_VALIDATE_INT);
        if ($value === false || $value < 1) {
            $errors[$field][] = 'Seleccione ' . $label . '.';
            return null;
        }

        return (int) $value;
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function personName(mixed $value, string $field, string $label, bool $required, array &$errors): ?string
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
        if ($name === '') {
            if ($required) {
                $errors[$field][] = 'Ingrese ' . $label . '.';
            }
            return null;
        }

        $length = mb_strlen($name);
        if ($length < 2 || $length > 60 || preg_match("/^[\\p{L}][\\p{L}\\s'\\-]{1,59}$/u", $name) !== 1) {
            $errors[$field][] = 'Use solo letras, espacios o guiones (entre 2 y 60 caracteres) en ' . $label . '.';
            return null;
        }

        return $name;
    }
}
