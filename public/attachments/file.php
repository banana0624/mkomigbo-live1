<?php
declare(strict_types=1);

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

function mk_fail(int $code, string $msg): void {
  http_response_code($code);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $msg;
  exit;
}

function mk_is_staff_request(): bool {
  if (function_exists('staff_is_logged_in')) return (bool)staff_is_logged_in();
  if (function_exists('is_staff_logged_in')) return (bool)is_staff_logged_in();

  if (session_status() === PHP_SESSION_ACTIVE) {
    if (!empty($_SESSION['staff_user_id'])) return true;
    if (!empty($_SESSION['staff_id'])) return true;
    if (!empty($_SESSION['is_staff'])) return true;
  }
  return false;
}

function mk_require_attachment_access(array $row): void {
  if (function_exists('attachments_public_allowed')) {
    if ((bool)attachments_public_allowed($row) === true) return;
  }
  if (mk_is_staff_request()) return;
  mk_fail(403, 'Forbidden');
}

function mk_safe_external_url(string $u): string {
  $u = trim($u);
  if ($u === '') return '';
  if (preg_match('/[\r\n\0]/', $u)) return '';
  $p = @parse_url($u);
  if (!is_array($p)) return '';
  $scheme = strtolower((string)($p['scheme'] ?? ''));
  if (!in_array($scheme, ['http', 'https'], true)) return '';
  if (empty($p['host'])) return '';
  return $u;
}

function mk_header_filename(string $name): array {
  $name = str_replace(["\r", "\n"], '', trim($name));
  if ($name === '') $name = 'download';

  $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
  $ascii = trim((string)$ascii, '._-');
  if ($ascii === '') $ascii = 'download';

  $utf8 = rawurlencode($name);

  return [
    'ascii' => $ascii,
    'utf8'  => $utf8,
  ];
}

function mk_send_file(string $absPath, string $downloadName, string $mime): void {
  if (!is_file($absPath) || !is_readable($absPath)) mk_fail(404, 'Not found');

  $fn = mk_header_filename($downloadName);

  header('X-Content-Type-Options: nosniff');
  header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
  header('Content-Disposition: attachment; filename="' . $fn['ascii'] . '"; filename*=UTF-8\'\'' . $fn['utf8']);
  header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
  header('Pragma: no-cache');

  $size = @filesize($absPath);
  if (is_int($size) && $size >= 0) header('Content-Length: ' . $size);

  $fp = @fopen($absPath, 'rb');
  if (!$fp) mk_fail(500, 'Cannot open file');
  while (!feof($fp)) {
    $buf = fread($fp, 1048576);
    if ($buf === false) break;
    echo $buf;
  }
  fclose($fp);
  exit;
}

/** Main */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) mk_fail(400, 'Bad request');

if (!function_exists('db')) mk_fail(500, 'DB not available');
$pdo = db();
if (!($pdo instanceof PDO)) mk_fail(500, 'DB not available');

$st = $pdo->prepare("SELECT * FROM page_files WHERE id = :id LIMIT 1");
$st->execute([':id' => $id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) mk_fail(404, 'Not found');

mk_require_attachment_access($row);

$isExternal = ((int)($row['is_external'] ?? 0) === 1);
if ($isExternal) {
  $u = mk_safe_external_url((string)($row['external_url'] ?? ''));
  if ($u === '') mk_fail(400, 'Invalid external URL');
  header('Location: ' . $u, true, 302);
  exit;
}

/**
 * Choose best available local path.
 * Priority:
 * - stored_path (recommended)
 * - file_path (legacy)
 */
$rel = trim((string)($row['stored_path'] ?? ''));
if ($rel === '') $rel = trim((string)($row['file_path'] ?? ''));

if ($rel === '') mk_fail(404, 'Not found');

$base = realpath(__DIR__ . '/../../private/uploads');
if ($base === false) mk_fail(500, 'Uploads base missing');

// normalize relative path and block traversal
$rel = ltrim(str_replace(['\\', "\0"], ['/', ''], $rel), '/');
if ($rel === '' || strpos($rel, '..') !== false) mk_fail(400, 'Bad path');

$abs = realpath($base . '/' . $rel);
if ($abs === false || strpos($abs, $base) !== 0) mk_fail(404, 'Not found');

$download = (string)($row['original_name'] ?? '');
if ($download === '') $download = (string)($row['stored_name'] ?? '');
if ($download === '') $download = 'download';

$mime = (string)($row['mime_type'] ?? '');
mk_send_file($abs, $download, $mime);
