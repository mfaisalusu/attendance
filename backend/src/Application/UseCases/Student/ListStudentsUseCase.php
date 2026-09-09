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
        $items = array_map(function (object $student) {
            $data = $student->toArray();

            $dept   = $this->masterRepository->findDepartmentById($student->departmentId);
            $course = $this->masterRepository->findCourseById($student->courseId);
            $class  = $this->masterRepository->findClassById($student->classId);
            $sem    = $this->masterRepository->findSemesterById($student->semesterId);

            $data['department'] = $dept?->toArray();
            $data['course']     = $course?->toArray();
            $data['class']      = $class?->toArray();
            $data['semester']   = $sem?->toArray();

            return $data;
        }, $result['items']);

        return [
            'items'      => $items,
            'pagination' => $result['pagination'],
        ];
    }
}
