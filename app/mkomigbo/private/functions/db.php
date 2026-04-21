<?php
declare(strict_types=1);

/*
|------------------------------------------------------
| LOAD .env (simple, reliable)
|------------------------------------------------------
*/
$envPath = dirname(__DIR__, 2) . '/.env';

if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        $value = trim($value, "\"'");

        $_ENV[$key] = $value;
    }
}

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

    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $db   = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';
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
        die('DB ERROR: ' . $e->getMessage());
    }
}

/*
|------------------------------------------------------
| COMPATIBILITY WRAPPER (CRITICAL)
|------------------------------------------------------
*/
function mk_db(): PDO
{
    return db();
}