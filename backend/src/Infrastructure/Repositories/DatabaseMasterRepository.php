<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\MasterRepositoryInterface;
use App\Domain\ValueObjects\MasterItem;
use RuntimeException;

class DatabaseMasterRepository extends BaseRepository implements MasterRepositoryInterface
{
    // ------------------------------------------------------------------
    // Departments
    // ------------------------------------------------------------------

    public function getDepartments(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name FROM departments WHERE user_id = :uid ORDER BY name ASC'
        );
        $stmt->execute([':uid' => $userId]);
        return array_map(fn($r) => MasterItem::fromArray($r), $stmt->fetchAll());
    }

    public function findDepartmentById(int $id, int $userId): ?MasterItem
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name FROM departments WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ? MasterItem::fromArray($row) : null;
    }

    public function createDepartment(string $name, string $code, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'INSERT INTO departments (user_id, code, name) VALUES (:uid, :code, :name)'
        );
        $stmt->execute([':uid' => $userId, ':code' => strtoupper(trim($code)), ':name' => trim($name)]);
        return $this->findDepartmentById((int) $this->db->lastInsertId(), $userId);
    }

    public function updateDepartment(int $id, string $name, string $code, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'UPDATE departments SET code = :code, name = :name WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([':code' => strtoupper(trim($code)), ':name' => trim($name), ':id' => $id, ':uid' => $userId]);
        $item = $this->findDepartmentById($id, $userId);
        if ($item === null) throw new RuntimeException('Data tidak ditemukan.');
        return $item;
    }

    public function deleteDepartment(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM departments WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        if ($stmt->rowCount() === 0) throw new RuntimeException('Data tidak ditemukan.');
    }

    // ------------------------------------------------------------------
    // Courses
    // ------------------------------------------------------------------

    public function getCourses(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.code, c.name, c.department_id,
                    d.name AS department_name, d.code AS department_code
             FROM courses c
             JOIN departments d ON d.id = c.department_id
             WHERE c.user_id = :uid
             ORDER BY c.name ASC'
        );
        $stmt->execute([':uid' => $userId]);
        return array_map(fn($r) => MasterItem::fromArray($r), $stmt->fetchAll());
    }

    public function findCourseById(int $id, int $userId): ?MasterItem
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.code, c.name, c.department_id,
                    d.name AS department_name, d.code AS department_code
             FROM courses c
             JOIN departments d ON d.id = c.department_id
             WHERE c.id = :id AND c.user_id = :uid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ? MasterItem::fromArray($row) : null;
    }

    public function createCourse(string $name, string $code, int $departmentId, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'INSERT INTO courses (user_id, department_id, code, name) VALUES (:uid, :dept, :code, :name)'
        );
        $stmt->execute([':uid' => $userId, ':dept' => $departmentId, ':code' => strtoupper(trim($code)), ':name' => trim($name)]);
        return $this->findCourseById((int) $this->db->lastInsertId(), $userId);
    }

    public function updateCourse(int $id, string $name, string $code, int $departmentId, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'UPDATE courses SET code = :code, name = :name, department_id = :dept WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([':code' => strtoupper(trim($code)), ':name' => trim($name), ':dept' => $departmentId, ':id' => $id, ':uid' => $userId]);
        $item = $this->findCourseById($id, $userId);
        if ($item === null) throw new RuntimeException('Data tidak ditemukan.');
        return $item;
    }

    public function deleteCourse(int $id, int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM courses WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        if ($stmt->rowCount() === 0) throw new RuntimeException('Data tidak ditemukan.');
    }

    // ------------------------------------------------------------------
    // Classes
    // ------------------------------------------------------------------

    public function getClasses(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT cl.id, cl.code, cl.name, cl.department_id, cl.semester_id, cl.year,
                    d.name AS department_name, d.code AS department_code
             FROM classes cl
             JOIN departments d ON d.id = cl.department_id
             WHERE cl.user_id = :uid
             ORDER BY cl.name ASC'
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map(function (array $row) {
            $row['course_ids'] = $this->loadCourseIdsForClass((int) $row['id']);
            return MasterItem::fromArray($row);
        }, $rows);
    }

    public function findClassById(int $id, int $userId): ?MasterItem
    {
        $stmt = $this->db->prepare(
            'SELECT cl.id, cl.code, cl.name, cl.department_id, cl.semester_id, cl.year,
                    d.name AS department_name, d.code AS department_code
             FROM classes cl
             JOIN departments d ON d.id = cl.department_id
             WHERE cl.id = :id AND cl.user_id = :uid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['course_ids'] = $this->loadCourseIdsForClass($id);
        return MasterItem::fromArray($row);
    }

    public function createClass(
        string $name, string $code, int $departmentId,
        int $semesterId, int $year, array $courseIds, int $userId
    ): MasterItem {
        $stmt = $this->db->prepare(
            'INSERT INTO classes (user_id, department_id, code, name, semester_id, year)
             VALUES (:uid, :dept, :code, :name, :sem, :year)'
        );
        $stmt->execute([
            ':uid' => $userId, ':dept' => $departmentId,
            ':code' => strtoupper(trim($code)), ':name' => trim($name),
            ':sem' => $semesterId, ':year' => $year,
        ]);
        $classId = (int) $this->db->lastInsertId();
        $this->syncClassCourses($classId, $courseIds);
        return $this->findClassById($classId, $userId);
    }

    public function updateClass(
        int $id, string $name, string $code, int $departmentId,
        int $semesterId, int $year, array $courseIds, int $userId
    ): MasterItem {
        $stmt = $this->db->prepare(
            'UPDATE classes SET code = :code, name = :name, department_id = :dept,
             semester_id = :sem, year = :year WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            ':code' => strtoupper(trim($code)), ':name' => trim($name),
            ':dept' => $departmentId, ':sem' => $semesterId, ':year' => $year,
            ':id' => $id, ':uid' => $userId,
        ]);
        $item = $this->findClassById($id, $userId);
        if ($item === null) throw new RuntimeException('Data tidak ditemukan.');
        $this->syncClassCourses($id, $courseIds);
        return $this->findClassById($id, $userId);
    }

    public function deleteClass(int $id, int $userId): void
    {
        // class_courses FK ON DELETE CASCADE — tidak perlu hapus manual
        $stmt = $this->db->prepare('DELETE FROM classes WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        if ($stmt->rowCount() === 0) throw new RuntimeException('Data tidak ditemukan.');
    }

    // ------------------------------------------------------------------
    // Semesters (global, fixed 1–8)
    // ------------------------------------------------------------------

    public function getSemesters(): array
    {
        $items = [];
        for ($i = 1; $i <= 8; $i++) {
            $items[] = new MasterItem($i, "Semester {$i}");
        }
        return $items;
    }

    public function findSemesterById(int $id): ?MasterItem
    {
        if ($id < 1 || $id > 8) return null;
        return new MasterItem($id, "Semester {$id}");
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** @return int[] */
    private function loadCourseIdsForClass(int $classId): array
    {
        $stmt = $this->db->prepare(
            'SELECT course_id FROM class_courses WHERE class_id = :class_id ORDER BY course_id ASC'
        );
        $stmt->execute([':class_id' => $classId]);
        return array_map(fn($r) => (int) $r['course_id'], $stmt->fetchAll());
    }

    /** @param int[] $courseIds */
    private function syncClassCourses(int $classId, array $courseIds): void
    {
        // Delete existing then re-insert (simple full-replace strategy)
        $del = $this->db->prepare('DELETE FROM class_courses WHERE class_id = :class_id');
        $del->execute([':class_id' => $classId]);

        if (empty($courseIds)) return;

        $ins = $this->db->prepare(
            'INSERT IGNORE INTO class_courses (class_id, course_id) VALUES (:class_id, :course_id)'
        );
        foreach (array_unique($courseIds) as $courseId) {
            $ins->execute([':class_id' => $classId, ':course_id' => (int) $courseId]);
        }
    }
}
