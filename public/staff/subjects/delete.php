<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
mk_require_staff_login();

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r", "\n"], '', trim($location));
    if ($location === '') $location = '/staff/subjects/index.php';
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
    return (bool)$cache[$key];
  }
}
if (!function_exists('csrf_verify_local')) {
  function csrf_verify_local(string $token): bool {
    $sess = (string)($_SESSION['csrf_token'] ?? '');
    return ($token !== '' && $sess !== '' && hash_equals($sess, $token));
  }
}
if (!function_exists('csrf_field_local')) {
  function csrf_field_local(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . h((string)$_SESSION['csrf_token']) . '">';
  }
}

try {
  $pdo = function_exists('staff_pdo') ? staff_pdo() : (function_exists('db') ? db() : null);
  if (!$pdo instanceof PDO) throw new RuntimeException('Database handle not available.');
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$return = safe_return((string)($_GET['return'] ?? ($_POST['return'] ?? '/staff/subjects/index.php')), '/staff/subjects/index.php');

if ($id <= 0) {
  flash_set('error', 'Invalid subject ID.');
  redirect_to($return);
}

$has_display = column_exists($pdo, 'subjects', 'display_name');
$has_name    = column_exists($pdo, 'subjects', 'name');
$has_title   = column_exists($pdo, 'subjects', 'title');
$has_slug    = column_exists($pdo, 'subjects', 'slug');

$nameCol = $has_display ? 'display_name' : ($has_name ? 'name' : ($has_title ? 'title' : null));

$sel = ["id"];
$sel[] = $has_slug ? "slug" : "NULL AS slug";
$sel[] = $nameCol ? "`{$nameCol}` AS display_name" : "CAST(id AS CHAR) AS display_name";

$st = $pdo->prepare("SELECT " . implode(', ', $sel) . " FROM subjects WHERE id = ? LIMIT 1");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  flash_set('error', "Subject not found (#{$id}).");
  redirect_to($return);
}

$name = (string)($row['display_name'] ?? ('Subject #' . $id));
$slug = (string)($row['slug'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!csrf_verify_local($token)) {
    flash_set('error', 'Security check failed (CSRF). Please retry.');
    redirect_to('/staff/subjects/delete.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));
  }

  try {
    $del = $pdo->prepare("DELETE FROM subjects WHERE id = ? LIMIT 1");
    $del->execute([$id]);

    if ($del->rowCount() > 0) {
      flash_set('notice', "Deleted subject #{$id} successfully.");
    } else {
      flash_set('error', 'Delete did not complete (subject may already be removed).');
    }

    redirect_to($return);
  } catch (Throwable $e) {
    flash_set('error', 'Delete failed: ' . $e->getMessage());
    redirect_to('/staff/subjects/delete.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));
  }
}

$page_title = 'Staff • Delete Subject';
if (function_exists('mk_view_set')) mk_view_set(['page_title' => $page_title]);

if (function_exists('mk_require_shared')) {
  mk_require_shared('staff_header.php');
} else {
  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html><head><meta charset='utf-8'><title>" . h($page_title) . "</title></head><body><main>";
}

echo '<section class="hero">';
echo '<h1 class="hero__title">Delete Subject</h1>';
echo '<p class="hero__subtitle">This action cannot be undone.</p>';
echo '</section>';

echo '<section class="card"><div class="card__body">';
echo '<h2 class="card__title" style="margin:0 0 8px;">Confirm deletion</h2>';
echo '<p class="muted" style="margin:0 0 12px;">You are about to delete <strong>' . h($name) . '</strong>'
  . ($slug !== '' ? ' <span class="muted">(' . h($slug) . ')</span>' : '')
  . '.</p>';

echo '<form method="post" action="">';
echo csrf_field_local();
echo '<input type="hidden" name="id" value="' . h((string)$id) . '">';
echo '<input type="hidden" name="return" value="' . h($return) . '">';
echo '<div class="actions" style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">';
echo '<button class="btn btn--danger" type="submit">Yes, delete</button>';
echo '<a class="btn btn--ghost" href="' . h($return) . '">Cancel</a>';
echo '</div>';
echo '</form>';
echo '</div></section>';

if (function_exists('mk_require_shared')) {
  mk_require_shared('staff_footer.php');
} else {
  echo "</main></body></html>";
}
