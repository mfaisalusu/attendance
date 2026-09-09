<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Attendance;
use App\Domain\Repositories\AttendanceRepositoryInterface;
use RuntimeException;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    // ------------------------------------------------------------------
    // List students + their attendance for a given date, scoped by class
    // ------------------------------------------------------------------

    public function listByDate(int $userId, string $date, ?int $classId = null): array
    {
        [$join, $params] = $this->buildStudentJoin($userId, $classId);
        $sql = "SELECT a.* FROM attendance a {$join} WHERE a.attendance_date = :date";
        $params[':date'] = $date;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($row) => Attendance::fromArray($row), $stmt->fetchAll());
    }

    // ------------------------------------------------------------------
    // Find single record (ownership check via JOIN)
    // ------------------------------------------------------------------

    public function findById(int $id, int $userId): ?Attendance
    {
        $stmt = $this->db->prepare(
            'SELECT a.* FROM attendance a
             INNER JOIN students s ON s.id = a.student_id AND s.user_id = :uid
             WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ? Attendance::fromArray($row) : null;
    }

    // ------------------------------------------------------------------
    // Bulk upsert
    // ------------------------------------------------------------------

    public function bulkUpsert(int $userId, string $date, array $items): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO attendance (student_id, attendance_date, status)
                 VALUES (:student_id, :date, :status)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()'
            );
            foreach ($items as $item) {
                $stmt->execute([
                    ':student_id' => (int) $item['student_id'],
                    ':date'       => $date,
                    ':status'     => $item['status'],
                ]);
            }
            $this->db->commit();
        } catch (\Throwable) {
            $this->db->rollBack();
            throw new RuntimeException('Gagal menyimpan absensi. Transaksi dibatalkan.');
        }
    }

    // ------------------------------------------------------------------
    // Update single record
    // ------------------------------------------------------------------

    public function update(int $id, int $userId, string $status): ?Attendance
    {
        $stmt = $this->db->prepare(
            'UPDATE attendance a
             INNER JOIN students s ON s.id = a.student_id AND s.user_id = :uid
             SET a.status = :status, a.updated_at = NOW()
             WHERE a.id = :id'
        );
        $stmt->execute([':status' => $status, ':uid' => $userId, ':id' => $id]);
        return $stmt->rowCount() > 0 ? $this->findById($id, $userId) : null;
    }

    // ------------------------------------------------------------------
    // Monthly recap — matrix harian (satu baris per mahasiswa, kolom = tanggal)
    // Kembalikan: array keyed by student_id => ['nip', 'name', 'days' => [1=>'H', 2=>null, ...]]
    // ------------------------------------------------------------------

    public function monthlyMatrix(int $userId, int $year, int $month, ?int $classId = null): array
    {
        $conditions = ['s.user_id = :uid'];
        $params     = [':uid' => $userId, ':year' => $year, ':month' => $month];

        if ($classId !== null) {
            $conditions[] = 's.class_id = :class_id';
            $params[':class_id'] = $classId;
        }

        $where = implode(' AND ', $conditions);

        // 1. Fetch all students in scope (ordered by name)
        $sStmt = $this->db->prepare(
            "SELECT s.id, s.nip, s.name FROM students s WHERE {$where} ORDER BY s.name ASC"
        );
        $sStmt->execute($params);
        $students = $sStmt->fetchAll();

        if (empty($students)) return [];

        // 2. Fetch all attendance records for this month
        $aStmt = $this->db->prepare(
            "SELECT a.student_id, DAY(a.attendance_date) AS day, a.status
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id
             WHERE {$where}
               AND YEAR(a.attendance_date)  = :year
               AND MONTH(a.attendance_date) = :month"
        );
        $aStmt->execute($params);
        $records = $aStmt->fetchAll();

        // 3. Build lookup: [student_id][day] => status abbreviation
        $statusMap = [
            'hadir' => 'H',
            'izin'  => 'I',
            'sakit' => 'S',
            'alpha' => 'A',
        ];
        $lookup = [];
        foreach ($records as $r) {
            $lookup[(int) $r['student_id']][(int) $r['day']] = $statusMap[$r['status']] ?? $r['status'];
        }

        // 4. Number of days in the month
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

        // 5. Build matrix rows
        $rows = [];
        foreach ($students as $s) {
            $sid  = (int) $s['id'];
            $days = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $days[$d] = $lookup[$sid][$d] ?? null;
            }
            $rows[] = [
                'student_id' => $sid,
                'nip'        => $s['nip'],
                'name'       => $s['name'],
                'days'       => $days,
            ];
        }

        return $rows;
    }

    // ------------------------------------------------------------------
    // Summary by date (for dashboard)
    // ------------------------------------------------------------------

    public function summaryByDate(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(a.id) AS total_absen,
                    SUM(a.status='hadir') AS hadir, SUM(a.status='izin') AS izin,
                    SUM(a.status='sakit') AS sakit, SUM(a.status='alpha') AS alpha
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id AND s.user_id = :uid
             WHERE a.attendance_date = :date"
        );
        $stmt->execute([':uid' => $userId, ':date' => $date]);
        $row = $stmt->fetch();
        return [
            'date'        => $date,
            'total_absen' => (int) $row['total_absen'],
            'hadir'       => (int) $row['hadir'],
            'izin'        => (int) $row['izin'],
            'sakit'       => (int) $row['sakit'],
            'alpha'       => (int) $row['alpha'],
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function buildStudentJoin(int $userId, ?int $classId): array
    {
        $conditions = ['s.id = a.student_id', 's.user_id = :uid'];
        $params     = [':uid' => $userId];

        if ($classId !== null) {
            $conditions[] = 's.class_id = :class_id';
            $params[':class_id'] = $classId;
        }

        return ['INNER JOIN students s ON ' . implode(' AND ', $conditions), $params];
    }
}
