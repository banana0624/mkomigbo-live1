<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| DB BOOTSTRAP (DEPLOYMENT SAFE)
|--------------------------------------------------------------------------
*/

function resolve_db_path(): string
{
    $candidates = [

        // ✅ HARDCODED ACTIVE RELEASE (MOST RELIABLE IN YOUR SETUP)
        __DIR__ . '/../../app/mkomigbo/private/config/db.php',

        // fallback based on release root
        dirname(__DIR__, 3) . '/app/mkomigbo/private/config/db.php',

        // fallback absolute known release (YOUR CONFIRMED PATH)
        '/releases/2026-04-25-120559/app/mkomigbo/private/config/db.php',
    ];

    foreach ($candidates as $path) {

        if (is_string($path) && is_file($path)) {
            return realpath($path);
        }
    }

    http_response_code(500);
    exit('NO DB FILE FOUND');
}

/*
|--------------------------------------------------------------------------
| Validate DB Bootstrap
|--------------------------------------------------------------------------
*/

if (!function_exists('db')) {

    http_response_code(500);

    exit('DB FUNCTION STILL MISSING');
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

function auth_attempt_login(string $email, string $password): bool
{
    if (!function_exists('db')) {

        exit('DB FUNCTION NOT FOUND');
    }

    try {

        $pdo = db();

    } catch (Throwable $e) {

        exit('DB CONNECTION FAILED: ' . $e->getMessage());
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM staff_users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        exit('USER NOT FOUND');
    }

    if (
        array_key_exists('is_active', $user) &&
        (int)$user['is_active'] !== 1
    ) {

        exit('USER DISABLED');
    }

    $hash = $user['password_hash'] ?? '';

    if (!$hash) {

        exit('PASSWORD HASH EMPTY');
    }

    if (!password_verify($password, $hash)) {

        exit('PASSWORD VERIFY FAILED');
    }

    session_regenerate_id(true);

    $role = $user['role'] ?? 'staff';

    $_SESSION['staff_user_id'] = (int)$user['id'];

    $_SESSION['staff_role'] = $role;

    $_SESSION['staff_email'] = $user['email'];

    $_SESSION['staff'] = [
        'id'    => (int)$user['id'],
        'email' => $user['email'],
        'role'  => $role,
    ];

    if ($role === 'admin') {

        $_SESSION['admin_user_id'] = (int)$user['id'];

        $_SESSION['admin'] = [
            'id'    => (int)$user['id'],
            'email' => $user['email'],
            'role'  => $role,
        ];
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| Role Protection
|--------------------------------------------------------------------------
*/

function auth_require_role(string $role = 'staff'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {

        session_start();
    }

    if (empty($_SESSION['staff_user_id'])) {

        header('Location: /staff/login.php');

        exit;
    }

    $currentRole = $_SESSION['staff_role'] ?? 'staff';

    if ($currentRole !== $role) {

        http_response_code(403);

        exit('Forbidden');
    }
}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

function auth_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool)$params['secure'],
            (bool)$params['httponly']
        );
    }

    session_destroy();
}