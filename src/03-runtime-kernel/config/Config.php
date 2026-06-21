<?php

class Config
{
    private static array $data = [];

    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            die('Missing .env file');
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            self::$data[trim($key)] = trim($value);
        }
    }

    public static function get(string $key, $default = null)
    {
        return self::$data[$key] ?? $default;
    }
}