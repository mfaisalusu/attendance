<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Student;

interface StudentRepositoryInterface
{
    /**
     * @return array{items: Student[], pagination: array}
     */
    public function paginate(
        int     $userId,
        int     $page,
        int     $limit,
        ?string $search       = null,
        ?int    $departmentId = null,
        ?int    $courseId     = null,
        ?int    $classId      = null,
        ?int    $semesterId   = null,
    ): array;

    public function findById(int $id, int $userId): ?Student;

    public function nipExistsForUser(string $nip, int $userId, ?int $excludeId = null): bool;

    public function create(
        int    $userId,
        string $nip,
        string $name,
        int    $departmentId,
        int    $courseId,
        int    $classId,
        int    $semesterId,
    ): Student;

    public function update(
        int    $id,
        int    $userId,
        string $nip,
        string $name,
        int    $departmentId,
        int    $courseId,
        int    $classId,
        int    $semesterId,
    ): ?Student;

    public function delete(int $id, int $userId): bool;

    /**
     * List all students (no pagination) for attendance forms.
     * @return Student[]
     */
    public function listForAttendance(
        int  $userId,
        ?int $departmentId = null,
        ?int $courseId     = null,
        ?int $classId      = null,
        ?int $semesterId   = null,
    ): array;
}
