<?php

declare(strict_types=1);

namespace App\Application\UseCases\Student;

use App\Domain\Repositories\MasterRepositoryInterface;
use App\Domain\Repositories\StudentRepositoryInterface;
use RuntimeException;

class GetStudentUseCase
{
    public function __construct(
        private readonly StudentRepositoryInterface $studentRepository,
        private readonly MasterRepositoryInterface  $masterRepository,
    ) {}

    public function execute(int $id, int $userId): array
    {
        $student = $this->studentRepository->findById($id, $userId);

        if ($student === null) {
            throw new RuntimeException('Mahasiswa tidak ditemukan.');
        }

        $data = $student->toArray();

        $data['department'] = $this->masterRepository->findDepartmentById($student->departmentId, $userId)?->toArray();
        $data['course']     = $this->masterRepository->findCourseById($student->courseId, $userId)?->toArray();
        $data['class']      = $this->masterRepository->findClassById($student->classId, $userId)?->toArray();
        $data['semester']   = $this->masterRepository->findSemesterById($student->semesterId)?->toArray();

        return $data;
    }
}
