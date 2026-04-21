<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
mk_require_staff_login();

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  header('Content-Type: text/plain; charset=utf-8');
  echo "Method Not Allowed";
  exit;
}

if (!function_exists('redirect_to')) {
  function redirect_to(string $location, int $code = 302): void {
    $location = str_replace(["\r", "\n"], '', trim($location));
    if ($location === '') $location = '/staff/subjects/index.php';
    if ($location[0] === '/') {
      header('Location: ' . $location, true, $code);
      exit;
    }
    if (function_exists('url_for')) {
      $location = (string)url_for($location);
    }
    header('Location: ' . $location, true, $code);
    exit;
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
if (!function_exists('flash_set')) {
  function flash_set(string $key, string $msg): void {
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][$key] = $msg;
  }
}
if (!function_exists('column_exists')) {
  function column_exists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];

    try {
      $st = $pdo->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
      ");
      $st->execute([$table, $column]);
      $cache[$key] = (bool)$st->fetchColumn();
    } catch (Throwable $e) {
      $cache[$key] = false;
    }
    return (bool)$cache[$key];
  }
}

if (function_exists('csrf_require')) {
  csrf_require();
} else {
  $sent = (string)($_POST['csrf_token'] ?? '');
  $sess = (string)($_SESSION['csrf_token'] ?? '');
  if ($sent === '' || $sess === '' || !hash_equals($sess, $sent)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Invalid CSRF token.";
    exit;
  }
}

$return = safe_return((string)($_POST['return'] ?? '/staff/subjects/index.php'), '/staff/subjects/index.php');

try {
  $pdo = function_exists('staff_pdo') ? staff_pdo() : (function_exists('db') ? db() : null);
  if (!$pdo instanceof PDO) {
    throw new RuntimeException('Database handle not available.');
  }
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

$action = trim((string)($_POST['action'] ?? ''));
$allowed = ['publish', 'unpublish', 'delete', 'save_order'];
if (!in_array($action, $allowed, true)) {
  flash_set('error', 'Unknown bulk action.');
  redirect_to($return);
}

$ids = $_POST['ids'] ?? [];
if (!is_array($ids)) $ids = [];
$ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($v) => $v > 0)));

$has_is_public = column_exists($pdo, 'subjects', 'is_public');
$has_visible   = column_exists($pdo, 'subjects', 'visible');
$pub_col       = $has_is_public ? 'is_public' : ($has_visible ? 'visible' : null);

$has_nav_order = column_exists($pdo, 'subjects', 'nav_order');
$has_position  = column_exists($pdo, 'subjects', 'position');
$order_col     = $has_nav_order ? 'nav_order' : ($has_position ? 'position' : null);

try {
  if ($action === 'save_order') {
    if (!$order_col) {
      flash_set('error', 'No order column found (nav_order/position).');
      redirect_to($return);
    }

    $map = $_POST['nav_order'] ?? [];
    if (!is_array($map)) $map = [];

    $pdo->beginTransaction();
    $st = $pdo->prepare("UPDATE subjects SET `{$order_col}` = :v WHERE id = :id LIMIT 1");

    $count = 0;
    foreach ($map as $id => $val) {
      $id = (int)$id;
      if ($id <= 0) continue;

      $raw = trim((string)$val);

      if ($raw === '') {
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->bindValue(':v', null, PDO::PARAM_NULL);
        $st->execute();
        $count++;
        continue;
      }

      if (!preg_match('/^-?\d+$/', $raw)) continue;

      $v = (int)$raw;
      $st->bindValue(':id', $id, PDO::PARAM_INT);
      $st->bindValue(':v', $v, PDO::PARAM_INT);
      $st->execute();
      $count++;
    }

    $pdo->commit();
    flash_set('notice', "Order saved ({$count} row(s)).");
    redirect_to($return);
  }

  if (!$ids) {
    flash_set('error', 'No rows selected.');
    redirect_to($return);
  }

  if ($action === 'publish' || $action === 'unpublish') {
    if (!$pub_col) {
      flash_set('error', 'No publish/visibility column found on subjects.');
      redirect_to($return);
    }

    $value = ($action === 'publish') ? 1 : 0;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE subjects SET `{$pub_col}` = ? WHERE id IN ({$placeholders})";
    $params = array_merge([$value], $ids);

    $st = $pdo->prepare($sql);
    $st->execute($params);

    flash_set('notice', $action === 'publish'
      ? 'Selected subjects published.'
      : 'Selected subjects unpublished.');
    redirect_to($return);
  }

  if ($action === 'delete') {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "DELETE FROM subjects WHERE id IN ({$placeholders})";
    $st = $pdo->prepare($sql);
    $st->execute($ids);

    flash_set('notice', 'Selected subject(s) deleted.');
    redirect_to($return);
  }

  flash_set('error', 'Unknown bulk action.');
  redirect_to($return);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  flash_set('error', 'Bulk action failed: ' . $e->getMessage());
  redirect_to($return);
}
