<?php

declare(strict_types=1);

namespace App\Application\UseCases\Dashboard;

use App\Infrastructure\Repositories\DashboardRepository;

class GetDashboardStatsUseCase
{
    public function __construct(
        private readonly DashboardRepository $dashboardRepository,
    ) {}

    public function execute(int $userId): array
    {
        $today           = date('Y-m-d');
        $totalStudents   = $this->dashboardRepository->totalStudents($userId);
        $todayAttendance = $this->dashboardRepository->attendanceByDate($userId, $today);

        return [
            'total_mahasiswa'   => $totalStudents,
            'today'             => $today,
            'total_absen_hari_ini' => $todayAttendance['total_absen'],
            'hadir_hari_ini'    => $todayAttendance['hadir'],
            'izin_hari_ini'     => $todayAttendance['izin'],
            'sakit_hari_ini'    => $todayAttendance['sakit'],
            'alpha_hari_ini'    => $todayAttendance['alpha'],
            'shortcuts' => [
                ['label' => 'Tambah Mahasiswa',  'path' => '/students/create'],
                ['label' => 'Absensi Hari Ini',  'path' => '/attendance'],
                ['label' => 'Rekap Absensi',     'path' => '/attendance/recap'],
            ],
        ];
    }
}
