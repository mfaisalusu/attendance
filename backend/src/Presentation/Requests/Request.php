<?php

declare(strict_types=1);

namespace App\Presentation\Requests;

class Request
{
    private array $body   = [];
    private array $query  = [];
    private array $params = [];
    private array $files  = [];

    public function __construct()
    {
        $this->query = $_GET ?? [];
        $this->files = $_FILES ?? [];

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $this->body = json_decode($raw ?: '', true) ?? [];
        } else {
            $this->body = $_POST ?? [];
        }
    }

    // ------------------------------------------------------------------
    // Body (POST / JSON payload)
    // ------------------------------------------------------------------

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->body;
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->body, array_flip($keys));
    }

    // ------------------------------------------------------------------
    // Query string (?key=value)
    // ------------------------------------------------------------------

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function queryInt(string $key, int $default = 0): int
    {
        $val = $this->query[$key] ?? null;
        return ($val !== null && is_numeric($val)) ? (int) $val : $default;
    }

    // ------------------------------------------------------------------
    // Route params (injected by Router)
    // ------------------------------------------------------------------

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    // ------------------------------------------------------------------
    // Headers
    // ------------------------------------------------------------------

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public function bearerToken(): ?string
    {
        // 1. Standard Authorization header
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }

        // 2. Query string fallback — used for direct file links (download/view/content)
        //    where setting a request header is not possible (e.g. <a href>, fetch without proxy)
        $queryToken = $_GET['token'] ?? null;
        if ($queryToken !== null && $queryToken !== '' && $queryToken !== 'null') {
            return $queryToken;
        }

        return null;
    }

    // ------------------------------------------------------------------
    // File uploads
    // ------------------------------------------------------------------

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function allFiles(): array
    {
        return $this->files;
    }
}
