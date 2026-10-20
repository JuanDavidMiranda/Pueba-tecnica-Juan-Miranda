<?php

declare(strict_types=1);

namespace App\Http;

use PDOException;
use Throwable;

final class ErrorHandler
{
    public static function from(Throwable $exception): JsonResponse
    {
        if ($exception instanceof PDOException) {
            error_log('[sihos] PDO: ' . $exception->getMessage());

            return new JsonResponse(500, ['message' => 'No se pudo conectar con la base de datos.']);
        }

        error_log('[sihos] ' . $exception::class . ': ' . $exception->getMessage());

        return new JsonResponse(500, ['message' => 'Error interno del servidor.']);
    }
}
