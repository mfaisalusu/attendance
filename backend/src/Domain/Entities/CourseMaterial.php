<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class CourseMaterial
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $courseId,
        public readonly string $meetingName,
        public readonly string $filePath,
        public readonly string $fileName,
        public readonly int $fileSize,
        public readonly ?string $mimeType,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:         (int) $row['id'],
            userId:     (int) $row['user_id'],
            courseId:   (int) $row['course_id'],
            meetingName: $row['meeting_name'],
            filePath:   $row['file_path'],
            fileName:   $row['file_name'],
            fileSize:   (int) $row['file_size'],
            mimeType:   $row['mime_type'] ?? null,
            createdAt:  $row['created_at'],
            updatedAt:  $row['updated_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->userId,
            'course_id'   => $this->courseId,
            'meeting_name'=> $this->meetingName,
            'file_path'   => $this->filePath,
            'file_name'   => $this->fileName,
            'file_size'   => $this->fileSize,
            'mime_type'   => $this->mimeType,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }
}