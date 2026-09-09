<?php

declare(strict_types=1);

namespace App\Application\UseCases\Student;

use App\Application\DTO\StudentDTO;
use App\Domain\Repositories\MasterRepositoryInterface;
use App\Domain\Repositories\StudentRepositoryInterface;
use InvalidArgumentException;

class CreateStudentUseCase
{
    public function __construct(
        private readonly StudentRepositoryInterface $studentRepository,
        private readonly MasterRepositoryInterface  $masterRepository,
    ) {}

    public function execute(int $userId, StudentDTO $dto): array
    {
        // Validate ownership of all FK master IDs
        if ($this->masterRepository->findDepartmentById($dto->departmentId, $userId) === null) {
            throw new InvalidArgumentException('Jurusan tidak ditemukan atau bukan milik Anda.');
        }
        if ($this->masterRepository->findCourseById($dto->courseId, $userId) === null) {
            throw new InvalidArgumentException('Mata kuliah tidak ditemukan atau bukan milik Anda.');
        }
        if ($this->masterRepository->findClassById($dto->classId, $userId) === null) {
            throw new InvalidArgumentException('Kelas tidak ditemukan atau bukan milik Anda.');
        }

        if ($this->studentRepository->nipExistsForUser($dto->nip, $userId)) {
            throw new InvalidArgumentException('NIP sudah digunakan oleh mahasiswa lain.');
        }

        $student = $this->studentRepository->create(
            $userId,
            $dto->nip,
            $dto->name,
            $dto->departmentId,
            $dto->courseId,
            $dto->classId,
            $dto->semesterId,
        );

        return $student->toArray();
    }
}
