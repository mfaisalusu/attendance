<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Student
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $userId,
        public readonly string $nip,
        public readonly string $name,
        public readonly int    $departmentId,
        public readonly int    $courseId,
        public readonly int    $classId,
        public readonly int    $semesterId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:           (int) $row['id'],
            userId:       (int) $row['user_id'],
            nip:          $row['nip'],
            name:         $row['name'],
            departmentId: (int) $row['department_id'],
            courseId:     (int) $row['course_id'],
            classId:      (int) $row['class_id'],
            semesterId:   (int) $row['semester_id'],
            createdAt:    $row['created_at'],
            updatedAt:    $row['updated_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->userId,
            'nip'           => $this->nip,
            'name'          => $this->name,
            'department_id' => $this->departmentId,
            'course_id'     => $this->courseId,
            'class_id'      => $this->classId,
            'semester_id'   => $this->semesterId,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
        ];
    }
}
