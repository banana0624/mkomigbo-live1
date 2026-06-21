<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Canonical Authentication Bootstrap
|--------------------------------------------------------------------------
*/

if (defined('MK_LIB_AUTH_LOADED')) {
    return;
}

define('MK_LIB_AUTH_LOADED', true);

/*
|--------------------------------------------------------------------------
| Sessions
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Resolve DB Config
|--------------------------------------------------------------------------
*/

function mk_resolve_db_file(): string
{
    $candidates = [

        // ACTIVE RELEASE CONFIG
        '/home/mkomigbo/releases/2026-04-25-120559/app/mkomigbo/private/config/db.php',

        // relative fallback
        dirname(__DIR__) . '/app/mkomigbo/private/config/db.php',
    ];

    foreach ($candidates as $path) {

        if (
            is_string($path) &&
            $path !== '' &&
            file_exists($path) &&
            is_file($path)
        ) {
            return $path;
        }
    }

    echo '<pre>';
    echo "DB FILE SEARCH FAILED\n\n";

    foreach ($candidates as $candidate) {

        echo $candidate . "\n";

        echo file_exists($candidate)
            ? "EXISTS\n\n"
            : "MISSING\n\n";
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| LOAD DB FILE (THIS WAS MISSING)
|--------------------------------------------------------------------------
*/

$dbFile = mk_resolve_db_file();

require_once $dbFile;

if (!function_exists('db')) {
    throw new RuntimeException('DB FUNCTION STILL MISSING');
}

/*
|--------------------------------------------------------------------------
| Load Auth Bridge
|--------------------------------------------------------------------------
*/

$bridge = dirname(__DIR__) . '/public/staff/auth_bridge.php';

if (is_file($bridge)) {
    require_once $bridge;
}

/*
|--------------------------------------------------------------------------
| Fallback auth_require_role
|--------------------------------------------------------------------------
*/

if (!function_exists('auth_require_role')) {

    function auth_require_role(string $role): void
    {
        $ok = false;

        switch ($role) {

            case 'admin':

                $ok =
                    !empty($_SESSION['admin_user_id']) ||
                    !empty($_SESSION['admin']['id']);

                if (!$ok) {

                    header('Location: /admin/login.php');

                    exit;
                }

                return;

            case 'staff':
            default:

                $ok =
                    !empty($_SESSION['staff_user_id']) ||
                    !empty($_SESSION['staff']['id']);

                if (!$ok) {

                    $return =
                        $_SERVER['REQUEST_URI']
                        ?? '/staff/';

                    header(
                        'Location: /staff/login.php?return=' .
                        rawurlencode($return)
                    );

                    exit;
                }

                return;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Fallback auth_login
|--------------------------------------------------------------------------
*/

if (!function_exists('auth_login')) {

    function auth_login(
        int $userId,
        string $role = 'staff',
        array $extra = []
    ): bool {

        if ($userId <= 0) {
            return false;
        }

        session_regenerate_id(true);

        switch ($role) {

            case 'admin':

                $_SESSION['admin_user_id'] = $userId;

                $_SESSION['admin'] = array_merge([
                    'id' => $userId,
                    'role' => 'admin',
                ], $extra);

                return true;

            case 'staff':
            default:

                $_SESSION['staff_user_id'] = $userId;

                $_SESSION['staff'] = array_merge([
                    'id' => $userId,
                    'role' => 'staff',
                ], $extra);

                return true;
        }
    }
}