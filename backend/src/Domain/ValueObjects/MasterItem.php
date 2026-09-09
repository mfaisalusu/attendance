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

        return new self($id, $name, $row);
    }

    public function toArray(): array
    {
        return array_merge(['id' => $this->id, 'name' => $this->name], $this->extra);
    }
}
