<?php

declare(strict_types=1);

final class Route
{
    private static array $routes = [];
    private static array $groupStack = [];

    public static function group(string $prefix, array $middleware, callable $callback): void
    {
        self::$groupStack[] = [
            'prefix' => rtrim($prefix, '/'),
            'middleware' => $middleware
        ];

        $callback();

        array_pop(self::$groupStack);
    }

    public static function get(string $path, string $controller, string $method, array $middleware = []): void
    {
        $group = end(self::$groupStack) ?: ['prefix' => '', 'middleware' => []];

        $fullPath = rtrim($group['prefix'] . '/' . ltrim($path, '/'), '/');
        $fullPath = $fullPath === '' ? '/' : $fullPath;

        self::$routes[] = [
            'path' => $fullPath,
            'controller' => $controller,
            'method' => $method,
            'middleware' => array_merge($group['middleware'], $middleware),
        ];
    }

    public static function all(): array
    {
        return self::$routes;
    }
}