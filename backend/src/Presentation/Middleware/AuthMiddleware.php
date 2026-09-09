<?php

declare(strict_types=1);

namespace App\Presentation\Middleware;

use App\Infrastructure\Security\JwtService;
use App\Presentation\Requests\Request;
use App\Presentation\Responses\JsonResponse;
use Exception;

class AuthMiddleware
{
    /**
     * Returns a middleware callable that validates the Bearer JWT token.
     * The resolved user ID is stored in $_REQUEST['_auth_user_id'] so
     * controllers can retrieve it via AuthMiddleware::userId().
     */
    public static function handle(): callable
    {
        return function (Request $request): void {
            $token = $request->bearerToken();

            if ($token === null) {
                JsonResponse::unauthorized('Token autentikasi diperlukan.');
            }

            try {
                $userId = JwtService::getUserId($token);
                $_REQUEST['_auth_user_id'] = $userId;
            } catch (Exception) {
                JsonResponse::unauthorized('Token tidak valid atau sudah kedaluwarsa.');
            }
        };
    }

    /**
     * Retrieve the authenticated user's ID from the request context.
     * Call this inside controllers that are protected by this middleware.
     */
    public static function userId(): int
    {
        return (int) ($_REQUEST['_auth_user_id'] ?? 0);
    }
}
