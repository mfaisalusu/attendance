<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Presentation\Middleware\AuthMiddleware;

abstract class BaseController
{
    protected function authUserId(): int
    {
        return AuthMiddleware::userId();
    }
}
