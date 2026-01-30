<?php
declare(strict_types=1);

/**
 * /public/staff/pages/attachments_delete.php
 * Staff: delete an attachment safely (DB + disk).
 *
 * Redirect: attach=deleted|missing|denied|csrf|invalid|error
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  exit;
}

/* ---------------------------------------------------------
   Minimal fallbacks
--------------------------------------------------------- */
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
if (!function_exists('staff_redirect')) {
  function staff_redirect(string $location, int $code = 302): void {
    $location = str_replace(["\r","\n"], '', $location);
    header('Location: ' . $location, true, $code);
    exit;
  }
}
if (!function_exists('staff_pdo')) {
  function staff_pdo(): ?PDO {
    return (function_exists('db') && db() instanceof PDO) ? db() : null;
  }
}
if (!function_exists('staff_csrf_verify')) {
  function staff_csrf_verify(string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $sess = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sess) || $sess === '' || $token === '') return false;
    return hash_equals($sess, $token);
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
      return (bool)$cache[$key];
    } catch (Throwable $e) {
      $cache[$key] = false;
      return false;
    }
  }
}

$return = staff_safe_return_url((string)($_POST['return'] ?? ''), '/staff/subjects/pgs/index.php');

$go = static function (string $return, string $code): never {
  $target = $return . (strpos($return, '?') === false ? '?' : '&') . 'attach=' . rawurlencode($code);
  $target = str_replace(["\r","\n"], '', $target);
  if (function_exists('url_for')) $target = (string)url_for($target);
  staff_redirect($target, 303);
};

/* Auth */
$staffId = function_exists('staff_id') ? (int)staff_id() : 0;
if ($staffId < 1) {
  $go($return, 'denied');
}

/* CSRF */
$token = (string)($_POST['csrf_token'] ?? ($_POST['csrf'] ?? ''));
if (!staff_csrf_verify($token)) {
  $go($return, 'csrf');
}

/* Inputs */
$id     = (int)($_POST['id'] ?? 0);
$pageId = (int)($_POST['page_id'] ?? 0);
if ($id < 1 || $pageId < 1) {
  $go($return, 'invalid');
}

/* DB */
$pdo = staff_pdo();
if (!$pdo instanceof PDO) {
  $go($return, 'error');
}

/* Ensure table exists */
try {
  $stt = $pdo->prepare("
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'page_files'
    LIMIT 1
  ");
  $stt->execute();
  if (!$stt->fetchColumn()) $go($return, 'missing');
} catch (Throwable $e) {
  $go($return, 'error');
}

/* Schema-tolerant fetch */
$cols = ['id','page_id'];
foreach (['file_path','stored_path','stored_name','is_external','external_url'] as $c) {
  if (pf__column_exists($pdo, 'page_files', $c)) $cols[] = $c;
}

$st = $pdo->prepare("
  SELECT " . implode(', ', array_unique($cols)) . "
  FROM page_files
  WHERE id = :id AND page_id = :pid
  LIMIT 1
");
$st->bindValue(':id', $id, PDO::PARAM_INT);
$st->bindValue(':pid', $pageId, PDO::PARAM_INT);
$st->execute();
$row = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$row) {
  $go($return, 'missing');
}

/* Detect external rows (skip disk delete) */
$isExternal = false;
if (array_key_exists('is_external', $row)) {
  $isExternal = ((int)($row['is_external'] ?? 0) === 1);
}
if (!$isExternal && !empty($row['external_url'] ?? '')) {
  $isExternal = true;
}

/* Disk delete (local only, best-effort) */
$deletedFile = false;
$hadLocalCandidate = false;

if (!$isExternal) {
  $uploadsRoot = dirname(__DIR__, 2) . '/lib/uploads/page_files'; // /public_html/lib/uploads/page_files
  $uploadsRootReal = realpath($uploadsRoot);

  if ($uploadsRootReal && is_dir($uploadsRootReal)) {
    $rootNorm = rtrim(str_replace('\\','/',$uploadsRootReal), '/');

    // Prefer stored_path, then file_path
    $relative = '';
    $storedPath = isset($row['stored_path']) ? trim((string)$row['stored_path']) : '';
    $filePath   = isset($row['file_path']) ? trim((string)$row['file_path']) : '';

    if ($storedPath !== '') $relative = $storedPath;
    elseif ($filePath !== '') $relative = $filePath;

    if ($relative !== '') {
      // If it looks like "/lib/uploads/page_files/..." map it into web root, else treat as relative under uploadsRootReal
      $abs = $relative;

      if ($abs[0] === '/' || $abs[0] === '\\') {
        // try to map /lib/uploads/page_files/... to /public_html/lib/uploads/page_files/...
        $needle = '/lib/uploads/page_files/';
        $relNorm = str_replace('\\','/',$abs);
        if (strpos($relNorm, $needle) === 0) {
          $abs = $rootNorm . '/' . ltrim(substr($relNorm, strlen($needle)), '/');
        } else {
          // absolute path provided — allow only if it resolves under uploads root
          $abs = $relNorm;
        }
      } else {
        $abs = $rootNorm . '/' . ltrim($abs, '/\\');
      }

      $hadLocalCandidate = true;

      $real = realpath($abs);
      if ($real && is_file($real)) {
        $realNorm = str_replace('\\','/',$real);
        if (strpos($realNorm, $rootNorm . '/') === 0) {
          $deletedFile = @unlink($real) ? true : false;
        }
      }
    }
  }
}

/* DB delete is authoritative */
try {
  $del = $pdo->prepare("DELETE FROM page_files WHERE id = :id AND page_id = :pid LIMIT 1");
  $del->bindValue(':id', $id, PDO::PARAM_INT);
  $del->bindValue(':pid', $pageId, PDO::PARAM_INT);
  $del->execute();
} catch (Throwable $e) {
  $go($return, 'error');
}

/* Outcome */
if ($isExternal) {
  $go($return, 'deleted');
}
if (!$hadLocalCandidate) {
  // row existed but had no path columns populated (or no uploads root); DB deleted anyway
  $go($return, 'deleted');
}
$go($return, $deletedFile ? 'deleted' : 'missing');
