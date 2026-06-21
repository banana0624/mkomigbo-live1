<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

mk_staff_session_start();
auth_require_role('staff');

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!function_exists('staff_redirect')) {
  function staff_redirect(string $location, int $code = 302): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, $code);
    exit;
  }
}

if (!function_exists('staff_safe_return_url')) {
  function staff_safe_return_url(string $raw, string $default): string {
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

if (!function_exists('pf_flash_set')) {
  function pf_flash_set(string $key, string $msg): void {
    mk_staff_session_start();
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
      $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = $msg;
  }
}

if (!function_exists('pf_col_exists')) {
  function pf_col_exists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $k = strtolower($table . '.' . $column);
    if (array_key_exists($k, $cache)) return (bool)$cache[$k];

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
      $cache[$k] = (bool)$st->fetchColumn();
    } catch (Throwable $e) {
      $cache[$k] = false;
    }

    return (bool)$cache[$k];
  }
}

if (!function_exists('pf_csrf_ok')) {
  function pf_csrf_ok(): bool {
    mk_staff_session_start();

    $posted = [
      (string)($_POST['csrf_token'] ?? ''),
      (string)($_POST['csrf'] ?? ''),
      (string)($_POST['_token'] ?? ''),
    ];
    $posted = array_values(array_filter($posted, static fn($v) => $v !== ''));
    if (!$posted) return false;

    foreach (['csrf_verify', 'mk_csrf_verify', 'csrf_check'] as $fn) {
      if (function_exists($fn)) {
        foreach ($posted as $tok) {
          try {
            if ($fn($tok) === true) return true;
          } catch (Throwable $e) {
            // continue
          }
        }
      }
    }

    $sessionCandidates = [];
    foreach (['csrf_token', 'csrf', '_token'] as $k) {
      if (!empty($_SESSION[$k]) && is_string($_SESSION[$k])) {
        $sessionCandidates[] = (string)$_SESSION[$k];
      }
    }
    $sessionCandidates = array_values(array_unique(array_filter($sessionCandidates, static fn($v) => $v !== '')));

    foreach ($posted as $tok) {
      foreach ($sessionCandidates as $sess) {
        if (hash_equals($sess, $tok)) return true;
      }
    }

    return false;
  }
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$return = staff_safe_return_url(
  (string)($_POST['return'] ?? $_GET['return'] ?? $_SERVER['HTTP_REFERER'] ?? ''),
  '/staff/subjects/pgs/'
);

if ($method !== 'POST') {
  pf_flash_set('error', 'Invalid request method.');
  staff_redirect($return, 302);
}

if (!pf_csrf_ok()) {
  pf_flash_set('error', 'Security check failed. Please try again.');
  staff_redirect($return, 302);
}

$pageId = (int)($_POST['page_id'] ?? $_POST['id'] ?? 0);
$action = strtolower(trim((string)($_POST['action'] ?? $_POST['workflow_action'] ?? $_POST['do'] ?? '')));

if ($pageId <= 0) {
  pf_flash_set('error', 'Missing or invalid page id.');
  staff_redirect($return, 302);
}

if (!in_array($action, ['publish', 'unpublish'], true)) {
  pf_flash_set('error', 'Unknown workflow action.');
  staff_redirect($return, 302);
}

if (!function_exists('db')) {
  pf_flash_set('error', 'Database helper unavailable.');
  staff_redirect($return, 302);
}

try {
  $pdo = db();
  if (!$pdo instanceof PDO) {
    pf_flash_set('error', 'Database connection unavailable.');
    staff_redirect($return, 302);
  }

  $st = $pdo->prepare("SELECT id FROM pages WHERE id = ? LIMIT 1");
  $st->execute([$pageId]);
  $exists = (int)($st->fetchColumn() ?: 0);
  if ($exists <= 0) {
    pf_flash_set('error', 'Page not found.');
    staff_redirect($return, 302);
  }

  $sets = [];
  $vals = [];

  $isPublish = ($action === 'publish');

  if (pf_col_exists($pdo, 'pages', 'published')) {
    $sets[] = "published = ?";
    $vals[] = $isPublish ? 1 : 0;
  }
  if (pf_col_exists($pdo, 'pages', 'is_published')) {
    $sets[] = "is_published = ?";
    $vals[] = $isPublish ? 1 : 0;
  }
  if (pf_col_exists($pdo, 'pages', 'status')) {
    $sets[] = "status = ?";
    $vals[] = $isPublish ? 'published' : 'draft';
  }
  if (pf_col_exists($pdo, 'pages', 'workflow_state')) {
    $sets[] = "workflow_state = ?";
    $vals[] = $isPublish ? 'published' : 'draft';
  }
  if (pf_col_exists($pdo, 'pages', 'published_at')) {
    $sets[] = $isPublish ? "published_at = NOW()" : "published_at = NULL";
  }
  if (pf_col_exists($pdo, 'pages', 'updated_at')) {
    $sets[] = "updated_at = NOW()";
  }
  if (pf_col_exists($pdo, 'pages', 'modified_at')) {
    $sets[] = "modified_at = NOW()";
  }
  if (pf_col_exists($pdo, 'pages', 'updated_by')) {
    $sets[] = "updated_by = ?";
    $vals[] = (int)($_SESSION['staff_user_id'] ?? 0);
  }
  if (pf_col_exists($pdo, 'pages', 'updated_by_staff_id')) {
    $sets[] = "updated_by_staff_id = ?";
    $vals[] = (int)($_SESSION['staff_user_id'] ?? 0);
  }

  if (!$sets) {
    pf_flash_set('error', 'No publishable workflow columns were found on pages.');
    staff_redirect($return, 302);
  }

  $sql = "UPDATE pages SET " . implode(', ', $sets) . " WHERE id = ? LIMIT 1";
  $vals[] = $pageId;

  $up = $pdo->prepare($sql);
  $up->execute($vals);

  if (function_exists('mk_staff_audit_log')) {
    mk_staff_audit_log(
      $isPublish ? 'page_publish' : 'page_unpublish',
      ['page_id' => $pageId, 'staff_user_id' => (int)($_SESSION['staff_user_id'] ?? 0)]
    );
  }

  pf_flash_set('success', $isPublish ? 'Page published.' : 'Page unpublished.');
  staff_redirect($return, 302);

} catch (Throwable $e) {
  if (function_exists('mk_staff_audit_log')) {
    mk_staff_audit_log('page_workflow_update_error', [
      'page_id' => $pageId,
      'action' => $action,
      'error' => $e->getMessage(),
      'staff_user_id' => (int)($_SESSION['staff_user_id'] ?? 0),
    ]);
  }
  pf_flash_set('error', 'Workflow update failed.');
  staff_redirect($return, 302);
}
