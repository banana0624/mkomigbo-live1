<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap Runtime
|--------------------------------------------------------------------------
|
| Kernel-safe bootstrap layer.
| Avoid redefining ownership already established by _init.php.
|
*/

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

/*
|--------------------------------------------------------------------------
| Environment bootstrap
|--------------------------------------------------------------------------
*/

$envFile = APP_ROOT . '/.env.php';

if (is_file($envFile)) {
    require_once $envFile;
}

/*
|--------------------------------------------------------------------------
| Canonical DB authority
|--------------------------------------------------------------------------
*/

require_once APP_ROOT . '/app/mkomigbo/private/functions/db.php';

/*
|--------------------------------------------------------------------------
| Path constants (PRIVATE_PATH, SHARED_PATH, FUNCTIONS_PATH, etc.)
|--------------------------------------------------------------------------
*/
if (!defined('PRIVATE_PATH')) {
    require_once APP_ROOT . '/app/mkomigbo/private/functions/contain.php';
}

