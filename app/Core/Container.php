<?php

namespace App\Core;

class Container
{
    private static array $bindings = [];

    public static function bind(string $key, callable $resolver): void
    {
        self::$bindings[$key] = $resolver;
    }

    public static function make(string $key)
    {
        return isset(self::$bindings[$key])
            ? (self::$bindings[$key])()
            : null;
    }
}