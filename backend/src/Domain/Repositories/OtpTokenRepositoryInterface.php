<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\OtpToken;

interface OtpTokenRepositoryInterface
{
    public function create(int $userId, string $tokenHash, string $expiresAt): OtpToken;

    public function findLatestByUserId(int $userId): ?OtpToken;

    public function incrementAttempts(int $tokenId): void;

    public function markUsed(int $tokenId): void;

    public function deleteByUserId(int $userId): void;
}
