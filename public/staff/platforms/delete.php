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

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$return = safe_return((string)($_GET['return'] ?? $_POST['return'] ?? '/staff/platforms/index.php'), '/staff/platforms/index.php');

if ($id <= 0) {
  flash_set('error', 'Invalid platform ID.');
  redirect_to($return);
}

$pdo = db();

try {
  $st = $pdo->prepare("
    UPDATE platforms
       SET deleted_at = NOW(),
           status = 'draft',
           is_public = 0,
           updated_at = NOW()
     WHERE id = ?
       AND deleted_at IS NULL
     LIMIT 1
  ");
  $st->execute([$id]);

  flash_set('notice', 'Platform moved to trash.');
  redirect_to($return);

} catch (Throwable $e) {
  flash_set('error', 'Delete failed: ' . $e->getMessage());
  redirect_to($return);
}
