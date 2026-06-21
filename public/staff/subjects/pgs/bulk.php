<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
auth_require_role('staff');

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r","\n"], '', trim($location));
    if ($location === '') $location = '/staff/subjects/pgs/index.php';
    header('Location: ' . $location, true, 302);
    exit;
  }
}
if (!function_exists('pf__flash_set')) {
  function pf__flash_set(string $key, string $msg): void {
    mk_staff_session_start();
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
    if (strpos($raw, '/staff/') !== 0) return $default;
    return $raw;
  }
}
if (!function_exists('pf__csrf_verify')) {
  function pf__csrf_verify(string $token): bool {
    mk_staff_session_start();
    $sess = $_SESSION['csrf_token'] ?? '';
    return is_string($sess) && $sess !== '' && $token !== '' && hash_equals($sess, $token);
  }
}
if (!function_exists('pf__pdo')) {
  function pf__pdo(): ?PDO {
    if (function_exists('staff_pdo')) {
      $pdo = staff_pdo();
      if ($pdo instanceof PDO) return $pdo;
    }
    if (function_exists('db')) {
      $pdo = db();
      if ($pdo instanceof PDO) return $pdo;
    }
    return null;
  }
}
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
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

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
  redirect_to('/staff/subjects/pgs/index.php');
}

$return = pf__safe_return_url((string)($_POST['return'] ?? ''), '/staff/subjects/pgs/index.php');

if (!pf__csrf_verify((string)($_POST['csrf_token'] ?? ''))) {
  pf__flash_set('error', 'Security check failed (CSRF). Please retry.');
  redirect_to($return);
}

$pdo = pf__pdo();
if (!$pdo instanceof PDO) {
  pf__flash_set('error', 'Database handle not available.');
  redirect_to($return);
}

$action = (string)($_POST['action'] ?? '');
$ids = $_POST['ids'] ?? [];
if (!is_array($ids)) $ids = [];

$cleanIds = [];
foreach ($ids as $v) {
  $i = filter_var($v, FILTER_VALIDATE_INT);
  if ($i !== false && (int)$i > 0) $cleanIds[] = (int)$i;
}
$cleanIds = array_values(array_unique($cleanIds));

$orderCol = pf__column_exists($pdo, 'pages', 'nav_order') ? 'nav_order'
         : (pf__column_exists($pdo, 'pages', 'position') ? 'position' : null);

$hasIsPublic = pf__column_exists($pdo, 'pages', 'is_public');
$hasVisible  = pf__column_exists($pdo, 'pages', 'visible');
$hasStatus   = pf__column_exists($pdo, 'pages', 'status');
$hasWorkflow = pf__column_exists($pdo, 'pages', 'workflow_state');
$hasPublishedAt = pf__column_exists($pdo, 'pages', 'published_at');
$hasUpdatedAt = pf__column_exists($pdo, 'pages', 'updated_at');
$hasModifiedAt = pf__column_exists($pdo, 'pages', 'modified_at');

try {
  if ($action === 'publish' || $action === 'unpublish') {
    if (!$cleanIds) {
      pf__flash_set('error', 'No pages selected.');
      redirect_to($return);
    }

    $sets = [];
    $vals = [];
    $isPublish = ($action === 'publish');

    if ($hasIsPublic) { $sets[] = 'is_public = ?'; $vals[] = $isPublish ? 1 : 0; }
    if ($hasVisible)  { $sets[] = 'visible = ?';   $vals[] = $isPublish ? 1 : 0; }
    if ($hasStatus)   { $sets[] = 'status = ?';    $vals[] = $isPublish ? 'published' : 'draft'; }
    if ($hasWorkflow) { $sets[] = 'workflow_state = ?'; $vals[] = $isPublish ? 'published' : 'draft'; }
    if ($hasPublishedAt) $sets[] = $isPublish ? 'published_at = NOW()' : 'published_at = NULL';
    if ($hasUpdatedAt)   $sets[] = 'updated_at = NOW()';
    if ($hasModifiedAt)  $sets[] = 'modified_at = NOW()';

    if (!$sets) {
      pf__flash_set('error', 'No publish workflow columns were found on pages.');
      redirect_to($return);
    }

    $marks = implode(',', array_fill(0, count($cleanIds), '?'));
    $sql = "UPDATE pages SET " . implode(', ', $sets) . " WHERE id IN ($marks)";
    $vals = array_merge($vals, $cleanIds);

    $st = $pdo->prepare($sql);
    $st->execute($vals);

    pf__flash_set('notice', $isPublish ? 'Selected pages published.' : 'Selected pages unpublished.');
    redirect_to($return);
  }

  if ($action === 'save_order') {
    if ($orderCol === null) {
      pf__flash_set('error', 'Ordering column not available in pages table.');
      redirect_to($return);
    }

    $nav = $_POST['nav_order'] ?? [];
    if (!is_array($nav) || !$nav) {
      pf__flash_set('error', 'No ordering values submitted.');
      redirect_to($return);
    }

    $pdo->beginTransaction();
    $st = $pdo->prepare("UPDATE pages SET `$orderCol` = :ord WHERE id = :id LIMIT 1");
    $updated = 0;

    foreach ($nav as $idStr => $ordVal) {
      $pid = filter_var($idStr, FILTER_VALIDATE_INT);
      if ($pid === false || (int)$pid <= 0) continue;

      $v = trim((string)$ordVal);
      if ($v === '') {
        $st->bindValue(':ord', null, PDO::PARAM_NULL);
      } else {
        $oi = filter_var($v, FILTER_VALIDATE_INT);
        if ($oi === false) continue;
        $st->bindValue(':ord', (int)$oi, PDO::PARAM_INT);
      }
      $st->bindValue(':id', (int)$pid, PDO::PARAM_INT);
      $st->execute();
      $updated++;
    }

    $pdo->commit();
    pf__flash_set('notice', 'Order saved (' . $updated . ' row(s)).');
    redirect_to($return);
  }

  pf__flash_set('error', 'Unknown bulk action.');
  redirect_to($return);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  pf__flash_set('error', 'Bulk action failed: ' . $e->getMessage());
  redirect_to($return);
}
