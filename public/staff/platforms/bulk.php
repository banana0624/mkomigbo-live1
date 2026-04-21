<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
mk_require_staff_login();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r","\n"], '', trim($location));
    if ($location === '') $location = '/staff/platforms/index.php';
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

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  redirect_to('/staff/platforms/index.php');
}

$token = (string)($_POST['csrf_token'] ?? '');
$sess  = (string)($_SESSION['csrf_token'] ?? '');
if ($token === '' || $sess === '' || !hash_equals($sess, $token)) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Invalid CSRF token.";
  exit;
}

$return = safe_return((string)($_POST['return'] ?? '/staff/platforms/index.php'), '/staff/platforms/index.php');

$action = trim((string)($_POST['action'] ?? ''));
$map = [
  'set_live'    => 'set_live',
  'set_draft'   => 'set_draft',
  'set_public'  => 'set_public',
  'set_private' => 'set_private',
  'soft_delete' => 'soft_delete',
  'restore'     => 'restore',
];
$action = $map[$action] ?? '';

if ($action === '') {
  flash_set('error', 'Invalid bulk action.');
  redirect_to($return);
}

$ids_in = $_POST['ids'] ?? [];
$ids = [];
if (is_array($ids_in)) {
  foreach ($ids_in as $v) {
    $n = (int)$v;
    if ($n > 0) $ids[] = $n;
  }
}
$ids = array_values(array_unique($ids));

if (!$ids) {
  flash_set('error', 'No valid platform rows selected.');
  redirect_to($return);
}

$pdo = db();
$in = implode(',', array_fill(0, count($ids), '?'));

try {
  switch ($action) {
    case 'set_live':
      $sql = "UPDATE platforms SET status='live', updated_at=NOW() WHERE id IN ($in)";
      $msg = 'Platform(s) set live.';
      break;

    case 'set_draft':
      $sql = "UPDATE platforms SET status='draft', updated_at=NOW() WHERE id IN ($in)";
      $msg = 'Platform(s) set draft.';
      break;

    case 'set_public':
      $sql = "UPDATE platforms SET is_public=1, updated_at=NOW() WHERE id IN ($in)";
      $msg = 'Platform(s) set public.';
      break;

    case 'set_private':
      $sql = "UPDATE platforms SET is_public=0, updated_at=NOW() WHERE id IN ($in)";
      $msg = 'Platform(s) set private.';
      break;

    case 'soft_delete':
      $sql = "UPDATE platforms SET deleted_at=NOW(), status='draft', is_public=0, updated_at=NOW() WHERE id IN ($in) AND deleted_at IS NULL";
      $msg = 'Platform(s) moved to trash.';
      break;

    case 'restore':
      $sql = "UPDATE platforms SET deleted_at=NULL, updated_at=NOW() WHERE id IN ($in)";
      $msg = 'Platform(s) restored.';
      break;

    default:
      flash_set('error', 'Unsupported bulk action.');
      redirect_to($return);
  }

  $st = $pdo->prepare($sql);
  $st->execute($ids);

  flash_set('notice', $msg);
  redirect_to($return);

} catch (Throwable $e) {
  flash_set('error', 'Bulk action failed: ' . $e->getMessage());
  redirect_to($return);
}
