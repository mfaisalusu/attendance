<?php

declare(strict_types=1);

namespace App\Domain\Entities;

class Attendance
{
    public const STATUSES = ['hadir', 'izin', 'sakit', 'alpha'];

    public function __construct(
        public readonly int    $id,
        public readonly int    $studentId,
        public readonly int    $courseId,
        public readonly string $attendanceDate,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:             (int) $row['id'],
            studentId:      (int) $row['student_id'],
            courseId:       (int) $row['course_id'],
            attendanceDate: $row['attendance_date'],
            status:         $row['status'],
            createdAt:      $row['created_at'],
            updatedAt:      $row['updated_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'student_id'      => $this->studentId,
            'course_id'       => $this->courseId,
            'attendance_date' => $this->attendanceDate,
            'status'          => $this->status,
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
        ];
    }
}
