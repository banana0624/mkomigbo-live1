<?php
declare(strict_types=1);

if (defined('MK_APP_INITIALIZED')) {
    return;
}
define('MK_APP_INITIALIZED', true);

/* PATHS */
$appRoot = dirname(__DIR__, 2);

define('APP_ROOT', realpath($appRoot) ?: $appRoot);
define('PRIVATE_PATH', APP_ROOT . '/private');
define('SITE_ROOT', dirname(APP_ROOT, 2));
define('PUBLIC_ROOT', SITE_ROOT . '/public');

/* ENV */
$envFile = APP_ROOT . '/.env';

if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
}

/* DEBUG */
define('APP_ENV', 'dev');
define('APP_DEBUG', true);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/* DB */
function db(): PDO {
    static $pdo;

    if ($pdo instanceof PDO) return $pdo;

    $pdo = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4",
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    return $pdo;
}

/* HELPERS */
function url_for(string $path): string {
    return '/' . ltrim($path, '/');
}