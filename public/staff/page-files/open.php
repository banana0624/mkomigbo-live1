<?php
declare(strict_types=1);

/**
 * /public/staff/page-files/open.php
 *
 * Staff OPEN attachment:
 * - Local: streams file from /public_html/lib/uploads/page_files/{page_id}/...
 *          with Content-Disposition: inline (browser open)
 * - External: re-validates URL via allowlist and 303 redirects (never fetches)
 *
 * POST-only + CSRF required.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }

/* Auth */
if (function_exists('require_staff')) {
  require_staff();
} elseif (function_exists('require_staff_login')) {
  require_staff_login();
} elseif (function_exists('mk_require_staff_login')) {
  mk_require_staff_login();
}

/* Helpers */
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('staff_csrf_verify')) {
  function staff_csrf_verify(string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $sess = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sess) || $sess === '' || $token === '') return false;
    return hash_equals($sess, $token);
  }
}
if (!function_exists('mk_staff_audit_try')) {
  function mk_staff_audit_try(PDO $pdo, string $action, array $meta = []): void {
    try {
      $st = $pdo->prepare("
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'staff_audit_log'
        LIMIT 1
      ");
      $st->execute();
      if (!$st->fetchColumn()) return;

      $user = $_SESSION['staff_email'] ?? ($_SESSION['email'] ?? ($_SESSION['user_email'] ?? ''));
      if (!is_string($user)) $user = '';

      $ip  = (string)($_SERVER['REMOTE_ADDR'] ?? '');
      $ua  = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
      $js  = json_encode($meta, JSON_UNESCAPED_SLASHES);

      $ins = $pdo->prepare("
        INSERT INTO staff_audit_log (action, actor, ip, user_agent, meta_json, created_at)
        VALUES (:action, :actor, :ip, :ua, :meta, NOW())
      ");
      $ins->execute([
        ':action' => $action,
        ':actor'  => $user,
        ':ip'     => $ip,
        ':ua'     => $ua,
        ':meta'   => is_string($js) ? $js : '{}',
      ]);
    } catch (Throwable $e) {
      // never break page open/download
    }
  }
}

if (!function_exists('mk_pagefile__normalize_external_url')) {
  /**
   * Normalize common “human-entered” URLs into a strict absolute URL.
   * - adds https:// when scheme missing (e.g. en.wikipedia.org/wiki/...)
   * - supports //host/path
   * - strips CR/LF
   * - rejects obviously unsafe schemes
   */
  function mk_pagefile__normalize_external_url(string $raw): string {
    $u = trim($raw);
    if ($u === '') return '';

    // kill header injection vectors early
    $u = str_replace(["\r", "\n"], '', $u);

    // tolerate protocol-relative
    if (strncmp($u, '//', 2) === 0) {
      $u = 'https:' . $u;
    }

    // if user stored host/path without scheme, add https://
    if (!preg_match('~^[a-zA-Z][a-zA-Z0-9+\-.]*://~', $u)) {
      // avoid turning relative paths into external URLs
      if ($u[0] === '/' || $u[0] === '\\') return '';

      $u = 'https://' . $u;
    }

    $p = @parse_url($u);
    if (!is_array($p) || empty($p['scheme']) || empty($p['host'])) return '';

    $scheme = strtolower((string)$p['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) return '';

    // remove surrounding dots/spaces, normalize host
    $host = strtolower(trim((string)$p['host'], " \t\n\r\0\x0B."));

    // rebuild a clean URL (keeps path/query/fragment as-is)
    $path = isset($p['path']) ? (string)$p['path'] : '/';
    if ($path === '') $path = '/';

    $query = isset($p['query']) ? ('?' . (string)$p['query']) : '';
    $frag  = isset($p['fragment']) ? ('#' . (string)$p['fragment']) : '';

    // encode spaces (common culprit with Wikipedia copy/paste)
    $path = str_replace(' ', '%20', $path);

    $port = isset($p['port']) ? (int)$p['port'] : 0;
    $portPart = ($port > 0 && !in_array($port, [80, 443], true)) ? (':' . $port) : '';

    return $scheme . '://' . $host . $portPart . $path . $query . $frag;
  }
}

/* CSRF */
$token = (string)($_POST['csrf_token'] ?? '');
if (!staff_csrf_verify($token)) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo "CSRF failed.\n";
  exit;
}

/* Inputs */
$fileId = (int)($_POST['file_id'] ?? 0);
$pageId = (int)($_POST['page_id'] ?? 0);
if ($fileId < 1 || $pageId < 1) {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Invalid request.\n";
  exit;
}

/* DB */
$pdo = (function_exists('db') && db() instanceof PDO) ? db() : null;
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "DB not available.\n";
  exit;
}

/* Fetch row (must belong to this page_id) */
$cols = ['id','page_id','original_name','stored_name','file_path','stored_path','mime_type','file_size','created_at'];
foreach (['is_external','external_url','external_host'] as $c) {
  try {
    $stc = $pdo->prepare("
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='page_files' AND COLUMN_NAME=?
      LIMIT 1
    ");
    $stc->execute([$c]);
    if ($stc->fetchColumn()) $cols[] = $c;
  } catch (Throwable $e) {}
}

$st = $pdo->prepare("
  SELECT " . implode(',', array_unique($cols)) . "
  FROM page_files
  WHERE id = :id AND page_id = :pid
  LIMIT 1
");
$st->execute([':id' => $fileId, ':pid' => $pageId]);
$row = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$row) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Attachment not found.\n";
  exit;
}

$extUrlRaw = trim((string)($row['external_url'] ?? ''));
$isExternalFlag = !empty($row['is_external'] ?? 0);

/**
 * IMPORTANT:
 * If is_external is missing/false but external_url is present, treat as external.
 * This prevents “works for YouTube but not Wikipedia” when some rows were saved differently.
 */
$isExternal = $isExternalFlag || ($extUrlRaw !== '');

if ($isExternal) {
  if ($extUrlRaw === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "External URL missing.\n";
    exit;
  }

  $extUrl = mk_pagefile__normalize_external_url($extUrlRaw);
  if ($extUrl === '') {
    mk_staff_audit_try($pdo, 'page_file.open.external_blocked', [
      'file_id' => $fileId,
      'page_id' => $pageId,
      'url'     => $extUrlRaw,
      'reason'  => 'normalize_failed',
    ]);
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "External URL blocked.\n";
    exit;
  }

  /* Same external validator as download.php */
  if (!defined('PRIVATE_PATH') || !is_string(PRIVATE_PATH) || PRIVATE_PATH === '') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "PRIVATE_PATH missing.\n";
    exit;
  }

  $fn = rtrim(PRIVATE_PATH, '/\\') . '/functions/page_attachments_external.php';
  if (!is_file($fn)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "External validation function missing.\n";
    exit;
  }
  require_once $fn;

  if (!function_exists('mk_pagefile__validate_external_url')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "External validator missing.\n";
    exit;
  }

  $v = mk_pagefile__validate_external_url($extUrl);
  if (empty($v['ok']) || empty($v['url'])) {
    mk_staff_audit_try($pdo, 'page_file.open.external_blocked', [
      'file_id' => $fileId,
      'page_id' => $pageId,
      'url'     => $extUrl,
      'raw'     => $extUrlRaw,
      'reason'  => is_array($v) && isset($v['reason']) ? (string)$v['reason'] : 'validator_reject',
    ]);
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "External URL blocked.\n";
    exit;
  }

  $target = str_replace(["\r","\n"], '', (string)$v['url']);

  mk_staff_audit_try($pdo, 'page_file.open.external_redirect', [
    'file_id' => $fileId,
    'page_id' => $pageId,
    'url'     => $target,
  ]);

  // POST -> redirect to GET
  header('Location: ' . $target, true, 303);
  exit;
}

/* Local file open (inline) */
$storedPath = trim((string)($row['stored_path'] ?? ''));
$filePath   = trim((string)($row['file_path'] ?? ''));

$relative = $storedPath !== '' ? $storedPath : $filePath;
if ($relative === '') {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Stored path missing.\n";
  exit;
}

/*
  Your uploads live outside /public/:
  /public_html/lib/uploads/page_files/{page_id}/...
*/
$uploadsRoot = dirname(__DIR__, 2) . '/lib/uploads/page_files'; // /public_html/lib/uploads/page_files
$uploadsRootReal = realpath($uploadsRoot);

if (!$uploadsRootReal || !is_dir($uploadsRootReal)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Uploads root missing.\n";
  exit;
}

/* Build absolute path */
$abs = $relative;
if ($abs[0] !== '/' && $abs[0] !== '\\') {
  $abs = $uploadsRootReal . '/' . ltrim($abs, '/\\');
}

$real = realpath($abs);
if (!$real || !is_file($real)) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
  echo "File missing on disk.\n";
  exit;
}

/* Boundary check: must remain under uploads root */
$rootNorm = rtrim(str_replace('\\','/',$uploadsRootReal), '/');
$realNorm = str_replace('\\','/',$real);
if (strpos($realNorm, $rootNorm . '/') !== 0) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Access denied.\n";
  exit;
}

$openName = trim((string)($row['original_name'] ?? ''));
if ($openName === '') $openName = basename($real);

$mime = trim((string)($row['mime_type'] ?? ''));
if ($mime === '') $mime = 'application/octet-stream';

mk_staff_audit_try($pdo, 'page_file.open.local_inline', [
  'file_id' => $fileId,
  'page_id' => $pageId,
  'name'    => $openName,
  'mime'    => $mime,
]);

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . str_replace('"','', $openName) . '"');
header('Content-Length: ' . (string)filesize($real));

$fp = fopen($real, 'rb');
if (!$fp) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Failed to read file.\n";
  exit;
}
fpassthru($fp);
fclose($fp);
exit;
