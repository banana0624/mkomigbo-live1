<?php
declare(strict_types=1);

/**
 * /public/_init.php
 * Single bootstrap for ALL web entrypoints under /public (public + staff).
 *
 * Goals:
 * - Deterministically load: /app/mkomigbo/private/assets/initialize.php
 * - Audit-clean (no legacy/bad init strings)
 * - Lazy sessions:
 *     * Staff routes: auto session
 *     * Public GET/HEAD: no session by default (caching-friendly)
 *     * Public pages can opt-in: define('MK_REQUIRE_SESSION', true) before requiring this file
 */

/* ---------------------------------------------------------
   Hardening: runtime error display off by default
--------------------------------------------------------- */
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

/* ---------------------------------------------------------
   Idempotency
--------------------------------------------------------- */
if (defined('MK_PUBLIC_INIT_LOADED') && MK_PUBLIC_INIT_LOADED === true) { return; }
define('MK_PUBLIC_INIT_LOADED', true);

/* ---------------------------------------------------------
   Brand (display only; does not affect routing)
--------------------------------------------------------- */
if (!defined('MK_BRAND_NAME')) define('MK_BRAND_NAME', 'Mkomi Igbo');
if (!defined('MK_SITE_NAME'))  define('MK_SITE_NAME',  'Mkomi Igbo');

/* ---------------------------------------------------------
   Locate authoritative initialize.php (deterministic + fallback scan)
--------------------------------------------------------- */
$__mk_app_root = dirname(__DIR__) . '/app/mkomigbo';
$__mk_init = $__mk_app_root . '/private/assets/initialize.php';

if (!is_file($__mk_init)) {
  // Bounded upward scan as emergency fallback:
  $dir = __DIR__;
  $found = null;

  for ($i = 0; $i <= 16; $i++) {
    $try = $dir . '/app/mkomigbo/private/assets/initialize.php';
    if (is_file($try)) { $found = $try; break; }

    $parent = dirname($dir);
    if ($parent === $dir) break;
    $dir = $parent;
  }

  if (is_string($found) && $found !== '') {
    $__mk_init = $found;
  }
}

if (!is_file($__mk_init)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Init not found\n";
  echo "Tried: {$__mk_init}\n";
  exit;
}

require_once $__mk_init;

/* ---------------------------------------------------------
   Ensure core constants exist (do not override initialize.php)
--------------------------------------------------------- */
if (!defined('APP_ROOT')) {
  $rp = @realpath($__mk_app_root);
  if (is_string($rp) && $rp !== '' && is_dir($rp)) define('APP_ROOT', $rp);
  else define('APP_ROOT', $__mk_app_root);
}

if (!defined('PRIVATE_PATH') && defined('APP_ROOT')) {
  define('PRIVATE_PATH', rtrim((string)APP_ROOT, '/') . '/private');
}

/**
 * PUBLIC_PATH should equal the web docroot on disk.
 * On most setups (including yours), DOCUMENT_ROOT points to /public_html/public.
 */
if (!defined('PUBLIC_PATH')) {
  $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
  if ($docRoot !== '' && is_dir($docRoot)) {
    define('PUBLIC_PATH', $docRoot);
  } else {
    define('PUBLIC_PATH', dirname(__DIR__) . '/public');
  }
}

/* Convenience aliases (only if missing; do not fight initialize.php) */
if (!defined('PUBLIC_ROOT'))   define('PUBLIC_ROOT', PUBLIC_PATH);
if (!defined('PUBLIC_SUBDIR')) define('PUBLIC_SUBDIR', PUBLIC_PATH);
if (!defined('SITE_ROOT'))     define('SITE_ROOT', dirname((string)PUBLIC_PATH));

/* ---------------------------------------------------------
   Staff detection + HTTPS helper
--------------------------------------------------------- */
if (!function_exists('mk_is_staff_request')) {
  function mk_is_staff_request(): bool {
    $uri    = (string)($_SERVER['REQUEST_URI'] ?? '');
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    return
      ($uri !== '' && preg_match('~^/staff(?:/|$)~', $uri))
      || ($script !== '' && preg_match('~/staff(?:/|$)~', $script));
  }
}

if (!function_exists('mk_https_request')) {
  function mk_https_request(): bool {
    return
      (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
      || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443')
      || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
  }
}

/* ---------------------------------------------------------
   Lazy session start
--------------------------------------------------------- */
if (!function_exists('mk_session_start')) {
  function mk_session_start(bool $force = false): void {
    if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) return;

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    $shouldStart =
      $force
      || (defined('MK_REQUIRE_SESSION') && MK_REQUIRE_SESSION === true)
      || mk_is_staff_request()
      || !in_array($method, ['GET', 'HEAD'], true);

    if (!$shouldStart) return;

    if (!headers_sent()) {
      $https = mk_https_request();
      @session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https ? true : false,
        'httponly' => true,
        'samesite' => 'Lax',
      ]);
    }

    @session_start();
  }
}

/* Auto-session for staff routes only */
mk_session_start(false);

/* ---------------------------------------------------------
   Optional libs (tolerant, no overrides)
   Note: initialize.php already loads core_shim.php; do NOT force-load it here.
--------------------------------------------------------- */
$maybe_require = static function (?string $path): void {
  if (is_string($path) && $path !== '' && is_file($path)) {
    require_once $path;
  }
};

if (defined('PRIVATE_PATH')) {
  $private = (string)PRIVATE_PATH;

  // Only load helpers if they exist and likely not already loaded by bootstrap_init.php
  $maybe_require($private . '/functions/helpers.php');
  $maybe_require($private . '/functions/util.php');

  $maybe_require($private . '/functions/security.php');
  $maybe_require($private . '/functions/csrf.php');
  $maybe_require($private . '/functions/auth.php');
}

/* Defensive auth fallback */
if (!function_exists('mk_attempt_staff_login') && defined('APP_ROOT')) {
  $fallback = rtrim((string)APP_ROOT, '/') . '/private/functions/auth.php';
  if (is_file($fallback)) require_once $fallback;
}

/* ---------------------------------------------------------
   Shared view vars + shared template loader (optional)
--------------------------------------------------------- */
if (!function_exists('mk_view_set')) {
  function mk_view_set(array $vars): void {
    if (!isset($GLOBALS['mk_view_vars']) || !is_array($GLOBALS['mk_view_vars'])) {
      $GLOBALS['mk_view_vars'] = [];
    }
    foreach ($vars as $k => $v) {
      if (!is_string($k) || $k === '') continue;
      $GLOBALS['mk_view_vars'][$k] = $v;
    }
  }
}

if (!function_exists('mk_require_shared')) {
  function mk_require_shared(string $filename): void {
    $filename = trim($filename);
    if ($filename === '') return;

    if (!defined('PRIVATE_PATH')) {
      throw new RuntimeException('PRIVATE_PATH not defined; cannot include shared template.');
    }

    $file = rtrim((string)PRIVATE_PATH, '/') . '/shared/' . ltrim($filename, '/');
    if (!is_file($file)) {
      throw new RuntimeException('Shared template not found: ' . $file);
    }

    $GLOBALS['mk_brand_name'] = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomi Igbo';
    $GLOBALS['mk_site_name']  = defined('MK_SITE_NAME')  ? (string)MK_SITE_NAME  : 'Mkomi Igbo';

    if (isset($GLOBALS['mk_view_vars']) && is_array($GLOBALS['mk_view_vars'])) {
      extract($GLOBALS['mk_view_vars'], EXTR_OVERWRITE);
    }

    require $file;
  }
}
