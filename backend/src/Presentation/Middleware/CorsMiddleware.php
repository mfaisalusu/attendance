<?php

declare(strict_types=1);

namespace App\Presentation\Middleware;

class CorsMiddleware
{
    public static function handle(): void
    {
        $allowedOrigins = getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173';
        $origins        = array_map('trim', explode(',', $allowedOrigins));

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $origins, true) || in_array('*', $origins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');

        // Respond immediately to preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
