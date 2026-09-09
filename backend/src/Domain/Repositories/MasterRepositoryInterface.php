<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\ValueObjects\MasterItem;

interface MasterRepositoryInterface
{
    // ----------------------------------------------------------------
    // Read  (semua difilter per userId)
    // ----------------------------------------------------------------

    /** @return MasterItem[] */
    public function getDepartments(int $userId): array;

    /** @return MasterItem[] */
    public function getCourses(int $userId): array;

    /** @return MasterItem[] */
    public function getClasses(int $userId): array;

    /** @return MasterItem[] */
    public function getSemesters(): array;   // global — tidak per-user

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
    // Write — Courses
    // ----------------------------------------------------------------

    public function createCourse(string $name, string $code, int $userId): MasterItem;

    public function updateCourse(int $id, string $name, string $code, int $userId): MasterItem;

    public function deleteCourse(int $id, int $userId): void;

    // ----------------------------------------------------------------
    // Write — Classes
    // ----------------------------------------------------------------

    public function createClass(string $name, string $code, int $semesterId, int $year, int $userId): MasterItem;

    public function updateClass(int $id, string $name, string $code, int $semesterId, int $year, int $userId): MasterItem;

    public function deleteClass(int $id, int $userId): void;
}
