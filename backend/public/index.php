<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

use App\Infrastructure\Database\Connection;
use App\Presentation\Middleware\CorsMiddleware;
use App\Infrastructure\Config\EnvLoader;
use App\Presentation\Router\Router;
use App\Presentation\Responses\JsonResponse;

// Load environment variables
EnvLoader::load(BASE_PATH . '/.env');

// Handle CORS before anything else
CorsMiddleware::handle();

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Register centralized error / exception handler
set_exception_handler(function (Throwable $e) {
    $isDebug = (getenv('APP_DEBUG') === 'true');

    $body = [
        'success' => false,
        'message' => $isDebug ? $e->getMessage() : 'Terjadi kesalahan pada server.',
    ];

    if ($isDebug) {
        $body['trace'] = $e->getTraceAsString();
    }

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode($body);
    exit;
});

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Bootstrap router
$router = new Router();
require_once BASE_PATH . '/routes/api.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$router->dispatch($method, $uri);
