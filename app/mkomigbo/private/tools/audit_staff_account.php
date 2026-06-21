<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/tools/audit_staff_account.php
 * Private tool: audits staff auth/session/RBAC assumptions.
 *
 * Safe for:
 * - CLI (php private/tools/audit_staff_account.php)
 * - Web runner (captures plain-text output)
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!function_exists('mk_tool_line')) {
  function mk_tool_line(string $label, string $value = ''): void {
    if ($value !== '') {
      echo $label . ': ' . $value . "\n";
    } else {
      echo $label . "\n";
    }
  }
}

/* ---------------------------------------------------------
   Bootstrap (DO NOT use ../../_init.php here)
--------------------------------------------------------- */
if (!defined('APP_ROOT')) {
  // __DIR__ = .../app/mkomigbo/private/tools
  // // DISABLED_APP_ROOT (DISABLED_AUTO_FIX), dirname(__DIR__, 2));
}

$init = APP_ROOT . '/private/assets/initialize.php';
if (!is_file($init)) {
  mk_tool_line('FAIL', 'initialize.php missing at ' . $init);
  exit(1);
}

require_once $init;

mk_tool_line('audit_staff_account ok');
mk_tool_line('PHP', PHP_VERSION);
mk_tool_line('SAPI', (string)php_sapi_name());
mk_tool_line('APP_ROOT', (string)APP_ROOT);
mk_tool_line('INIT', $init);

/* ---------------------------------------------------------
   Session/auth observations (best-effort, no secrets)
--------------------------------------------------------- */
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
mk_tool_line('SESSION_ACTIVE', (session_status() === PHP_SESSION_ACTIVE) ? 'yes' : 'no');

$staff_id = $_SESSION['staff_user_id'] ?? null;
mk_tool_line('staff_user_id', is_scalar($staff_id) ? (string)$staff_id : '(none)');

$role = $_SESSION['staff_role'] ?? null;
mk_tool_line('staff_role', is_scalar($role) ? (string)$role : '(none)');

/* Auth helper presence */
mk_tool_line('mk_is_staff_logged_in()', function_exists('mk_is_staff_logged_in') ? 'yes' : 'no');
mk_tool_line('mk_require_staff_login()', function_exists('mk_require_staff_login') ? 'yes' : 'no');
mk_tool_line('mk_require_staff_permission()', function_exists('mk_require_staff_permission') ? 'yes' : 'no');

/* DB check (safe) */
try {
  if (!function_exists('db')) throw new RuntimeException('db() not found');
  $pdo = db();
  if (!$pdo instanceof PDO) throw new RuntimeException('db() did not return PDO');
  $ok = (bool)$pdo->query('SELECT 1');
  mk_tool_line('DB', $ok ? 'OK' : 'FAIL');
} catch (Throwable $e) {
  mk_tool_line('DB_FAIL', $e->getMessage());
}

/* Optional: confirm staff_users exists */
try {
  if (function_exists('db')) {
    $pdo = db();
    $st = $pdo->query("SHOW TABLES LIKE 'staff_users'");
    $has = $st && $st->fetchColumn();
    mk_tool_line('staff_users table', $has ? 'present' : 'missing');
  }
} catch (Throwable $e) {
  mk_tool_line('staff_users check fail', $e->getMessage());
}
