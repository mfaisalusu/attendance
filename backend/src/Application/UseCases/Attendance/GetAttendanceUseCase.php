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

    public function execute(int $userId, string $date, ?int $classId = null): array
    {
        $students  = $this->studentRepository->listForAttendance($userId, $classId);
        $records   = $this->attendanceRepository->listByDate($userId, $date, $classId);

        $recordMap = [];
        foreach ($records as $r) { $recordMap[$r->studentId] = $r; }

        $items      = [];
        $absenCount = 0;
        foreach ($students as $s) {
            $rec    = $recordMap[$s->id] ?? null;
            $item   = $s->toArray();
            $item['attendance'] = $rec ? ['id' => $rec->id, 'status' => $rec->status] : null;
            if ($rec !== null) $absenCount++;
            $items[] = $item;
        }

        $total = count($students);
        return [
            'date'     => $date,
            'students' => $items,
            'summary'  => [
                'total'          => $total,
                'sudah_diabsen'  => $absenCount,
                'belum_diabsen'  => $total - $absenCount,
            ],
        ];
    }
}
