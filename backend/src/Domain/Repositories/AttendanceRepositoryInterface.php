<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Attendance;

interface AttendanceRepositoryInterface
{
    /** @return Attendance[] */
    public function listByDate(int $userId, string $date, ?int $classId = null, ?int $courseId = null): array;

    public function findById(int $id, int $userId): ?Attendance;

    /** @param array<array{student_id: int, status: string}> $items */
    public function bulkUpsert(int $userId, string $date, int $courseId, array $items): void;

    public function update(int $id, int $userId, string $status): ?Attendance;

    /**
     * Monthly matrix — one row per student, days keyed 1–N with H/I/S/A or null.
     * @return array<array{student_id:int,nip:string,name:string,days:array<int,string|null>}>
     */
    public function monthlyMatrix(int $userId, int $year, int $month, ?int $classId = null, ?int $courseId = null): array;

    public function summaryByDate(int $userId, string $date): array;
}
