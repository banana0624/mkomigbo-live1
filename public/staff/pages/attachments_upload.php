<?php
declare(strict_types=1);

/**
 * /public/staff/pages/attachments_upload.php
 * Staff: upload page attachments
 *
 * Redirect: attach=sent|partial|error|invalid|csrf|too_large|nofile
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
   Minimal fallbacks (only if _init.php did not provide them)
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

$return = staff_safe_return_url((string)($_POST['return'] ?? ''), '/staff/subjects/pgs/index.php');

/* redirect helper: attaches attach=... to return URL */
$go = static function (string $return, string $code): never {
  $target = $return . (strpos($return, '?') === false ? '?' : '&') . 'attach=' . rawurlencode($code);
  $target = str_replace(["\r","\n"], '', $target);
  if (function_exists('url_for')) $target = (string)url_for($target);
  staff_redirect($target, 303);
};

/* Require login */
$staffId = function_exists('staff_id') ? (int)staff_id() : 0;
if ($staffId < 1) {
  $login = function_exists('url_for')
    ? (string)url_for('/staff/login.php?notice=login')
    : '/staff/login.php?notice=login';
  $login = str_replace(["\r","\n"], '', $login);
  header('Location: ' . $login, true, 302);
  exit;
}

/* CSRF (accept csrf_token canonical + legacy csrf) */
$token = (string)($_POST['csrf_token'] ?? ($_POST['csrf'] ?? ''));
if (!staff_csrf_verify($token)) {
  $go($return, 'csrf');
}

/* Validate page_id */
$pageId = (int)($_POST['page_id'] ?? 0);
if ($pageId < 1) {
  $go($return, 'invalid');
}

/* Detect “POST too large” (PHP drops $_POST/$_FILES silently) */
$cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($cl > 0 && empty($_FILES)) {
  $go($return, 'too_large');
}

/* Validate files exist */
$files = $_FILES['attachments'] ?? null;
if (!is_array($files) || (!isset($files['name']) && !isset($files['tmp_name']))) {
  $go($return, 'nofile');
}

/* DB */
$pdo = staff_pdo();
if (!$pdo instanceof PDO) {
  $go($return, 'error');
}

/* Delegate to private, authoritative upload engine */
if (!defined('PRIVATE_PATH') || !is_string(PRIVATE_PATH) || PRIVATE_PATH === '') {
  $go($return, 'error');
}

$fn = rtrim(PRIVATE_PATH, '/\\') . '/functions/page_attachments_upload.php';
if (!is_file($fn)) {
  $go($return, 'error');
}
require_once $fn;

if (!function_exists('mk_staff_upload_page_attachments')) {
  $go($return, 'error');
}

try {
  $res = mk_staff_upload_page_attachments($pdo, $pageId, $staffId, $files);

  $notice = 'sent';
  if (!is_array($res) || empty($res['ok'])) $notice = 'error';
  if (is_array($res) && !empty($res['errors'])) $notice = 'partial';

  $go($return, $notice);
} catch (Throwable $e) {
  $go($return, 'error');
}
