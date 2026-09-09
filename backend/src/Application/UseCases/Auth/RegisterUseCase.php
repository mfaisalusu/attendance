<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Application\DTO\RegisterDTO;
use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use InvalidArgumentException;

class RegisterUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function execute(RegisterDTO $dto): User
    {
        if ($this->userRepository->emailExists($dto->email)) {
            throw new InvalidArgumentException('Email sudah digunakan.');
        }

        if ($dto->password !== $dto->passwordConfirmation) {
            throw new InvalidArgumentException('Password dan konfirmasi password tidak sama.');
        }

        $passwordHash = password_hash($dto->password, PASSWORD_BCRYPT);

        return $this->userRepository->create($dto->name, $dto->email, $passwordHash);
    }
}
