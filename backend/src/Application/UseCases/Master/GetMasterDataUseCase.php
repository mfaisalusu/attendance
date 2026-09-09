<?php

declare(strict_types=1);

namespace App\Application\UseCases\Master;

use App\Domain\Repositories\MasterRepositoryInterface;

class GetMasterDataUseCase
{
    public function __construct(
        private readonly MasterRepositoryInterface $masterRepository,
    ) {}

    // ------------------------------------------------------------------
    // Read
    // ------------------------------------------------------------------

    public function getDepartments(int $userId): array
    {
        return array_map(fn($i) => $i->toArray(), $this->masterRepository->getDepartments($userId));
    }

    public function getCourses(int $userId): array
    {
        return array_map(fn($i) => $i->toArray(), $this->masterRepository->getCourses($userId));
    }

    public function getClasses(int $userId): array
    {
        return array_map(fn($i) => $i->toArray(), $this->masterRepository->getClasses($userId));
    }

    public function getSemesters(): array
    {
        return array_map(fn($i) => $i->toArray(), $this->masterRepository->getSemesters());
    }

    /**
     * Generate daftar tahun akademik dari 2026 hingga tahun berjalan.
     */
    public function getYears(): array
    {
        $start   = 2026;
        $current = (int) date('Y');
        $end     = max($start, $current);

        $years = [];
        for ($y = $start; $y <= $end; $y++) {
            $years[] = ['id' => $y, 'name' => (string) $y];
        }
        return $years;
    }

    // ------------------------------------------------------------------
    // Write — Departments
    // ------------------------------------------------------------------

    public function createDepartment(string $name, string $code, int $userId): array
    {
        return $this->masterRepository->createDepartment($name, $code, $userId)->toArray();
    }

    public function updateDepartment(int $id, string $name, string $code, int $userId): array
    {
        return $this->masterRepository->updateDepartment($id, $name, $code, $userId)->toArray();
    }

    public function deleteDepartment(int $id, int $userId): void
    {
        $this->masterRepository->deleteDepartment($id, $userId);
    }

    // ------------------------------------------------------------------
    // Write — Courses
    // ------------------------------------------------------------------

    public function createCourse(string $name, string $code, int $userId): array
    {
        return $this->masterRepository->createCourse($name, $code, $userId)->toArray();
    }

    public function updateCourse(int $id, string $name, string $code, int $userId): array
    {
        return $this->masterRepository->updateCourse($id, $name, $code, $userId)->toArray();
    }

    public function deleteCourse(int $id, int $userId): void
    {
        $this->masterRepository->deleteCourse($id, $userId);
    }

    // ------------------------------------------------------------------
    // Write — Classes
    // ------------------------------------------------------------------

    public function createClass(string $name, string $code, int $semesterId, int $year, int $userId): array
    {
        return $this->masterRepository->createClass($name, $code, $semesterId, $year, $userId)->toArray();
    }

    public function updateClass(int $id, string $name, string $code, int $semesterId, int $year, int $userId): array
    {
        return $this->masterRepository->updateClass($id, $name, $code, $semesterId, $year, $userId)->toArray();
    }

    public function deleteClass(int $id, int $userId): void
    {
        $this->masterRepository->deleteClass($id, $userId);
    }
}
