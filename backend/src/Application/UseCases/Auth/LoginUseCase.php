<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Application\DTO\LoginDTO;
use App\Application\Services\OtpService;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\OtpTokenRepositoryInterface;
use App\Infrastructure\Security\JwtService;
use RuntimeException;

class LoginUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface     $userRepository,
        private readonly OtpTokenRepositoryInterface $otpRepository,
        private readonly OtpService                  $otpService,
    ) {}

    /**
     * Validate credentials.
     *
     * - development mode : skip OTP, langsung return JWT token.
     * - production mode  : kirim OTP ke email, return null (frontend redirect ke /verify-2fa).
     */
    public function execute(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        // Timing-safe: always run password_verify even if user not found
        $hash = $user?->password ?? '$2y$10$invalidhashpaddingtostoptimingattacks00000000000000000';

        if (!password_verify($dto->password, $hash) || $user === null) {
            throw new RuntimeException('Email atau password tidak valid.');
        }

        $isDev = getenv('APP_ENV') === 'development';

        if ($isDev) {
            // Development: bypass OTP, langsung generate JWT
            $token = JwtService::generate($user->id);

            return [
                'mode'          => 'dev_bypass',
                'authenticated' => true,
                'token'         => $token,
                'user'          => $user->toPublicArray(),
            ];
        }

        // Production: generate OTP dan kirim ke email
        $this->otpRepository->deleteByUserId($user->id);
        $this->otpService->generateAndSend($user->id, $user->email, $user->name);

        return [
            'mode'        => 'otp',
            'requires_2fa' => true,
        ];
    }
}
