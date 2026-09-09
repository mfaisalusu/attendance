<?php

declare(strict_types=1);

namespace App\Presentation\Requests;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value      = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $value, string $rule, array $data): void
    {
        if ($rule === 'required') {
            if ($value === null || $value === '') {
                $this->addError($field, "{$field} wajib diisi.");
            }
            return;
        }

        // Skip other rules when value is empty and not required
        if ($value === null || $value === '') {
            return;
        }

        if ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, "{$field} harus berupa email yang valid.");
            }
            return;
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if (is_string($value) && mb_strlen($value) < $min) {
                $this->addError($field, "{$field} minimal {$min} karakter.");
            }
            return;
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (is_string($value) && mb_strlen($value) > $max) {
                $this->addError($field, "{$field} maksimal {$max} karakter.");
            }
            return;
        }

        if (str_starts_with($rule, 'same:')) {
            $otherField = substr($rule, 5);
            if ($value !== ($data[$otherField] ?? null)) {
                $this->addError($field, "{$field} harus sama dengan {$otherField}.");
            }
            return;
        }

        if (str_starts_with($rule, 'in:')) {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array($value, $allowed, true)) {
                $this->addError($field, "{$field} tidak valid.");
            }
            return;
        }

        if ($rule === 'integer') {
            if (!is_numeric($value)) {
                $this->addError($field, "{$field} harus berupa angka.");
            }
            return;
        }

        if ($rule === 'date') {
            $d = \DateTime::createFromFormat('Y-m-d', (string) $value);
            if (!$d || $d->format('Y-m-d') !== (string) $value) {
                $this->addError($field, "{$field} harus berupa tanggal (YYYY-MM-DD).");
            }
            return;
        }

        if ($rule === 'array') {
            if (!is_array($value)) {
                $this->addError($field, "{$field} harus berupa array.");
            }
            return;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
