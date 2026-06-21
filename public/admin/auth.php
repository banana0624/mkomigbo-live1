<?php
declare(strict_types=1);

/**
 * /public/admin/auth.php
 *
 * Canonical admin auth layer.
 */

if (defined('MK_ADMIN_AUTH_LOADED')) {
    return;
}

define('MK_ADMIN_AUTH_LOADED', true);

/* ---------------------------------------------------------
   Session
--------------------------------------------------------- */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ---------------------------------------------------------
   Require admin login
--------------------------------------------------------- */

if (!function_exists('auth_require_role')) {

    function auth_require_role(string $role): void
    {
        $current =
            $_SESSION['role']
            ?? $_SESSION['staff_role']
            ?? '';

        if ($current !== $role) {

            header('Location: /admin/login.php');

            exit;
        }
    }
}

/* ---------------------------------------------------------
   Login helper
--------------------------------------------------------- */

if (!function_exists('auth_login')) {

    function auth_login(
        int $userId,
        string $role,
        array $extra = []
    ): void {

        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;

        foreach ($extra as $k => $v) {
            $_SESSION[$k] = $v;
        }
    }
}

/* ---------------------------------------------------------
   Logout helper
--------------------------------------------------------- */

if (!function_exists('auth_logout')) {

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
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}