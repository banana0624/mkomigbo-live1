<?php
declare(strict_types=1);

/**
 * /public/_bootstrap.php
 * Public bootstrap helpers (hardened).
 *
 * Guarantees:
 * - never calls is_file() with null
 * - safe if included multiple times
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (defined('MK_PUBLIC_BOOTSTRAP_LOADED')) { return; }
define('MK_PUBLIC_BOOTSTRAP_LOADED', true);

/* Ensure APP_ROOT/PUBLIC_ROOT */
if (!defined('PUBLIC_ROOT')) define('PUBLIC_ROOT', __DIR__);

if (!defined('APP_ROOT')) {
  $root = dirname(__DIR__);                 // /home/mkomigbo/public_html
  $cand = $root . '/app/mkomigbo';
  if (is_dir($cand)) define('APP_ROOT', $cand);
}

/* Minimal helpers */
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('u')) {
  function u(string $v): string { return rawurlencode($v); }
}
if (!function_exists('url_for')) {
  function url_for(string $path): string {
    $path = ($path === '') ? '/' : $path;
    if ($path[0] !== '/') $path = '/' . $path;
    $base = defined('WWW_ROOT') ? (string)WWW_ROOT : '';
    return $base . $path;
  }
}

/* Optional app helpers (defensive) */
$helpers = [];
if (defined('APP_ROOT')) {
  $helpers = [
    APP_ROOT . '/private/functions/functions.php',
    APP_ROOT . '/private/functions/helpers.php',
    APP_ROOT . '/private/functions/validation.php',
  ];
}

foreach ($helpers as $f) {
  // THIS is the key hardening:
  if (is_string($f) && $f !== '' && is_file($f)) {
    require_once $f;
  }
}
