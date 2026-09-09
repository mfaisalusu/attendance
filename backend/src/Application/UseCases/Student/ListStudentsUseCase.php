<?php

declare(strict_types=1);

namespace App\Application\UseCases\Student;

use App\Domain\Repositories\MasterRepositoryInterface;
use App\Domain\Repositories\StudentRepositoryInterface;

class ListStudentsUseCase
{
    public function __construct(
        private readonly StudentRepositoryInterface $studentRepository,
        private readonly MasterRepositoryInterface  $masterRepository,
    ) {}

    public function execute(
        int     $userId,
        int     $page         = 1,
        int     $limit        = 20,
        ?string $search       = null,
        ?int    $departmentId = null,
        ?int    $courseId     = null,
        ?int    $classId      = null,
        ?int    $semesterId   = null,
    ): array {
        $result = $this->studentRepository->paginate(
            $userId, $page, $limit, $search,
            $departmentId, $courseId, $classId, $semesterId
        );

        // Enrich each student with master labels
        $items = array_map(function (object $student) use ($userId) {
            $data = $student->toArray();

            $data['department'] = $this->masterRepository->findDepartmentById($student->departmentId, $userId)?->toArray();
            $data['course']     = $this->masterRepository->findCourseById($student->courseId, $userId)?->toArray();
            $data['class']      = $this->masterRepository->findClassById($student->classId, $userId)?->toArray();
            $data['semester']   = $this->masterRepository->findSemesterById($student->semesterId)?->toArray();

            return $data;
        }, $result['items']);

        return [
            'items'      => $items,
            'pagination' => $result['pagination'],
        ];
    }
}
