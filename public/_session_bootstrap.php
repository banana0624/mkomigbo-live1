<?php
declare(strict_types=1);

/**
 * Session bootstrap (shared by public + staff)
 * - Forces a writable save_path (avoids host permission oddities)
 * - Sets secure cookie flags correctly behind HTTPS/proxy
 * - Ensures one consistent session across endpoints
 */

if (!function_exists('mk__session_start')) {
    function mk__session_start(): void
    {
        $https =
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

        $savePath = realpath(__DIR__ . '/../private/sessions');
        if ($savePath === false) {
            $savePath = __DIR__ . '/../private/sessions';
        }

        @ini_set('session.save_path', $savePath);
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.cookie_httponly', '1');
        @ini_set('session.cookie_secure', $https ? '1' : '0');
        @ini_set('session.cookie_samesite', 'Lax');
        @ini_set('session.cookie_path', '/');

        @session_name('MKSESSID');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }
}

mk__session_start();