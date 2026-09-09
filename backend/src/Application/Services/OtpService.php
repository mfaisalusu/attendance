<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\OtpTokenRepositoryInterface;
use App\Infrastructure\Mail\MailerInterface;

class OtpService
{
    private const OTP_TTL_MINUTES = 5;

    public function __construct(
        private readonly OtpTokenRepositoryInterface $otpRepository,
        private readonly MailerInterface             $mailer,
    ) {}

    public function generateAndSend(int $userId, string $email, string $name): string
    {
        // Generate cryptographically random 6-digit OTP
        $plainOtp  = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $tokenHash = password_hash($plainOtp, PASSWORD_BCRYPT);
        $expiresAt = date('Y-m-d H:i:s', time() + (self::OTP_TTL_MINUTES * 60));

        $this->otpRepository->create($userId, $tokenHash, $expiresAt);

        // throws RuntimeException if SMTP fails — propagated to caller
        $this->mailer->sendOtp($email, $name, $plainOtp, self::OTP_TTL_MINUTES);

        return $plainOtp;
    }
}
