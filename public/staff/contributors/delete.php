<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
auth_require_role('staff');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r","\n"], '', trim($location));
    if ($location === '') $location = '/staff/contributors/index.php';
    if ($location[0] === '/') {
      header('Location: ' . $location, true, 302);
      exit;
    }
    if (function_exists('url_for')) {
      $location = (string)url_for($location);
    }
    header('Location: ' . $location, true, 302);
    exit;
  }
}
if (!function_exists('flash_set')) {
  function flash_set(string $key, string $msg): void {
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][$key] = $msg;
  }
}
if (!function_exists('safe_return')) {
  function safe_return(string $raw, string $default): string {
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
if (!function_exists('column_exists')) {
  function column_exists(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
    $st->execute([$table, $column]);
    return ((int)$st->fetchColumn() > 0);
  }
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$return = safe_return((string)($_GET['return'] ?? $_POST['return'] ?? '/staff/contributors/index.php'), '/staff/contributors/index.php');

if ($id <= 0) {
  flash_set('error', 'Invalid contributor ID.');
  redirect_to($return);
}

$pdo = function_exists('staff_pdo') ? staff_pdo() : db();

$has_status  = column_exists($pdo, 'contributors', 'status');
$has_public  = column_exists($pdo, 'contributors', 'is_public');
$has_deleted = column_exists($pdo, 'contributors', 'deleted_at');
$has_updated = column_exists($pdo, 'contributors', 'updated_at');

if (!$has_deleted) {
  flash_set('error', 'contributors.deleted_at column is missing.');
  redirect_to($return);
}

try {
  $sets = ["deleted_at = NOW()"];
  if ($has_status) $sets[] = "status = 'draft'";
  if ($has_public) $sets[] = "is_public = 0";
  if ($has_updated) $sets[] = "updated_at = NOW()";

  $sql = "UPDATE contributors SET " . implode(', ', $sets) . " WHERE id = ? AND deleted_at IS NULL LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute([$id]);

  flash_set('notice', 'Contributor moved to trash.');
  redirect_to($return);

} catch (Throwable $e) {
  flash_set('error', 'Delete failed: ' . $e->getMessage());
  redirect_to($return);
}
