<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Application\DTO\Verify2faDTO;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\OtpTokenRepositoryInterface;
use App\Infrastructure\Security\JwtService;
use RuntimeException;

class Verify2faUseCase
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly UserRepositoryInterface     $userRepository,
        private readonly OtpTokenRepositoryInterface $otpRepository,
    ) {}

    /**
     * Returns JWT token string on successful verification.
     */
    public function execute(Verify2faDTO $dto): string
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if ($user === null) {
            throw new RuntimeException('Kode verifikasi tidak valid atau sudah kedaluwarsa.');
        }

        $otpToken = $this->otpRepository->findLatestByUserId($user->id);

        if ($otpToken === null) {
            throw new RuntimeException('Kode verifikasi tidak ditemukan.');
        }

        if ($otpToken->isUsed()) {
            throw new RuntimeException('Kode verifikasi sudah digunakan.');
        }

        if ($otpToken->isExpired()) {
            throw new RuntimeException('Kode verifikasi sudah kedaluwarsa.');
        }

        if ($otpToken->hasExceededAttempts(self::MAX_ATTEMPTS)) {
            throw new RuntimeException('Terlalu banyak percobaan. Silakan login ulang untuk mendapatkan kode baru.');
        }

        // Increment attempt count before verifying (prevents brute-force)
        $this->otpRepository->incrementAttempts($otpToken->id);

        if (!password_verify($dto->token, $otpToken->tokenHash)) {
            throw new RuntimeException('Kode verifikasi tidak valid.');
        }

        // Mark OTP as used
        $this->otpRepository->markUsed($otpToken->id);

        // Mark email as verified if not already
        if ($user->emailVerifiedAt === null) {
            $this->userRepository->markEmailVerified($user->id);
        }

        return JwtService::generate($user->id);
    }
}
