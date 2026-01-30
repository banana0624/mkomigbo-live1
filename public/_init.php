<?php
declare(strict_types=1);

/**
 * /public/_init.php
 * Public bootstrap (single source of truth).
 *
 * Responsibilities:
 * - Locate and load: /app/mkomigbo/private/assets/initialize.php
 * - Fail safely (no HTML leakage, no headers already sent issues)
 * - Provide APP_ROOT consistently for public controllers
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!headers_sent()) {
  header('Content-Type: text/html; charset=utf-8');
}

/* ---------------------------------------------------------
   Locate initialize.php (robust + deterministic)
--------------------------------------------------------- */

/**
 * Find initialize.php by scanning upward from a start dir.
 */
if (!function_exists('mk_find_init')) {
  function mk_find_init(string $startDir, int $maxDepth = 14): ?string {
    $dir = $startDir;

    for ($i = 0; $i <= $maxDepth; $i++) {
      $candidates = [
        $dir . '/app/mkomigbo/private/assets/initialize.php',      // your current layout
        $dir . '/private/assets/initialize.php',                  // legacy layout (fallback)
      ];

      foreach ($candidates as $p) {
        if (is_string($p) && $p !== '' && is_file($p)) {
          return $p;
        }
      }

      $parent = dirname($dir);
      if ($parent === $dir) break;
      $dir = $parent;
    }

    return null;
  }
}

$__mk_init = null;

/* Prefer the known absolute location (fast path) */
$__known = '/home/mkomigbo/public_html/app/mkomigbo/private/assets/initialize.php';
if (is_file($__known)) {
  $__mk_init = $__known;
} else {
  /* Scan upward from /public */
  $__mk_init = mk_find_init(__DIR__, 14);
}

if (!is_string($__mk_init) || $__mk_init === '' || !is_file($__mk_init)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Bootstrap failed: initialize.php not found.\n";
  echo "Tried:\n";
  echo " - {$__known}\n";
  echo " - upward scan from: " . __DIR__ . "\n";
  exit;
}

require_once $__mk_init;

/* Ensure APP_ROOT is defined (initialize.php should define it, but keep safe) */
if (!defined('APP_ROOT')) {
  // If initialize.php didn't define APP_ROOT, infer it from the init path:
  // .../app/mkomigbo/private/assets/initialize.php -> .../app/mkomigbo
  define('APP_ROOT', dirname(dirname(dirname($__mk_init))));
}

/* Optional: load public bootstrap helpers if present */
$__pub_bootstrap = __DIR__ . '/_bootstrap.php';
if (is_file($__pub_bootstrap)) {
  require_once $__pub_bootstrap;
}

/* Sessions are usually started inside auth flows; do not force session here */
