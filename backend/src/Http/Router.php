<?php

declare(strict_types=1);

namespace DndPortal\Http;

final class Router {
    /** @var array<int,array{method:string,path:string,handler:callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void {
        $this->routes[] = ['method' => 'GET', 'path' => $path, 'handler' => $handler];
    }

    public function post(string $path, callable $handler): void {
        $this->routes[] = ['method' => 'POST', 'path' => $path, 'handler' => $handler];
    }

    public function patch(string $path, callable $handler): void {
        $this->routes[] = ['method' => 'PATCH', 'path' => $path, 'handler' => $handler];
    }

    public function put(string $path, callable $handler): void {
        $this->routes[] = ['method' => 'PUT', 'path' => $path, 'handler' => $handler];
    }

    public function delete(string $path, callable $handler): void {
        $this->routes[] = ['method' => 'DELETE', 'path' => $path, 'handler' => $handler];
    }

    public function options(string $path, callable $handler): void {
        $this->routes[] = ['method' => 'OPTIONS', 'path' => $path, 'handler' => $handler];
    }

    public function dispatch(Request $request): never {
        foreach ($this->routes as $route) {
            if (
                $route['method'] === $request->method() &&
                $this->matches($route['path'], $request->path())
            ) {
                $route['handler']($request);
                exit();
            }
        }
        Response::error('not_found', 'Endpoint not found.', 404);
    }

    private function matches(string $route, string $path): bool {
        $a = explode('/', trim($route, '/'));
        $b = explode('/', trim($path, '/'));
        if (count($a) !== count($b)) {
            return false;
        }
        foreach ($a as $i => $part) {
            if ($part !== $b[$i] && !(str_starts_with($part, '{') && str_ends_with($part, '}'))) {
                return false;
            }
        }
        return true;
    }
}
