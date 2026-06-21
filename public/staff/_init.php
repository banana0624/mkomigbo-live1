<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| STAFF INIT - SAFE MODE
|--------------------------------------------------------------------------
| No recursion
| No DB dependency
| No external auth dependency required to load page
|--------------------------------------------------------------------------
*/

if (defined('MK_STAFF_INIT_LOADED')) {
    return;
}

define('MK_STAFF_INIT_LOADED', true);

/* ---------------------------------------------------------
| Session SAFE START
--------------------------------------------------------- */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/* ---------------------------------------------------------
| SAFE AUTH GUARD (NO external function dependency)
--------------------------------------------------------- */

if (!function_exists('auth_require_role')) {

    function auth_require_role(string $role): void
    {
        if ($role !== 'staff') {
            return;
        }

        $loggedIn =
            !empty($_SESSION['staff_user_id']) ||
            !empty($_SESSION['staff_user']) ||
            !empty($_SESSION['staff_id']);

        if (!$loggedIn) {

            header('Location: /staff/login.php', true, 302);
            exit;
        }
    }
}

/* ---------------------------------------------------------
| Minimal helpers
--------------------------------------------------------- */

if (!function_exists('h')) {

    function h(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

/* ---------------------------------------------------------
| IMPORTANT: DO NOT AUTO-CALL auth_require_role HERE
| Only pages that require protection should call it
--------------------------------------------------------- */