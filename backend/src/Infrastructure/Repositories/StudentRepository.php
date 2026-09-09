<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Student;
use App\Domain\Repositories\StudentRepositoryInterface;

class StudentRepository extends BaseRepository implements StudentRepositoryInterface
{
    public function paginate(
        int     $userId,
        int     $page,
        int     $limit,
        ?string $search       = null,
        ?int    $departmentId = null,
        ?int    $courseId     = null,
        ?int    $classId      = null,
        ?int    $semesterId   = null,
    ): array {
        $page  = max(1, $page);
        $limit = max(1, min(100, $limit));

        [$where, $params] = $this->buildWhere($userId, $search, $departmentId, $courseId, $classId, $semesterId);

        // Count total
        $countSql = "SELECT COUNT(*) FROM students {$where}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch page
        $offset     = ($page - 1) * $limit;
        $totalPages = (int) ceil($total / $limit);

        $dataSql  = "SELECT * FROM students {$where} ORDER BY name ASC LIMIT :limit OFFSET :offset";
        $dataStmt = $this->db->prepare($dataSql);

        // PDO named params and positional don't mix — bind separately
        foreach ($params as $key => $value) {
            $dataStmt->bindValue($key, $value);
        }
        $dataStmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();

        $rows = $dataStmt->fetchAll();

        return [
            'items'      => array_map(fn($row) => Student::fromArray($row), $rows),
            'pagination' => [
                'page'        => $page,
                'limit'       => $limit,
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public function findById(int $id, int $userId): ?Student
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM students WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ? Student::fromArray($row) : null;
    }

    public function nipExistsForUser(string $nip, int $userId, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM students WHERE nip = :nip AND user_id = :user_id AND id != :exclude_id'
            );
            $stmt->execute([':nip' => $nip, ':user_id' => $userId, ':exclude_id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM students WHERE nip = :nip AND user_id = :user_id'
            );
            $stmt->execute([':nip' => $nip, ':user_id' => $userId]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(
        int    $userId,
        string $nip,
        string $name,
        int    $departmentId,
        int    $courseId,
        int    $classId,
        int    $semesterId,
    ): Student {
        $stmt = $this->db->prepare(
            'INSERT INTO students (user_id, nip, name, department_id, course_id, class_id, semester_id)
             VALUES (:user_id, :nip, :name, :dept, :course, :class, :semester)'
        );
        $stmt->execute([
            ':user_id'  => $userId,
            ':nip'      => $nip,
            ':name'     => $name,
            ':dept'     => $departmentId,
            ':course'   => $courseId,
            ':class'    => $classId,
            ':semester' => $semesterId,
        ]);

        return $this->findById((int) $this->db->lastInsertId(), $userId);
    }

    public function update(
        int    $id,
        int    $userId,
        string $nip,
        string $name,
        int    $departmentId,
        int    $courseId,
        int    $classId,
        int    $semesterId,
    ): ?Student {
        $stmt = $this->db->prepare(
            'UPDATE students
             SET nip = :nip, name = :name, department_id = :dept,
                 course_id = :course, class_id = :class, semester_id = :semester
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':nip'      => $nip,
            ':name'     => $name,
            ':dept'     => $departmentId,
            ':course'   => $courseId,
            ':class'    => $classId,
            ':semester' => $semesterId,
            ':id'       => $id,
            ':user_id'  => $userId,
        ]);

        return $this->findById($id, $userId);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM students WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function listForAttendance(
        int  $userId,
        ?int $departmentId = null,
        ?int $courseId     = null,
        ?int $classId      = null,
        ?int $semesterId   = null,
    ): array {
        [$where, $params] = $this->buildWhere(
            $userId, null, $departmentId, $courseId, $classId, $semesterId
        );

        $stmt = $this->db->prepare(
            "SELECT * FROM students {$where} ORDER BY name ASC"
        );
        $stmt->execute($params);

        return array_map(fn($row) => Student::fromArray($row), $stmt->fetchAll());
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Build WHERE clause and named-param array from optional filters.
     */
    private function buildWhere(
        int     $userId,
        ?string $search,
        ?int    $departmentId,
        ?int    $courseId,
        ?int    $classId,
        ?int    $semesterId,
    ): array {
        $conditions = ['user_id = :user_id'];
        $params     = [':user_id' => $userId];

        if ($search !== null && $search !== '') {
            $conditions[] = '(nip LIKE :search OR name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($departmentId !== null) {
            $conditions[] = 'department_id = :dept_id';
            $params[':dept_id'] = $departmentId;
        }

        if ($courseId !== null) {
            $conditions[] = 'course_id = :course_id';
            $params[':course_id'] = $courseId;
        }

        if ($classId !== null) {
            $conditions[] = 'class_id = :class_id';
            $params[':class_id'] = $classId;
        }

        if ($semesterId !== null) {
            $conditions[] = 'semester_id = :semester_id';
            $params[':semester_id'] = $semesterId;
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        return [$where, $params];
    }
}
