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
        int     $page    = 1,
        int     $limit   = 20,
        ?string $search  = null,
        ?int    $classId = null,
    ): array {
        $result = $this->studentRepository->paginate($userId, $page, $limit, $search, $classId);

        $items = array_map(function (object $student) use ($userId) {
            $data          = $student->toArray();
            $data['class'] = $this->masterRepository->findClassById($student->classId, $userId)?->toArray();
            return $data;
        }, $result['items']);

        return ['items' => $items, 'pagination' => $result['pagination']];
    }
}
