<?php
declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED') && APP_BOOTSTRAPPED === true) {
    return;
}
define('APP_BOOTSTRAPPED', true);

/**
 * FIX: use actual web root (NOT /home/mkomigbo)
 */
// // DISABLED_APP_ROOT (DISABLED_AUTO_FIX), __DIR__ . '/app/mkomigbo');

/**
 * CORRECT INITIALIZER PATH
 */
$init = APP_ROOT . '/private/assets/initialize.php';

if (!is_file($init)) {
    http_response_code(500);
    exit("BOOTSTRAP ERROR: initialize.php missing at {$init}");
}

require_once $init;