<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\ValueObjects\MasterItem;

interface MasterRepositoryInterface
{
    // ----------------------------------------------------------------
    // Read
    // ----------------------------------------------------------------

    /** @return MasterItem[] */
    public function getDepartments(int $userId): array;

    /** @return MasterItem[] — setiap item menyertakan department_id */
    public function getCourses(int $userId): array;

    /** @return MasterItem[] — setiap item menyertakan department_id, semester_id, year, course_ids[] */
    public function getClasses(int $userId): array;

    /** @return MasterItem[] — fixed 1–8, global */
    public function getSemesters(): array;

    public function findDepartmentById(int $id, int $userId): ?MasterItem;
    public function findCourseById(int $id, int $userId): ?MasterItem;
    public function findClassById(int $id, int $userId): ?MasterItem;
    public function findSemesterById(int $id): ?MasterItem;

    // ----------------------------------------------------------------
    // Write — Departments
    // ----------------------------------------------------------------

    public function createDepartment(string $name, string $code, int $userId): MasterItem;
    public function updateDepartment(int $id, string $name, string $code, int $userId): MasterItem;
    public function deleteDepartment(int $id, int $userId): void;

    // ----------------------------------------------------------------
    // Write — Courses  (department_id wajib)
    // ----------------------------------------------------------------

    public function createCourse(string $name, string $code, int $departmentId, int $userId): MasterItem;
    public function updateCourse(int $id, string $name, string $code, int $departmentId, int $userId): MasterItem;
    public function deleteCourse(int $id, int $userId): void;

    // ----------------------------------------------------------------
    // Write — Classes  (department_id + course_ids[] wajib)
    // ----------------------------------------------------------------

    /** @param int[] $courseIds */
    public function createClass(string $name, string $code, int $departmentId, int $semesterId, int $year, array $courseIds, int $userId): MasterItem;

    /** @param int[] $courseIds */
    public function updateClass(int $id, string $name, string $code, int $departmentId, int $semesterId, int $year, array $courseIds, int $userId): MasterItem;

    public function deleteClass(int $id, int $userId): void;
}
