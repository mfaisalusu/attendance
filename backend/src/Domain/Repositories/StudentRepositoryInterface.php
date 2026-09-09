<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Student;

interface StudentRepositoryInterface
{
    /** @return array{items: Student[], pagination: array} */
    public function paginate(
        int     $userId,
        int     $page,
        int     $limit,
        ?string $search  = null,
        ?int    $classId = null,
    ): array;

    public function findById(int $id, int $userId): ?Student;

    public function nipExistsForUser(string $nip, int $userId, ?int $excludeId = null): bool;

    public function create(int $userId, string $nip, string $name, int $classId): Student;

    public function update(int $id, int $userId, string $nip, string $name, int $classId): ?Student;

    public function delete(int $id, int $userId): bool;

    /** @return Student[] */
    public function listForAttendance(int $userId, ?int $classId = null): array;
}
