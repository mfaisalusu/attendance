<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\ValueObjects\MasterItem;

interface MasterRepositoryInterface
{
    /** @return MasterItem[] */
    public function getDepartments(): array;

    /** @return MasterItem[] */
    public function getCourses(): array;

    /** @return MasterItem[] */
    public function getClasses(): array;

    /** @return MasterItem[] */
    public function getSemesters(): array;

    public function findDepartmentById(int $id): ?MasterItem;

    public function findCourseById(int $id): ?MasterItem;

    public function findClassById(int $id): ?MasterItem;

    public function findSemesterById(int $id): ?MasterItem;
}
