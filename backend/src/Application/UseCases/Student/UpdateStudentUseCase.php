<?php

declare(strict_types=1);

namespace App\Application\UseCases\Student;

use App\Application\DTO\StudentDTO;
use App\Domain\Repositories\StudentRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

class UpdateStudentUseCase
{
    public function __construct(
        private readonly StudentRepositoryInterface $studentRepository,
    ) {}

    public function execute(int $id, int $userId, StudentDTO $dto): array
    {
        // Ensure student belongs to this user
        $existing = $this->studentRepository->findById($id, $userId);

        if ($existing === null) {
            throw new RuntimeException('Mahasiswa tidak ditemukan.');
        }

        // Check NIP uniqueness, excluding the current record
        if ($this->studentRepository->nipExistsForUser($dto->nip, $userId, $id)) {
            throw new InvalidArgumentException('NIP sudah digunakan oleh mahasiswa lain.');
        }

        $updated = $this->studentRepository->update(
            $id,
            $userId,
            $dto->nip,
            $dto->name,
            $dto->departmentId,
            $dto->courseId,
            $dto->classId,
            $dto->semesterId,
        );

        if ($updated === null) {
            throw new RuntimeException('Gagal memperbarui data mahasiswa.');
        }

        return $updated->toArray();
    }
}
