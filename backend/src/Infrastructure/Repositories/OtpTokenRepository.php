<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\OtpToken;
use App\Domain\Repositories\OtpTokenRepositoryInterface;

class OtpTokenRepository extends BaseRepository implements OtpTokenRepositoryInterface
{
    public function create(int $userId, string $tokenHash, string $expiresAt): OtpToken
    {
        $stmt = $this->db->prepare(
            'INSERT INTO otp_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $tokenHash, $expiresAt]);

        $id   = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare('SELECT * FROM otp_tokens WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return OtpToken::fromArray($stmt->fetch());
    }

    public function findLatestByUserId(int $userId): ?OtpToken
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM otp_tokens WHERE user_id = ? ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return $row ? OtpToken::fromArray($row) : null;
    }

    public function incrementAttempts(int $tokenId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE otp_tokens SET attempts = attempts + 1 WHERE id = ?'
        );
        $stmt->execute([$tokenId]);
    }

    public function markUsed(int $tokenId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE otp_tokens SET used_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$tokenId]);
    }

    public function deleteByUserId(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM otp_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}
