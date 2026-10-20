<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Presenter\PacientePresenter;
use App\Repository\CatalogRepository;
use App\Repository\PacienteRepository;
use App\Security\ImageUploader;
use App\Validation\PacienteValidator;
use PDOException;
use RuntimeException;

final class PacienteController
{
    public function __construct(
        private readonly PacienteRepository $pacientes,
        private readonly CatalogRepository $catalog,
        private readonly PacienteValidator $validator,
        private readonly ImageUploader $uploader,
        private readonly PacientePresenter $presenter,
    ) {
    }

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): JsonResponse
    {
        $page = max(1, (int) $this->scalar($request->query['page'] ?? 1));
        $perPage = (int) $this->scalar($request->query['per_page'] ?? 8);
        if ($perPage < 1) {
            $perPage = 8;
        }
        if ($perPage > 50) {
            $perPage = 50;
        }

        $term = trim($this->scalar($request->query['q'] ?? ''));
        if (mb_strlen($term) > 100) {
            $term = mb_substr($term, 0, 100);
        }

        $result = $this->pacientes->paginate($term, $page, $perPage);

        return new JsonResponse(200, [
            'data' => array_map($this->presenter->present(...), $result['rows']),
            'meta' => [
                'page' => $result['page'],
                'per_page' => $perPage,
                'total' => $result['total'],
                'last_page' => max(1, (int) ceil($result['total'] / $perPage)),
            ],
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): JsonResponse
    {
        $id = $this->id($params);
        $row = $id === null ? null : $this->pacientes->find($id);
        if ($row === null) {
            return new JsonResponse(404, ['message' => 'No se encontró el paciente.']);
        }

        return new JsonResponse(200, ['data' => $this->presenter->present($row)]);
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): JsonResponse
    {
        $data = $this->validated($request->body, null);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $foto = $this->uploadedFoto($request);
        if ($foto instanceof JsonResponse) {
            return $foto;
        }
        $data['foto'] = $foto;

        try {
            $id = $this->pacientes->create($data);
        } catch (PDOException $exception) {
            $this->uploader->delete($foto);
            if ($exception->getCode() === '23000') {
                return new JsonResponse(422, ['message' => 'El documento o el correo ya están registrados.']);
            }
            throw $exception;
        }

        return new JsonResponse(201, [
            'message' => 'Paciente creado correctamente.',
            'data' => $this->presenter->present($this->required($id)),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): JsonResponse
    {
        $id = $this->id($params);
        $current = $id === null ? null : $this->pacientes->find($id);
        if ($id === null || $current === null) {
            return new JsonResponse(404, ['message' => 'No se encontró el paciente.']);
        }

        $data = $this->validated($request->body, $id);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $foto = $this->uploadedFoto($request);
        if ($foto instanceof JsonResponse) {
            return $foto;
        }
        if ($foto !== null) {
            $data['foto'] = $foto;
        }

        try {
            $this->pacientes->update($id, $data);
        } catch (PDOException $exception) {
            $this->uploader->delete($foto);
            if ($exception->getCode() === '23000') {
                return new JsonResponse(422, ['message' => 'El documento o el correo ya están registrados.']);
            }
            throw $exception;
        }

        if ($foto !== null && is_string($current['foto'] ?? null)) {
            $this->uploader->delete($current['foto']);
        }

        return new JsonResponse(200, [
            'message' => 'Paciente actualizado correctamente.',
            'data' => $this->presenter->present($this->required($id)),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function destroy(Request $request, array $params = []): JsonResponse
    {
        $id = $this->id($params);
        $current = $id === null ? null : $this->pacientes->find($id);
        if ($id === null || $current === null) {
            return new JsonResponse(404, ['message' => 'No se encontró el paciente.']);
        }

        $this->pacientes->delete($id);
        if (is_string($current['foto'] ?? null)) {
            $this->uploader->delete($current['foto']);
        }

        return new JsonResponse(200, ['message' => 'Paciente eliminado correctamente.']);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>|JsonResponse
     */
    private function validated(array $input, ?int $ignoreId): array|JsonResponse
    {
        $result = $this->validator->validate($input);
        $errors = $result['errors'];
        $data = $result['data'];

        if (!isset($errors['tipo_documento_id']) && !$this->catalog->exists('tipos_documento', (int) $data['tipo_documento_id'])) {
            $errors['tipo_documento_id'][] = 'El tipo de documento no es válido.';
        }
        if (!isset($errors['genero_id']) && !$this->catalog->exists('genero', (int) $data['genero_id'])) {
            $errors['genero_id'][] = 'El género no es válido.';
        }
        if (!isset($errors['departamento_id']) && !$this->catalog->exists('departamentos', (int) $data['departamento_id'])) {
            $errors['departamento_id'][] = 'El departamento no es válido.';
        }
        if (!isset($errors['municipio_id']) && !isset($errors['departamento_id'])) {
            $municipio = $this->catalog->findMunicipio((int) $data['municipio_id']);
            if ($municipio === null || $municipio['departamento_id'] !== (int) $data['departamento_id']) {
                $errors['municipio_id'][] = 'El municipio no pertenece al departamento seleccionado.';
            }
        }
        if (!isset($errors['correo']) && $this->pacientes->emailTaken((string) $data['correo'], $ignoreId)) {
            $errors['correo'][] = 'Este correo ya está registrado.';
        }
        if (
            !isset($errors['numero_documento'])
            && !isset($errors['tipo_documento_id'])
            && $this->pacientes->documentTaken((int) $data['tipo_documento_id'], (string) $data['numero_documento'], $ignoreId)
        ) {
            $errors['numero_documento'][] = 'Este número de documento ya está registrado.';
        }

        if ($errors !== []) {
            return new JsonResponse(422, [
                'message' => 'Los datos no son válidos.',
                'errors' => $errors,
            ]);
        }

        return $data;
    }

    private function uploadedFoto(Request $request): string|JsonResponse|null
    {
        try {
            return $this->uploader->store($request->file('foto'));
        } catch (RuntimeException $exception) {
            return new JsonResponse(422, [
                'message' => $exception->getMessage(),
                'errors' => ['foto' => [$exception->getMessage()]],
            ]);
        }
    }

    /**
     * @param array<string, string> $params
     */
    private function id(array $params): ?int
    {
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return null;
        }

        return (int) $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function required(int $id): array
    {
        $row = $this->pacientes->find($id);
        if ($row === null) {
            throw new RuntimeException('No se pudo leer el paciente guardado.');
        }

        return $row;
    }

    private function scalar(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
