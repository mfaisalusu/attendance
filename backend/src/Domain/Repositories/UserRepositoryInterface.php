<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function emailExists(string $email): bool;

    public function create(string $name, string $email, string $passwordHash): User;

    public function markEmailVerified(int $userId): void;
}
