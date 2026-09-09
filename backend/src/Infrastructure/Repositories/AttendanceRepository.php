<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Attendance;
use App\Domain\Repositories\AttendanceRepositoryInterface;
use RuntimeException;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    // ------------------------------------------------------------------
    // List by date
    // ------------------------------------------------------------------

    public function listByDate(
        int     $userId,
        string  $date,
        ?int    $departmentId = null,
        ?int    $courseId     = null,
        ?int    $classId      = null,
        ?int    $semesterId   = null,
    ): array {
        [$join, $params] = $this->buildStudentJoin($userId, $departmentId, $courseId, $classId, $semesterId);

        $sql = "SELECT a.*
                FROM attendance a
                {$join}
                WHERE a.attendance_date = :date";

        $params[':date'] = $date;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(fn($row) => Attendance::fromArray($row), $stmt->fetchAll());
    }

    // ------------------------------------------------------------------
    // Find by ID (ownership check via JOIN)
    // ------------------------------------------------------------------

    public function findById(int $id, int $userId): ?Attendance
    {
        $stmt = $this->db->prepare(
            'SELECT a.*
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id AND s.user_id = :user_id
             WHERE a.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ? Attendance::fromArray($row) : null;
    }

    // ------------------------------------------------------------------
    // Bulk upsert (INSERT … ON DUPLICATE KEY UPDATE) — wrapped in transaction
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
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('Gagal menyimpan absensi. Transaksi dibatalkan.');
        }
    }

    // ------------------------------------------------------------------
    // Update single record
    // ------------------------------------------------------------------

    public function update(int $id, int $userId, string $status): ?Attendance
    {
        // Ownership check via JOIN on students
        $stmt = $this->db->prepare(
            'UPDATE attendance a
             INNER JOIN students s ON s.id = a.student_id AND s.user_id = :user_id
             SET a.status = :status, a.updated_at = NOW()
             WHERE a.id = :id'
        );
        $stmt->execute([':status' => $status, ':user_id' => $userId, ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        return $this->findById($id, $userId);
    }

    // ------------------------------------------------------------------
    // Monthly recap — SQL aggregation
    // ------------------------------------------------------------------

    public function monthlyRecap(
        int  $userId,
        int  $year,
        int  $month,
        ?int $departmentId = null,
        ?int $courseId     = null,
        ?int $classId      = null,
        ?int $semesterId   = null,
    ): array {
        $conditions = ['s.user_id = :user_id'];
        $params     = [':user_id' => $userId, ':year' => $year, ':month' => $month];

        if ($departmentId !== null) {
            $conditions[] = 's.department_id = :dept_id';
            $params[':dept_id'] = $departmentId;
        }
        if ($courseId !== null) {
            $conditions[] = 's.course_id = :course_id';
            $params[':course_id'] = $courseId;
        }
        if ($classId !== null) {
            $conditions[] = 's.class_id = :class_id';
            $params[':class_id'] = $classId;
        }
        if ($semesterId !== null) {
            $conditions[] = 's.semester_id = :semester_id';
            $params[':semester_id'] = $semesterId;
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT
                    s.id                                                            AS student_id,
                    s.nip,
                    s.name,
                    SUM(a.status = 'hadir')                                         AS hadir,
                    SUM(a.status = 'izin')                                          AS izin,
                    SUM(a.status = 'sakit')                                         AS sakit,
                    SUM(a.status = 'alpha')                                         AS alpha,
                    COUNT(a.id)                                                     AS total_pertemuan,
                    ROUND(
                        IF(COUNT(a.id) = 0, 0,
                           SUM(a.status = 'hadir') / COUNT(a.id) * 100
                        ), 2
                    )                                                               AS persentase
                FROM students s
                LEFT JOIN attendance a
                    ON  a.student_id = s.id
                    AND YEAR(a.attendance_date)  = :year
                    AND MONTH(a.attendance_date) = :month
                WHERE {$where}
                GROUP BY s.id, s.nip, s.name
                ORDER BY s.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(function (array $row): array {
            return [
                'student_id'       => (int)   $row['student_id'],
                'nip'              =>          $row['nip'],
                'name'             =>          $row['name'],
                'hadir'            => (int)   $row['hadir'],
                'izin'             => (int)   $row['izin'],
                'sakit'            => (int)   $row['sakit'],
                'alpha'            => (int)   $row['alpha'],
                'total_pertemuan'  => (int)   $row['total_pertemuan'],
                'persentase'       => (float) $row['persentase'],
            ];
        }, $stmt->fetchAll());
    }

    // ------------------------------------------------------------------
    // Daily summary (used by dashboard + attendance page indicator)
    // ------------------------------------------------------------------

    public function summaryByDate(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                 COUNT(a.id)                    AS total_absen,
                 SUM(a.status = 'hadir')        AS hadir,
                 SUM(a.status = 'izin')         AS izin,
                 SUM(a.status = 'sakit')        AS sakit,
                 SUM(a.status = 'alpha')        AS alpha
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id AND s.user_id = :user_id
             WHERE a.attendance_date = :date"
        );
        $stmt->execute([':user_id' => $userId, ':date' => $date]);
        $row = $stmt->fetch();

        return [
            'date'         => $date,
            'total_absen'  => (int) $row['total_absen'],
            'hadir'        => (int) $row['hadir'],
            'izin'         => (int) $row['izin'],
            'sakit'        => (int) $row['sakit'],
            'alpha'        => (int) $row['alpha'],
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Build JOIN + WHERE clause to scope attendance to students owned by $userId.
     */
    private function buildStudentJoin(
        int  $userId,
        ?int $departmentId,
        ?int $courseId,
        ?int $classId,
        ?int $semesterId,
    ): array {
        $joinConditions = ['s.id = a.student_id', 's.user_id = :user_id'];
        $params         = [':user_id' => $userId];

        if ($departmentId !== null) {
            $joinConditions[] = 's.department_id = :dept_id';
            $params[':dept_id'] = $departmentId;
        }
        if ($courseId !== null) {
            $joinConditions[] = 's.course_id = :course_id';
            $params[':course_id'] = $courseId;
        }
        if ($classId !== null) {
            $joinConditions[] = 's.class_id = :class_id';
            $params[':class_id'] = $classId;
        }
        if ($semesterId !== null) {
            $joinConditions[] = 's.semester_id = :semester_id';
            $params[':semester_id'] = $semesterId;
        }

        $join = 'INNER JOIN students s ON ' . implode(' AND ', $joinConditions);

        return [$join, $params];
    }
}
