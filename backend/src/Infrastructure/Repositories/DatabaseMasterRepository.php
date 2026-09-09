<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\MasterRepositoryInterface;
use App\Domain\ValueObjects\MasterItem;
use RuntimeException;

/**
 * MySQL-backed implementation of MasterRepositoryInterface.
 * Reads/writes to the departments, courses, classes tables.
 * Semesters are fixed (1–8) and generated in-memory — no DB table needed.
 */
class DatabaseMasterRepository extends BaseRepository implements MasterRepositoryInterface
{
    // ------------------------------------------------------------------
    // Read
    // ------------------------------------------------------------------

    public function getDepartments(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name FROM departments WHERE user_id = :user_id ORDER BY name ASC'
        );
        $stmt->execute([':user_id' => $userId]);
        return array_map(fn($r) => MasterItem::fromArray($r), $stmt->fetchAll());
    }

    public function getCourses(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name FROM courses WHERE user_id = :user_id ORDER BY name ASC'
        );
        $stmt->execute([':user_id' => $userId]);
        return array_map(fn($r) => MasterItem::fromArray($r), $stmt->fetchAll());
    }

    public function getClasses(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name, semester_id, year FROM classes WHERE user_id = :user_id ORDER BY name ASC'
        );
        $stmt->execute([':user_id' => $userId]);
        return array_map(fn($r) => MasterItem::fromArray($r), $stmt->fetchAll());
    }

    public function getSemesters(): array
    {
        // Fixed values 1–8 — no database table needed
        $items = [];
        for ($i = 1; $i <= 8; $i++) {
            $items[] = new MasterItem($i, "Semester {$i}");
        }
        return $items;
    }

    public function findDepartmentById(int $id, int $userId): ?MasterItem
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name FROM departments WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ? MasterItem::fromArray($row) : null;
    }

    public function findCourseById(int $id, int $userId): ?MasterItem
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name FROM courses WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ? MasterItem::fromArray($row) : null;
    }

    public function findClassById(int $id, int $userId): ?MasterItem
    {
        $stmt = $this->db->prepare(
            'SELECT id, code, name, semester_id, year FROM classes WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ? MasterItem::fromArray($row) : null;
    }

    public function findSemesterById(int $id): ?MasterItem
    {
        if ($id < 1 || $id > 8) return null;
        return new MasterItem($id, "Semester {$id}");
    }

    // ------------------------------------------------------------------
    // Write — Departments
    // ------------------------------------------------------------------

    public function createDepartment(string $name, string $code, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'INSERT INTO departments (user_id, code, name) VALUES (:user_id, :code, :name)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':code'    => strtoupper(trim($code)),
            ':name'    => trim($name),
        ]);
        return $this->findDepartmentById((int) $this->db->lastInsertId(), $userId);
    }

    public function updateDepartment(int $id, string $name, string $code, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'UPDATE departments SET code = :code, name = :name
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':code'    => strtoupper(trim($code)),
            ':name'    => trim($name),
            ':id'      => $id,
            ':user_id' => $userId,
        ]);

        if ($stmt->rowCount() === 0) {
            // Could be not-found OR no-change — verify existence
            $item = $this->findDepartmentById($id, $userId);
            if ($item === null) throw new RuntimeException('Data tidak ditemukan.');
            return $item;
        }

        return $this->findDepartmentById($id, $userId);
    }

    public function deleteDepartment(int $id, int $userId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM departments WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Data tidak ditemukan.');
        }
    }

    // ------------------------------------------------------------------
    // Write — Courses
    // ------------------------------------------------------------------

    public function createCourse(string $name, string $code, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'INSERT INTO courses (user_id, code, name) VALUES (:user_id, :code, :name)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':code'    => strtoupper(trim($code)),
            ':name'    => trim($name),
        ]);
        return $this->findCourseById((int) $this->db->lastInsertId(), $userId);
    }

    public function updateCourse(int $id, string $name, string $code, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'UPDATE courses SET code = :code, name = :name
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':code'    => strtoupper(trim($code)),
            ':name'    => trim($name),
            ':id'      => $id,
            ':user_id' => $userId,
        ]);

        if ($stmt->rowCount() === 0) {
            $item = $this->findCourseById($id, $userId);
            if ($item === null) throw new RuntimeException('Data tidak ditemukan.');
            return $item;
        }

        return $this->findCourseById($id, $userId);
    }

    public function deleteCourse(int $id, int $userId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM courses WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Data tidak ditemukan.');
        }
    }

    // ------------------------------------------------------------------
    // Write — Classes
    // ------------------------------------------------------------------

    public function createClass(string $name, string $code, int $semesterId, int $year, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'INSERT INTO classes (user_id, code, name, semester_id, year)
             VALUES (:user_id, :code, :name, :semester_id, :year)'
        );
        $stmt->execute([
            ':user_id'     => $userId,
            ':code'        => strtoupper(trim($code)),
            ':name'        => trim($name),
            ':semester_id' => $semesterId,
            ':year'        => $year,
        ]);
        return $this->findClassById((int) $this->db->lastInsertId(), $userId);
    }

    public function updateClass(int $id, string $name, string $code, int $semesterId, int $year, int $userId): MasterItem
    {
        $stmt = $this->db->prepare(
            'UPDATE classes SET code = :code, name = :name, semester_id = :semester_id, year = :year
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':code'        => strtoupper(trim($code)),
            ':name'        => trim($name),
            ':semester_id' => $semesterId,
            ':year'        => $year,
            ':id'          => $id,
            ':user_id'     => $userId,
        ]);

        if ($stmt->rowCount() === 0) {
            $item = $this->findClassById($id, $userId);
            if ($item === null) throw new RuntimeException('Data tidak ditemukan.');
            return $item;
        }

        return $this->findClassById($id, $userId);
    }

    public function deleteClass(int $id, int $userId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM classes WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Data tidak ditemukan.');
        }
    }
}
