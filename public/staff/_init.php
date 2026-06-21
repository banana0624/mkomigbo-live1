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
| Define APP_ROOT and PRIVATE_PATH (required by tools, etc.)
| public/staff/_init.php is 2 levels below the release root:
|   /home/mkomigbo/releases/2026-04-25-120559/public/staff/_init.php
|   dirname(__DIR__, 2) = /home/mkomigbo/releases/2026-04-25-120559
--------------------------------------------------------- */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

if (!defined('PRIVATE_PATH')) {
    define('PRIVATE_PATH', APP_ROOT . '/app/mkomigbo/private');
}

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

// Load DB functions (required for db() and staff_pdo())
$_db_path = APP_ROOT . '/app/mkomigbo/private/functions/db.php';
if (is_file($_db_path)) {
    require_once $_db_path;
}
unset($_db_path);

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