<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

class DashboardRepository extends BaseRepository
{
    /**
     * Total students belonging to this user.
     */
    public function totalStudents(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM students WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Attendance counts for a specific date, scoped to the user's students.
     */
    public function attendanceByDate(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                 COUNT(a.id)                 AS total_absen,
                 SUM(a.status = 'hadir')     AS hadir,
                 SUM(a.status = 'izin')      AS izin,
                 SUM(a.status = 'sakit')     AS sakit,
                 SUM(a.status = 'alpha')     AS alpha
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id AND s.user_id = :user_id
             WHERE a.attendance_date = :date"
        );
        $stmt->execute([':user_id' => $userId, ':date' => $date]);
        $row = $stmt->fetch();

        return [
            'total_absen' => (int) $row['total_absen'],
            'hadir'       => (int) $row['hadir'],
            'izin'        => (int) $row['izin'],
            'sakit'       => (int) $row['sakit'],
            'alpha'       => (int) $row['alpha'],
        ];
    }
}
