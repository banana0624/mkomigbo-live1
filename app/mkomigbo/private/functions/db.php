<?php
declare(strict_types=1);

/*
|------------------------------------------------------
| LOAD .env (robust multi-location search)
|------------------------------------------------------
*/
(static function(): void {

    // Already loaded — skip
    if (!empty($_ENV['DB_NAME'])) {
        return;
    }

    // Candidate locations, most specific first
    $candidates = [
        // Correct location: app/mkomigbo/.env
        dirname(__DIR__, 1) . '/.env',
        // One level up: app/.env (fallback)
        dirname(__DIR__, 2) . '/.env',
        // Release root: releases/2026-.../.env
        dirname(__DIR__, 3) . '/.env',
    ];

    foreach ($candidates as $envPath) {
        if (!is_file($envPath)) {
            continue;
        }

        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            continue;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim(trim($value), "\"'");
            if ($key !== '' && !isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }

        // Found and loaded — stop searching
        break;
    }
})();

/*
|------------------------------------------------------
| DATABASE CONNECTION (PRIMARY)
|------------------------------------------------------
*/
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host    = $_ENV['DB_HOST'] ?? 'localhost';
    $db      = $_ENV['DB_NAME'] ?? '';
    $user    = $_ENV['DB_USER'] ?? '';
    $pass    = $_ENV['DB_PASS'] ?? '';
    $charset = 'utf8mb4';

    if ($db === '' || $user === '') {
        die('DB CONFIG ERROR: Missing DB_NAME or DB_USER in .env');
    }

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;

    } catch (Throwable $e) {
        die('DATABASE CONNECTION FAILED: ' . $e->getMessage());
    }
}

/*
|------------------------------------------------------
| COMPATIBILITY WRAPPERS
|------------------------------------------------------
*/
function mk_db(): PDO
{
    return db();
}

function staff_pdo(): PDO
{
    return db();
}