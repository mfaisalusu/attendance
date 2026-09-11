<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

/**
 * Generic value object for flat master-data items loaded from JSON.
 * Extra fields (e.g. "code") are kept in $extra.
 */
class MasterItem
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly array  $extra = [],
    ) {}

    public static function fromArray(array $row): self
    {
        $id   = (int) $row['id'];
        $name = (string) $row['name'];
        unset($row['id'], $row['name']);

        // Cast known integer fields — PDO may return INT columns as strings
        // depending on the driver version or emulate-prepares setting.
        $intFields = ['department_id', 'semester_id', 'year', 'user_id'];
        foreach ($intFields as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = (int) $row[$field];
            }
        }

        return new self($id, $name, $row);
    }

    public function toArray(): array
    {
        return array_merge(['id' => $this->id, 'name' => $this->name], $this->extra);
    }
}
