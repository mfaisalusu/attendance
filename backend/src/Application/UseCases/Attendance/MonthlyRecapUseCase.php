<?php

declare(strict_types=1);

namespace App\Application\UseCases\Attendance;

use App\Domain\Repositories\AttendanceRepositoryInterface;

class MonthlyRecapUseCase
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    public function execute(int $userId, int $year, int $month, ?int $classId = null, ?int $courseId = null): array
    {
        $rows        = $this->attendanceRepository->monthlyMatrix($userId, $year, $month, $classId, $courseId);
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

        return [
            'year'         => $year,
            'month'        => $month,
            'days_in_month'=> $daysInMonth,
            'rows'         => $rows,
        ];
    }
}
