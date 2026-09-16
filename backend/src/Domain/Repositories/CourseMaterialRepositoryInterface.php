<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\CourseMaterial;

interface CourseMaterialRepositoryInterface
{
    public function paginate(int $userId, ?int $courseId, int $page, int $limit): array;
    public function findById(int $id, int $userId): ?CourseMaterial;
    public function create(int $userId, int $courseId, string $meetingName, string $filePath, string $fileName, int $fileSize, ?string $mimeType): CourseMaterial;
    public function delete(int $id, int $userId): bool;
}