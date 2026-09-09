<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use stdClass;

class JwtService
{
    private const ALGORITHM = 'HS256';
    private const TTL       = 86400; // 24 hours in seconds

    public static function generate(int $userId): string
    {
        $secret = self::getSecret();

        $payload = [
            'iss' => 'attendance-app',
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + self::TTL,
        ];

        return JWT::encode($payload, $secret, self::ALGORITHM);
    }

    public static function decode(string $token): stdClass
    {
        $secret = self::getSecret();
        return JWT::decode($token, new Key($secret, self::ALGORITHM));
    }

    public static function getUserId(string $token): int
    {
        $payload = self::decode($token);
        return (int) $payload->sub;
    }

    private static function getSecret(): string
    {
        $secret = getenv('JWT_SECRET') ?: '';

        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET must be at least 32 characters long.');
        }

        return $secret;
    }
}
