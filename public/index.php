<?php

declare(strict_types=1);

use App\Bootstrap;
use App\Http\ErrorHandler;
use App\Http\Request;

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Falta vendor/. Ejecute composer install en la raíz del proyecto.\n";
    exit;
}

require $autoload;

Bootstrap::init(dirname(__DIR__));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$request = Request::fromGlobals();

if (!str_starts_with($request->path, '/api')) {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'");
    require dirname(__DIR__) . '/views/app.php';
    exit;
}

try {
    Bootstrap::prepareDatabase();
    $response = Bootstrap::router()->dispatch($request);
} catch (Throwable $exception) {
    $response = ErrorHandler::from($exception);
}

$response->send();
