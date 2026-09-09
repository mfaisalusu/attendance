<?php

declare(strict_types=1);

namespace App\Presentation\Router;

use App\Presentation\Requests\Request;
use App\Presentation\Responses\JsonResponse;

class Router
{
    /** @var array<string, array<string, array{callable, array<callable>}>> */
    private array $routes = [];

    /** @var callable[] */
    private array $globalMiddlewares = [];

    // ------------------------------------------------------------------
    // Registration
    // ------------------------------------------------------------------

    public function get(string $path, callable $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, callable $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, callable $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function addMiddleware(callable $middleware): void
    {
        $this->globalMiddlewares[] = $middleware;
    }

    // ------------------------------------------------------------------
    // Dispatch
    // ------------------------------------------------------------------

    public function dispatch(string $method, string $uri): void
    {
        $method  = strtoupper($method);
        $uri     = '/' . trim($uri, '/');

        if (!isset($this->routes[$method])) {
            JsonResponse::notFound('Endpoint tidak ditemukan.');
        }

        foreach ($this->routes[$method] as $pattern => [$handler, $middlewares]) {
            $params = [];

            if ($this->match($pattern, $uri, $params)) {
                $request = new Request();
                $request->setParams($params);

                // Run route-specific middlewares
                foreach ($middlewares as $mw) {
                    $mw($request);
                }

                $handler($request);
                return;
            }
        }

        JsonResponse::notFound('Endpoint tidak ditemukan.');
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function addRoute(string $method, string $path, callable $handler, array $middlewares): void
    {
        $pattern = $this->toPattern($path);
        $this->routes[$method][$pattern] = [$handler, $middlewares];
    }

    /**
     * Convert /api/students/{id} → regex pattern.
     */
    private function toPattern(string $path): string
    {
        $path    = '/' . trim($path, '/');
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function match(string $pattern, string $uri, array &$params): bool
    {
        if (preg_match($pattern, $uri, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }
        return false;
    }
}
