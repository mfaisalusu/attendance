<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\MasterRepositoryInterface;
use App\Domain\ValueObjects\MasterItem;
use RuntimeException;

class JsonMasterRepository implements MasterRepositoryInterface
{
    private string $basePath;

    /** @var array<string, array> Raw rows cache (all users combined) */
    private array $rawCache = [];

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? (BASE_PATH . '/storage/master');
    }

    // ------------------------------------------------------------------
    // Read
    // ------------------------------------------------------------------

    public function getDepartments(int $userId): array
    {
        return $this->loadForUser('departments', $userId);
    }

    public function getCourses(int $userId): array
    {
        return $this->loadForUser('courses', $userId);
    }

    public function getClasses(int $userId): array
    {
        return $this->loadForUser('classes', $userId);
    }

    public function getSemesters(): array
    {
        // Semesters bersifat global — tidak difilter per user
        return array_map(
            fn(array $row) => MasterItem::fromArray($row),
            $this->loadRaw('semesters')
        );
    }

    public function findDepartmentById(int $id, int $userId): ?MasterItem
    {
        return $this->findByIdForUser('departments', $id, $userId);
    }

    public function findCourseById(int $id, int $userId): ?MasterItem
    {
        return $this->findByIdForUser('courses', $id, $userId);
    }

    public function findClassById(int $id, int $userId): ?MasterItem
    {
        return $this->findByIdForUser('classes', $id, $userId);
    }

    public function findSemesterById(int $id): ?MasterItem
    {
        foreach ($this->getSemesters() as $item) {
            if ($item->id === $id) return $item;
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Write — Departments
    // ------------------------------------------------------------------

    public function createDepartment(string $name, string $code, int $userId): MasterItem
    {
        return $this->createItem('departments', $name, $code, [], $userId);
    }

    public function updateDepartment(int $id, string $name, string $code, int $userId): MasterItem
    {
        return $this->updateItem('departments', $id, $name, $code, [], $userId);
    }

    public function deleteDepartment(int $id, int $userId): void
    {
        $this->deleteItem('departments', $id, $userId);
    }

    // ------------------------------------------------------------------
    // Write — Courses
    // ------------------------------------------------------------------

    public function createCourse(string $name, string $code, int $userId): MasterItem
    {
        return $this->createItem('courses', $name, $code, [], $userId);
    }

    public function updateCourse(int $id, string $name, string $code, int $userId): MasterItem
    {
        return $this->updateItem('courses', $id, $name, $code, [], $userId);
    }

    public function deleteCourse(int $id, int $userId): void
    {
        $this->deleteItem('courses', $id, $userId);
    }

    // ------------------------------------------------------------------
    // Write — Classes
    // ------------------------------------------------------------------

    public function createClass(string $name, string $code, int $semesterId, int $year, int $userId): MasterItem
    {
        return $this->createItem('classes', $name, $code, [
            'semester_id' => $semesterId,
            'year'        => $year,
        ], $userId);
    }

    public function updateClass(int $id, string $name, string $code, int $semesterId, int $year, int $userId): MasterItem
    {
        return $this->updateItem('classes', $id, $name, $code, [
            'semester_id' => $semesterId,
            'year'        => $year,
        ], $userId);
    }

    public function deleteClass(int $id, int $userId): void
    {
        $this->deleteItem('classes', $id, $userId);
    }

    // ------------------------------------------------------------------
    // Generic write helpers
    // ------------------------------------------------------------------

    private function createItem(
        string $name,
        string $itemName,
        string $code,
        array  $extra,
        int    $userId
    ): MasterItem {
        $rows  = $this->loadRaw($name);
        $newId = empty($rows) ? 1 : (max(array_column($rows, 'id')) + 1);

        $newRow = array_merge(
            ['id' => $newId, 'user_id' => $userId, 'code' => strtoupper(trim($code)), 'name' => trim($itemName)],
            $extra
        );
        $rows[] = $newRow;

        $this->save($name, $rows);

        return MasterItem::fromArray($newRow);
    }

    private function updateItem(
        string $name,
        int    $id,
        string $itemName,
        string $code,
        array  $extra,
        int    $userId
    ): MasterItem {
        $rows    = $this->loadRaw($name);
        $found   = false;
        $updated = null;

        foreach ($rows as &$row) {
            if ((int) $row['id'] === $id) {
                // Hanya pemilik data yang boleh mengubah
                if ((int) ($row['user_id'] ?? 0) !== $userId) {
                    throw new RuntimeException('Anda tidak memiliki akses ke data ini.');
                }
                $row['name'] = trim($itemName);
                $row['code'] = strtoupper(trim($code));
                foreach ($extra as $k => $v) {
                    $row[$k] = $v;
                }
                $updated = $row;
                $found   = true;
                break;
            }
        }
        unset($row);

        if (!$found) {
            throw new RuntimeException("Data tidak ditemukan.");
        }

        $this->save($name, $rows);

        return MasterItem::fromArray($updated);
    }

    private function deleteItem(string $name, int $id, int $userId): void
    {
        $rows = $this->loadRaw($name);

        $target = null;
        foreach ($rows as $row) {
            if ((int) $row['id'] === $id) {
                $target = $row;
                break;
            }
        }

        if ($target === null) {
            throw new RuntimeException("Data tidak ditemukan.");
        }

        // Hanya pemilik data yang boleh menghapus
        if ((int) ($target['user_id'] ?? 0) !== $userId) {
            throw new RuntimeException('Anda tidak memiliki akses ke data ini.');
        }

        $filtered = array_values(array_filter($rows, fn($r) => (int) $r['id'] !== $id));
        $this->save($name, $filtered);
    }

    // ------------------------------------------------------------------
    // File I/O helpers
    // ------------------------------------------------------------------

    /** Load all rows for a specific user as MasterItem[] */
    private function loadForUser(string $name, int $userId): array
    {
        $rows = array_filter(
            $this->loadRaw($name),
            fn(array $row) => (int) ($row['user_id'] ?? 0) === $userId
        );

        return array_map(
            fn(array $row) => MasterItem::fromArray($row),
            array_values($rows)
        );
    }

    private function findByIdForUser(string $name, int $id, int $userId): ?MasterItem
    {
        foreach ($this->loadForUser($name, $userId) as $item) {
            if ($item->id === $id) return $item;
        }
        return null;
    }

    private function loadRaw(string $name): array
    {
        if (isset($this->rawCache[$name])) {
            return $this->rawCache[$name];
        }

        $file = $this->basePath . "/{$name}.json";

        if (!file_exists($file)) {
            return [];
        }

        $raw  = file_get_contents($file);
        $rows = json_decode($raw, true);

        if (!is_array($rows)) {
            throw new RuntimeException("Invalid master data format in: {$name}.json");
        }

        $this->rawCache[$name] = $rows;

        return $rows;
    }

    private function save(string $name, array $rows): void
    {
        $file = $this->basePath . "/{$name}.json";

        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }

        $result = file_put_contents(
            $file,
            json_encode(
                array_values($rows),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );

        if ($result === false) {
            throw new RuntimeException("Gagal menyimpan data ke file: {$name}.json");
        }

        // Invalidate raw cache setelah write
        unset($this->rawCache[$name]);
    }
}
