<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class User
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $email,
        public readonly string  $password,
        public readonly ?string $emailVerifiedAt,
        public readonly string  $createdAt,
        public readonly string  $updatedAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:              (int) $row['id'],
            name:            $row['name'],
            email:           $row['email'],
            password:        $row['password'],
            emailVerifiedAt: $row['email_verified_at'] ?? null,
            createdAt:       $row['created_at'],
            updatedAt:       $row['updated_at'],
        );
    }

    public function toPublicArray(): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'email_verified_at' => $this->emailVerifiedAt,
            'created_at'        => $this->createdAt,
        ];
    }
}
