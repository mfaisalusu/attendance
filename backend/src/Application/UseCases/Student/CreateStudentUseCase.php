<?php

declare(strict_types=1);

namespace App\Application\UseCases\Student;

use App\Application\DTO\StudentDTO;
use App\Domain\Repositories\StudentRepositoryInterface;
use InvalidArgumentException;

class CreateStudentUseCase
{
    public function __construct(
        private readonly StudentRepositoryInterface $studentRepository,
    ) {}

    public function execute(int $userId, StudentDTO $dto): array
    {
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
