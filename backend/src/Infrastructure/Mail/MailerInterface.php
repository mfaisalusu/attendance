<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

interface MailerInterface
{
    public function sendOtp(
        string $toEmail,
        string $toName,
        string $otpCode,
        int    $ttlMinutes
    ): void;
}
