<?php

declare(strict_types=1);

namespace App\Application\DTO;

class AttendanceDTO
{
    /**
     * @param array<array{student_id: int, status: string}> $attendance
     */
    public function __construct(
        public readonly string $date,
        public readonly array  $attendance,
    ) {}
}
