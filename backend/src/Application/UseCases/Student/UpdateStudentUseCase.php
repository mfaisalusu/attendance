<?php

declare(strict_types=1);

namespace App\Application\UseCases\Student;

use App\Application\DTO\StudentDTO;
use App\Domain\Repositories\MasterRepositoryInterface;
use App\Domain\Repositories\StudentRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

class UpdateStudentUseCase
{
    public function __construct(
        private readonly StudentRepositoryInterface $studentRepository,
        private readonly MasterRepositoryInterface  $masterRepository,
    ) {}

    public function execute(int $id, int $userId, StudentDTO $dto): array
    {
        if ($this->studentRepository->findById($id, $userId) === null) {
            throw new RuntimeException('Mahasiswa tidak ditemukan.');
        }
        if ($this->masterRepository->findClassById($dto->classId, $userId) === null) {
            throw new InvalidArgumentException('Kelas tidak ditemukan atau bukan milik Anda.');
        }
        if ($this->studentRepository->nipExistsForUser($dto->nip, $userId, $id)) {
            throw new InvalidArgumentException('NIP sudah digunakan oleh mahasiswa lain.');
        }

        $updated = $this->studentRepository->update($id, $userId, $dto->nip, $dto->name, $dto->classId);
        if ($updated === null) throw new RuntimeException('Gagal memperbarui data mahasiswa.');
        return $updated->toArray();
    }
}
