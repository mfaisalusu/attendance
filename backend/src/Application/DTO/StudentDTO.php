<?php

declare(strict_types=1);

namespace App\Application\DTO;

class StudentDTO
{
    public function __construct(
        public readonly string $nip,
        public readonly string $name,
        public readonly int    $departmentId,
        public readonly int    $courseId,
        public readonly int    $classId,
        public readonly int    $semesterId,
    ) {}
}
