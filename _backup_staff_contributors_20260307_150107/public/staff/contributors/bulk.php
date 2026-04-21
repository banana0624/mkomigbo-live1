<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

mk_require_staff_login();

/**
 * /public/staff/contributors/bulk.php
 * Staff: Bulk actions for contributors
 *
 * Supports actions:
 * - set_active  (status = active)
 * - set_draft   (status = draft)
 * - delete      (delete rows)
 *
 * Also accepts legacy synonyms:
 * - publish   -> set_active
 * - unpublish -> set_draft
 *
 * - CSRF protected
 * - No arrow functions
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

if (function_exists('require_staff_login')) { require_staff_login(); }

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, 302);
    exit;
  }
}
if (!function_exists('pf__flash_set')) {
  function pf__flash_set(string $key, string $msg): void {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][$key] = $msg;
  }
}
if (!function_exists('pf__safe_return_url')) {
  function pf__safe_return_url(string $raw, string $default): string {
    $raw = trim($raw);
    if ($raw === '') return $default;
    $raw = rawurldecode($raw);
    if ($raw === '' || $raw[0] !== '/') return $default;
    if (preg_match('~^//~', $raw)) return $default;
    if (preg_match('~^[a-z]+:~i', $raw)) return $default;
    if (!preg_match('~^/staff/~', $raw)) return $default;
    return $raw;
  }
}
if (!function_exists('mk_table_exists')) {
  function mk_table_exists(PDO $db, string $table): bool {
    $st = $db->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1");
    $st->execute([$table]);
    return (bool)$st->fetchColumn();
  }
}
if (!function_exists('mk_column_exists')) {
  function mk_column_exists(PDO $db, string $table, string $column): bool {
    $st = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
    $st->execute([$table, $column]);
    return ((int)$st->fetchColumn() > 0);
  }
}

/* ---------------------------------------------------------
   Method
--------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  redirect_to(function_exists('url_for') ? url_for('/staff/contributors/') : '/staff/contributors/');
}

/* CSRF */
$token = (string)($_POST['csrf_token'] ?? '');
$sess  = (string)($_SESSION['csrf_token'] ?? '');
if ($token === '' || $sess === '' || !hash_equals($sess, $token)) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Invalid CSRF token.";
  exit;
}

/* Return */
$default_return = '/staff/contributors/index.php';
$return = pf__safe_return_url((string)($_POST['return'] ?? $default_return), $default_return);

/* Action */
$action = trim((string)($_POST['action'] ?? ''));
if ($action === 'publish') $action = 'set_active';
if ($action === 'unpublish') $action = 'set_draft';

if ($action !== 'set_active' && $action !== 'set_draft' && $action !== 'delete') {
  pf__flash_set('error', 'Invalid bulk action.');
  redirect_to($return);
}

/* IDs */
$ids_in = $_POST['ids'] ?? [];
if (!is_array($ids_in) || !$ids_in) {
  pf__flash_set('error', 'No rows selected.');
  redirect_to($return);
}

$ids = [];
foreach ($ids_in as $v) {
  $n = (int)$v;
  if ($n > 0) $ids[] = $n;
}
$ids = array_values(array_unique($ids));
if (!$ids) {
  pf__flash_set('error', 'No valid IDs selected.');
  redirect_to($return);
}

/* DB */
$pdo = function_exists('staff_pdo') ? staff_pdo() : (function_exists('db') ? db() : null);
if (!$pdo instanceof PDO) {
  pf__flash_set('error', 'Database handle not available.');
  redirect_to($return);
}
if (!mk_table_exists($pdo, 'contributors')) {
  pf__flash_set('error', 'contributors table not found.');
  redirect_to($return);
}

/* Execute */
try {
  $in = implode(',', array_fill(0, count($ids), '?'));

  if ($action === 'delete') {
    $sql = "DELETE FROM contributors WHERE id IN ($in)";
    $st = $pdo->prepare($sql);
    $st->execute($ids);
    pf__flash_set('notice', 'Deleted ' . (int)$st->rowCount() . ' contributor(s).');
    redirect_to($return);
  }

  if (!mk_column_exists($pdo, 'contributors', 'status')) {
    pf__flash_set('error', 'Status column missing on contributors.');
    redirect_to($return);
  }

  $newStatus = ($action === 'set_active') ? 'active' : 'draft';
  $sql = "UPDATE contributors SET status = ? WHERE id IN ($in)";
  $params = array_merge([$newStatus], $ids);

  $st = $pdo->prepare($sql);
  $st->execute($params);

  pf__flash_set('notice', 'Updated ' . count($ids) . ' contributor(s).');
  redirect_to($return);

} catch (Throwable $e) {
  pf__flash_set('error', 'Bulk action failed: ' . $e->getMessage());
  redirect_to($return);
}
