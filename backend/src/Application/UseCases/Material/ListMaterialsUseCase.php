<?php

declare(strict_types=1);

namespace App\Application\UseCases\Material;

use App\Domain\Repositories\CourseMaterialRepositoryInterface;

class ListMaterialsUseCase
{
    public function __construct(
        private readonly CourseMaterialRepositoryInterface $materialRepository,
    ) {}

    public function execute(int $userId, ?int $courseId, int $page = 1, int $limit = 20): array
    {
        return $this->materialRepository->paginate($userId, $courseId, $page, $limit);
    }
}