<?php
declare(strict_types=1);

require_once __DIR__ . '/_session_bootstrap.php';

/* -------------------------------------------------
   PATH RESOLUTION
------------------------------------------------- */

$init = dirname(__DIR__) . '/app/mkomigbo/private/assets/initialize.php';

if (!is_file($init)) {
    http_response_code(500);
    echo "BOOTSTRAP FAILED\n";
    echo "initialize.php not found\n";
    echo "Checked: " . $init . "\n";
    exit;
}

require_once $init;

/* -------------------------------------------------
   SAFETY
------------------------------------------------- */

if (!defined('PRIVATE_PATH')) {
    http_response_code(500);
    echo "BOOTSTRAP FAILED: PRIVATE_PATH not defined\n";
    exit;
}

/* -------------------------------------------------
   HELPERS
------------------------------------------------- */

if (!function_exists('mk_client_ip')) {
    function mk_client_ip(): string {
        return substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 64);
    }
}

if (!function_exists('mk_user_agent')) {
    function mk_user_agent(): string {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }
}