<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;
use RuntimeException;

class SmtpMailer implements MailerInterface
{
    public function sendOtp(
        string $toEmail,
        string $toName,
        string $otpCode,
        int    $ttlMinutes
    ): void {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST')     ?: 'smtp.mailtrap.io';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('MAIL_USERNAME') ?: '';
            $mail->Password   = getenv('MAIL_PASSWORD') ?: '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) (getenv('MAIL_PORT') ?: 2525);
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;

            $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@attendance.dev';
            $fromName    = getenv('MAIL_FROM_NAME')    ?: 'Attendance App';

            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = 'Kode Verifikasi Login';
            $mail->Body    = $this->buildHtmlBody($toName, $otpCode, $ttlMinutes);
            $mail->AltBody = $this->buildPlainBody($otpCode, $ttlMinutes);

            $mail->send();
        } catch (MailException $e) {
            // Do not expose SMTP details to caller
            throw new RuntimeException('Gagal mengirim email verifikasi. Coba lagi nanti.');
        }
    }

    private function buildHtmlBody(string $name, string $otp, int $ttl): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head><meta charset="UTF-8"></head>
        <body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px">
          <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:8px;padding:32px">
            <h2 style="color:#2563eb;margin-top:0">Kode Verifikasi Login</h2>
            <p>Halo, <strong>{$name}</strong>!</p>
            <p>Kode verifikasi login Anda adalah:</p>
            <div style="font-size:36px;font-weight:bold;letter-spacing:8px;text-align:center;
                        background:#f0f4ff;padding:16px;border-radius:8px;color:#1e40af;margin:24px 0">
              {$otp}
            </div>
            <p>Kode berlaku selama <strong>{$ttl} menit</strong>.</p>
            <p style="color:#6b7280;font-size:13px">
              Jika bukan Anda yang melakukan login, abaikan email ini.
            </p>
          </div>
        </body>
        </html>
        HTML;
    }

    private function buildPlainBody(string $otp, int $ttl): string
    {
        return "Kode verifikasi login Anda adalah: {$otp}\n\nKode berlaku selama {$ttl} menit.\n\nJika bukan Anda yang melakukan login, abaikan email ini.";
    }
}
