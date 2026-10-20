<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Repository\CatalogRepository;

final class CatalogController
{
    public function __construct(private readonly CatalogRepository $catalog)
    {
    }

    /**
     * @param array<string, string> $params
     */
    public function departamentos(Request $request, array $params = []): JsonResponse
    {
        return new JsonResponse(200, ['data' => $this->catalog->departamentos()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function tiposDocumento(Request $request, array $params = []): JsonResponse
    {
        return new JsonResponse(200, ['data' => $this->catalog->tiposDocumento()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function generos(Request $request, array $params = []): JsonResponse
    {
        return new JsonResponse(200, ['data' => $this->catalog->generos()]);
    }

    /**
     * @param array<string, string> $params
     */
    public function municipios(Request $request, array $params = []): JsonResponse
    {
        $raw = $request->query['departamento_id'] ?? null;
        if (!is_scalar($raw) || $raw === '') {
            return new JsonResponse(422, ['message' => 'Seleccione un departamento.']);
        }

        $departamentoId = filter_var($raw, FILTER_VALIDATE_INT);
        if ($departamentoId === false || $departamentoId < 1 || !$this->catalog->exists('departamentos', (int) $departamentoId)) {
            return new JsonResponse(422, ['message' => 'El departamento no es válido.']);
        }

        return new JsonResponse(200, [
            'data' => $this->catalog->municipios((int) $departamentoId),
        ]);
    }
}
