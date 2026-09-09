<?php

declare(strict_types=1);

namespace App\Application\UseCases\Student;

use App\Domain\Repositories\StudentRepositoryInterface;
use RuntimeException;

class DeleteStudentUseCase
{
    public function __construct(
        private readonly StudentRepositoryInterface $studentRepository,
    ) {}

    public function execute(int $id, int $userId): void
    {
        $deleted = $this->studentRepository->delete($id, $userId);

        if (!$deleted) {
            throw new RuntimeException('Mahasiswa tidak ditemukan atau tidak dapat dihapus.');
        }
    }
}
