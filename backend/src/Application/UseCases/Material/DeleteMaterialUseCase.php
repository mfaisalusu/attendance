<?php

declare(strict_types=1);

namespace App\Application\UseCases\Material;

use App\Domain\Repositories\CourseMaterialRepositoryInterface;

class DeleteMaterialUseCase
{
    public function __construct(
        private readonly CourseMaterialRepositoryInterface $materialRepository,
    ) {}

    public function execute(int $materialId, int $userId): void
    {
        $material = $this->materialRepository->findById($materialId, $userId);
        if (!$material) {
            throw new \RuntimeException('Materi tidak ditemukan.');
        }

        $this->materialRepository->delete($materialId, $userId);
    }
}