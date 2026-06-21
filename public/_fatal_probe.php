<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "FATAL PROBE START\n";

/* 1. Test session */
session_start();
echo "SESSION OK\n";

/* 2. Test bootstrap */
require_once __DIR__ . '/_init.php';
echo "INIT OK\n";

/* 3. Test auth file */
$auth = __DIR__ . '/../lib/auth.php';

if (is_file($auth)) {
    require_once $auth;
    echo "AUTH LOADED\n";
} else {
    echo "AUTH MISSING\n";
}

/* 4. Test DB */
if (function_exists('db')) {
    try {
        $pdo = db();
        echo "DB OK\n";
    } catch (Throwable $e) {
        echo "DB ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "DB FUNCTION MISSING\n";
}

/* 5. Test role function */
if (function_exists('auth_require_role')) {
    echo "AUTH FUNCTION OK\n";
} else {
    echo "AUTH FUNCTION MISSING\n";
}

echo "FATAL PROBE END\n";