<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class OtpToken
{
    public function __construct(
        public readonly int     $id,
        public readonly int     $userId,
        public readonly string  $tokenHash,
        public readonly string  $expiresAt,
        public readonly int     $attempts,
        public readonly ?string $usedAt,
        public readonly string  $createdAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:        (int) $row['id'],
            userId:    (int) $row['user_id'],
            tokenHash: $row['token_hash'],
            expiresAt: $row['expires_at'],
            attempts:  (int) $row['attempts'],
            usedAt:    $row['used_at'] ?? null,
            createdAt: $row['created_at'],
        );
    }

    public function isExpired(): bool
    {
        return strtotime($this->expiresAt) < time();
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function hasExceededAttempts(int $max = 5): bool
    {
        return $this->attempts >= $max;
    }
}
