<?php

declare(strict_types=1);

namespace App\Application\UseCases\Attendance;

use App\Domain\Repositories\AttendanceRepositoryInterface;
use App\Domain\Repositories\StudentRepositoryInterface;

class GetAttendanceUseCase
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository,
        private readonly StudentRepositoryInterface    $studentRepository,
    ) {}

    /**
     * Returns a list of students for the given filters, each enriched with
     * their attendance status for the requested date (null if not yet recorded).
     */
    public function execute(
        int     $userId,
        string  $date,
        ?int    $departmentId = null,
        ?int    $courseId     = null,
        ?int    $classId      = null,
        ?int    $semesterId   = null,
    ): array {
        // Get students matching filters (all, no pagination — for attendance form)
        $students = $this->studentRepository->listForAttendance(
            $userId, $departmentId, $courseId, $classId, $semesterId
        );

        // Build a map of studentId → attendance record
        $records     = $this->attendanceRepository->listByDate(
            $userId, $date, $departmentId, $courseId, $classId, $semesterId
        );
        $recordMap = [];
        foreach ($records as $record) {
            $recordMap[$record->studentId] = $record;
        }

        $items        = [];
        $absenCount   = 0;

        foreach ($students as $student) {
            $record = $recordMap[$student->id] ?? null;

            $item = $student->toArray();
            $item['attendance'] = $record ? [
                'id'     => $record->id,
                'status' => $record->status,
            ] : null;

            if ($record !== null) {
                $absenCount++;
            }

            $items[] = $item;
        }

        $total = count($students);

        return [
            'date'           => $date,
            'students'       => $items,
            'summary'        => [
                'total'          => $total,
                'sudah_diabsen'  => $absenCount,
                'belum_diabsen'  => $total - $absenCount,
            ],
        ];
    }
}
