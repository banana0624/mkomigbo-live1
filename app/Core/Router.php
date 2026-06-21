<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $prefix = '';

    public function group(string $prefix, callable $callback): void
    {
        $previous = $this->prefix;
        $this->prefix .= '/' . trim($prefix, '/');

        $callback($this);

        $this->prefix = $previous;
    }

    public function get(string $path, callable $handler): void
    {
        $fullPath = trim($this->prefix . '/' . trim($path, '/'), '/');

        $this->routes['GET'][$fullPath] = $handler;
    }

    public function resolve(Request $request)
    {
        $method = $request->method;
        $path   = $request->path;

        return $this->routes[$method][$path] ?? null;
    }
}