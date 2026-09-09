<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Attendance;

interface AttendanceRepositoryInterface
{
    /**
     * List attendance records for a date, filtered by student ownership + optional filters.
     * @return Attendance[]
     */
    public function listByDate(
        int     $userId,
        string  $date,
        ?int    $departmentId = null,
        ?int    $courseId     = null,
        ?int    $classId      = null,
        ?int    $semesterId   = null,
    ): array;

    /**
     * Find a single attendance record by ID, ensuring it belongs to a student owned by userId.
     */
    public function findById(int $id, int $userId): ?Attendance;

    /**
     * Bulk upsert (INSERT or UPDATE) attendance records within a transaction.
     * Each item: ['student_id' => int, 'status' => string]
     *
     * @param array<array{student_id: int, status: string}> $items
     */
    public function bulkUpsert(int $userId, string $date, array $items): void;

    /**
     * Update a single attendance record.
     */
    public function update(int $id, int $userId, string $status): ?Attendance;

    /**
     * Monthly recap aggregation — returns one row per student.
     * @return array<array{student_id,nip,name,hadir,izin,sakit,alpha,total_pertemuan,persentase}>
     */
    public function monthlyRecap(
        int  $userId,
        int  $year,
        int  $month,
        ?int $departmentId = null,
        ?int $courseId     = null,
        ?int $classId      = null,
        ?int $semesterId   = null,
    ): array;

    /**
     * Summary stats for a given date (used by dashboard and attendance page).
     */
    public function summaryByDate(int $userId, string $date): array;
}
