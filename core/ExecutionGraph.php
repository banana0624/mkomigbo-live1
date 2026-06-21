<?php
declare(strict_types=1);

final class ExecutionGraph
{
    private static array $nodes = [];
    private static array $edges = [];
    private static array $stack = [];

    public static function init(): void
    {
        self::record('__kernel__', 'init');
    }

    public static function record(string $file, string $type = 'include'): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['file'] ?? '__unknown__';

        self::$nodes[$file] = true;
        self::$nodes[$caller] = true;

        self::$edges[] = [
            'from' => $caller,
            'to'   => $file,
            'type' => $type,
            'time' => microtime(true)
        ];
    }

    public static function dump(): array
    {
        return [
            'nodes' => array_keys(self::$nodes),
            'edges' => self::$edges
        ];
    }

    public static function exportJson(): string
    {
        return json_encode(self::dump(), JSON_PRETTY_PRINT);
    }
}