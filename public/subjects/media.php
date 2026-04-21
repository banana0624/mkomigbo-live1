<?php
declare(strict_types=1);

/**
 * /public/subjects/media.php  (LEGACY SHIM ONLY)
 *
 * Old style:
 *   /subjects/media.php?subject={subject}&page={page}&file={filename}[&dl=1]
 *
 * Canonical:
 *   /subjects/{subject}/{page}/media/{filename}[?dl=1]
 *
 * MUST NOT: stream, read links.json, query DB, validate allowlists.
 * DOES: 301 -> canonical.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

$norm = static function ($v): string {
  $s = is_string($v) ? $v : (string)$v;
  $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s) ?? $s;
  return trim($s);
};

$slug_ok = static function (string $s): bool {
  return ($s !== '') && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
};

$fail = static function (int $code, string $msg): void {
  if (!headers_sent()) {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
  }
  echo $msg . "\n";
  exit;
};

$subject = strtolower($norm($_GET['subject'] ?? ''));
$page    = strtolower($norm($_GET['page'] ?? ''));
$file    = $norm($_GET['file'] ?? '');
$dl      = $norm($_GET['dl'] ?? '');

if ($subject === '' || $page === '' || $file === '') $fail(400, 'Missing parameters.');
if (!$slug_ok($subject) || !$slug_ok($page)) $fail(404, 'Not found.');

$file = str_replace(["\r", "\n"], '', $file);
$file = trim($file);

if ($file === '') $fail(400, 'Invalid file.');

// file must be a single path segment (no traversal)
if (strpos($file, '/') !== false || strpos($file, '\\') !== false || strpos($file, '..') !== false) {
  $fail(400, 'Invalid file.');
}

// Optional extra hardening: filename-ish only
if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,240}$/', $file)) {
  $fail(400, 'Invalid file.');
}

$base = function_exists('url_for') ? (string)url_for('/') : '/';
$base = rtrim($base, '/');

$dest = $base
  . '/subjects/'
  . rawurlencode($subject) . '/'
  . rawurlencode($page) . '/media/'
  . rawurlencode($file);

// Preserve dl=1 only (drop everything else)
if ($dl === '1') $dest .= '?dl=1';

if (!headers_sent()) {
  http_response_code(301);
  header('Location: ' . $dest);
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: no-store');
}
exit;
