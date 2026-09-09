<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\MasterRepositoryInterface;
use App\Domain\ValueObjects\MasterItem;
use RuntimeException;

class JsonMasterRepository implements MasterRepositoryInterface
{
    private string $basePath;

    /** @var array<string, MasterItem[]> */
    private array $cache = [];

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? (BASE_PATH . '/storage/master');
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    public function getDepartments(): array
    {
        return $this->load('departments');
    }

    public function getCourses(): array
    {
        return $this->load('courses');
    }

    public function getClasses(): array
    {
        return $this->load('classes');
    }

    public function getSemesters(): array
    {
        return $this->load('semesters');
    }

    public function findDepartmentById(int $id): ?MasterItem
    {
        return $this->findById('departments', $id);
    }

    public function findCourseById(int $id): ?MasterItem
    {
        return $this->findById('courses', $id);
    }

    public function findClassById(int $id): ?MasterItem
    {
        return $this->findById('classes', $id);
    }

    public function findSemesterById(int $id): ?MasterItem
    {
        return $this->findById('semesters', $id);
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * @return MasterItem[]
     */
    private function load(string $name): array
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $file = $this->basePath . "/{$name}.json";

        if (!file_exists($file)) {
            throw new RuntimeException("Master data file not found: {$name}.json");
        }

        $raw  = file_get_contents($file);
        $rows = json_decode($raw, true);

        if (!is_array($rows)) {
            throw new RuntimeException("Invalid master data format in: {$name}.json");
        }

        $this->cache[$name] = array_map(
            fn(array $row) => MasterItem::fromArray($row),
            $rows
        );

        return $this->cache[$name];
    }

    private function findById(string $name, int $id): ?MasterItem
    {
        foreach ($this->load($name) as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }
        return null;
    }
}
