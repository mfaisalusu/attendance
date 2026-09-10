<?php

declare(strict_types=1);

namespace App\Application\DTO;

class AttendanceDTO
{
    /**
     * @param int                                                  $courseId  Mata kuliah sesi absensi ini
     * @param array<array{student_id: int, status: string}>        $attendance
     */
    public function __construct(
        public readonly string $date,
        public readonly int    $courseId,
        public readonly array  $attendance,
    ) {}
}
