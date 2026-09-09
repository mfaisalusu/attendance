<?php

declare(strict_types=1);

namespace App\Application\UseCases\Attendance;

use App\Domain\Repositories\AttendanceRepositoryInterface;

class MonthlyRecapUseCase
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    public function execute(
        int  $userId,
        int  $year,
        int  $month,
        ?int $departmentId = null,
        ?int $courseId     = null,
        ?int $classId      = null,
        ?int $semesterId   = null,
    ): array {
        $rows = $this->attendanceRepository->monthlyRecap(
            $userId, $year, $month,
            $departmentId, $courseId, $classId, $semesterId
        );

        // Compute totals for summary
        $totalHadir = 0;
        $totalIzin  = 0;
        $totalSakit = 0;
        $totalAlpha = 0;

        foreach ($rows as $row) {
            $totalHadir += (int) $row['hadir'];
            $totalIzin  += (int) $row['izin'];
            $totalSakit += (int) $row['sakit'];
            $totalAlpha += (int) $row['alpha'];
        }

        return [
            'year'    => $year,
            'month'   => $month,
            'summary' => [
                'total_mahasiswa' => count($rows),
                'total_hadir'     => $totalHadir,
                'total_izin'      => $totalIzin,
                'total_sakit'     => $totalSakit,
                'total_alpha'     => $totalAlpha,
            ],
            'rows'    => $rows,
        ];
    }
}
