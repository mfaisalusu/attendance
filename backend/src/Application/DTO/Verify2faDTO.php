<?php

declare(strict_types=1);

namespace App\Application\DTO;

class Verify2faDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $token,
    ) {}
}
