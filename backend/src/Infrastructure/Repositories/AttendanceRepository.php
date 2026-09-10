<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Attendance;
use App\Domain\Repositories\AttendanceRepositoryInterface;
use RuntimeException;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    // ------------------------------------------------------------------
    // List attendance untuk tanggal tertentu, difilter by class + course
    // course_id wajib diisi karena attendance dibedakan per mata kuliah
    // ------------------------------------------------------------------

    public function listByDate(
        int    $userId,
        string $date,
        ?int   $classId  = null,
        ?int   $courseId = null
    ): array {
        $conditions = ['s.user_id = :uid', 'a.attendance_date = :date'];
        $params     = [':uid' => $userId, ':date' => $date];

        // JOIN ke students
        $joins = ['INNER JOIN students s ON s.id = a.student_id'];

        if ($classId !== null) {
            $conditions[]       = 's.class_id = :class_id';
            $params[':class_id'] = $classId;
        }

        if ($courseId !== null) {
            $conditions[]        = 'a.course_id = :course_id';
            $params[':course_id'] = $courseId;
        }

        $joinSql  = implode(' ', $joins);
        $whereSql = implode(' AND ', $conditions);

        $stmt = $this->db->prepare(
            "SELECT a.* FROM attendance a {$joinSql} WHERE {$whereSql}"
        );
        $stmt->execute($params);
        return array_map(fn($row) => Attendance::fromArray($row), $stmt->fetchAll());
    }

    // ------------------------------------------------------------------
    // Find single record
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
    // Bulk upsert — setiap item harus menyertakan course_id
    // UNIQUE KEY: (student_id, course_id, attendance_date)
    // ------------------------------------------------------------------

    public function bulkUpsert(int $userId, string $date, int $courseId, array $items): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO attendance (student_id, course_id, attendance_date, status)
                 VALUES (:student_id, :course_id, :date, :status)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()'
            );
            foreach ($items as $item) {
                $stmt->execute([
                    ':student_id' => (int) $item['student_id'],
                    ':course_id'  => $courseId,
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
    // Monthly recap — matrix harian per (student × hari)
    // Filter by class + course; attendance dibedakan per course_id
    // ------------------------------------------------------------------

    public function monthlyMatrix(
        int  $userId,
        int  $year,
        int  $month,
        ?int $classId  = null,
        ?int $courseId = null
    ): array {
        $studentConds  = ['s.user_id = :uid'];
        $studentParams = [':uid' => $userId];

        if ($classId !== null) {
            $studentConds[]             = 's.class_id = :class_id';
            $studentParams[':class_id'] = $classId;
        }

        // Filter mahasiswa berdasarkan course via class_courses pivot
        $courseJoin = '';
        if ($courseId !== null) {
            $courseJoin                    = 'INNER JOIN class_courses cc ON cc.class_id = s.class_id AND cc.course_id = :course_id';
            $studentParams[':course_id']   = $courseId;
        }

        $studentWhere = implode(' AND ', $studentConds);

        // 1. Fetch students in scope
        $sStmt = $this->db->prepare(
            "SELECT s.id, s.nip, s.name
             FROM students s {$courseJoin}
             WHERE {$studentWhere}
             ORDER BY s.name ASC"
        );
        $sStmt->execute($studentParams);
        $students = $sStmt->fetchAll();

        if (empty($students)) return [];

        // 2. Fetch attendance records untuk bulan ini
        // Filter course_id langsung dari kolom attendance.course_id — tidak perlu JOIN pivot
        $attendParams = [':uid' => $userId, ':year' => $year, ':month' => $month];
        $attendConds  = ['s.user_id = :uid', 'YEAR(a.attendance_date) = :year', 'MONTH(a.attendance_date) = :month'];

        if ($classId !== null) {
            $attendConds[]               = 's.class_id = :class_id';
            $attendParams[':class_id']   = $classId;
        }
        if ($courseId !== null) {
            $attendConds[]               = 'a.course_id = :course_id';
            $attendParams[':course_id']  = $courseId;
        }

        $attendWhere = implode(' AND ', $attendConds);

        $aStmt = $this->db->prepare(
            "SELECT a.student_id, DAY(a.attendance_date) AS day, a.status
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id
             WHERE {$attendWhere}"
        );
        $aStmt->execute($attendParams);
        $records = $aStmt->fetchAll();

        // 3. Build lookup [student_id][day] => abbreviation
        $abbr   = ['hadir' => 'H', 'izin' => 'I', 'sakit' => 'S', 'alpha' => 'A'];
        $lookup = [];
        foreach ($records as $r) {
            $lookup[(int) $r['student_id']][(int) $r['day']] = $abbr[$r['status']] ?? $r['status'];
        }

        // 4. Build matrix rows
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $rows        = [];

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
}
