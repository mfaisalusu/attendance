<?php

declare(strict_types=1);

namespace App\Application\UseCases\Material;

use App\Domain\Entities\CourseMaterial;
use App\Domain\Repositories\CourseMaterialRepositoryInterface;
use App\Infrastructure\Repositories\DatabaseMasterRepository;

class CreateMaterialUseCase
{
    public function __construct(
        private readonly CourseMaterialRepositoryInterface $materialRepository,
        private readonly DatabaseMasterRepository $masterRepository,
    ) {}

    public function execute(
        int $userId,
        int $courseId,
        string $meetingName,
        string $filePath,
        string $fileName,
        int $fileSize,
        ?string $mimeType
    ): CourseMaterial {
        // Verify course belongs to user
        $course = $this->masterRepository->findCourseById($courseId, $userId);
        if (!$course) {
            throw new \InvalidArgumentException('Mata kuliah tidak ditemukan.');
        }

        return $this->materialRepository->create(
            $userId,
            $courseId,
            $meetingName,
            $filePath,
            $fileName,
            $fileSize,
            $mimeType
        );
    }
}