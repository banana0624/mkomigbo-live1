<?php
declare(strict_types=1);

/**
 * /public/media.php
 * Central safe file delivery endpoint (reusable across the project).
 *
 * Primary (Subjects) query (supported):
 *   /media.php?subject={slug}&page={slug}&file={filename}&dl=0|1
 *
 * Legacy alias query (also supported):
 *   /media.php?s={subjectSlug}&p={pageSlug}&f={filename}&in=0|1
 *
 * Storage roots:
 * - Subjects PRIVATE (preferred):
 *     APP_ROOT/private/subjects-media/{subject}/{page}/{file}
 *   or PRIVATE_PATH/subjects-media/...
 *
 * - Optional Subjects PUBLIC (supported for older content):
 *     PUBLIC_PATH/subjects-media/{subject}/{page}/{file}
 *
 * Choose root via:
 *   &scope=private  (default)
 *   &scope=public
 *
 * Security:
 * - strict slug validation
 * - strict filename validation (no traversal, no slashes)
 * - block dangerous extensions ALWAYS
 * - allowlist extensions
 * - realpath boundary enforcement
 * - content-type sniff via finfo when available
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/_init.php';

/* PHP 7.x compatibility */
if (!function_exists('str_starts_with')) {
  function str_starts_with(string $haystack, string $needle): bool {
    return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
  }
}
if (!function_exists('str_contains')) {
  function str_contains(string $haystack, string $needle): bool {
    return $needle === '' || strpos($haystack, $needle) !== false;
  }
}

$fail = static function(int $code, string $msg = 'Not found.'): void {
  http_response_code($code);
  header('Content-Type: text/plain; charset=utf-8');
  echo $msg;
  exit;
};

$slug_ok = static function(string $s): bool {
  return ($s !== '') && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
};

$filename_ok = static function(string $name): bool {
  if ($name === '' || strlen($name) > 240) return false;
  if (str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, "\0")) return false;
  if (str_contains($name, '..')) return false;

  // allow common safe filename chars
  if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._ -]{0,230}$/', $name)) return false;

  return true;
};

/* Accept both param styles */
$subject = '';
$page    = '';
$file    = '';
$dl      = null;     // '0'|'1' or null
$inline  = null;     // 0|1 or null (legacy)
$scope   = '';       // 'private'|'public'

if (isset($_GET['subject']) && is_scalar($_GET['subject'])) $subject = strtolower(trim((string)$_GET['subject']));
if ($subject === '' && isset($_GET['s']) && is_scalar($_GET['s'])) $subject = strtolower(trim((string)$_GET['s']));

if (isset($_GET['page']) && is_scalar($_GET['page'])) $page = strtolower(trim((string)$_GET['page']));
if ($page === '' && isset($_GET['p']) && is_scalar($_GET['p'])) $page = strtolower(trim((string)$_GET['p']));

if (isset($_GET['file']) && is_scalar($_GET['file'])) $file = trim((string)$_GET['file']);
if ($file === '' && isset($_GET['f']) && is_scalar($_GET['f'])) $file = trim((string)$_GET['f']);

if (isset($_GET['dl']) && is_scalar($_GET['dl'])) $dl = trim((string)$_GET['dl']);      // 0|1
if (isset($_GET['in'])) $inline = (int)$_GET['in'];                                     // 0|1

if (isset($_GET['scope']) && is_scalar($_GET['scope'])) $scope = strtolower(trim((string)$_GET['scope']));
if ($scope !== 'public') $scope = 'private'; // default

if (!$slug_ok($subject) || !$slug_ok($page) || !$filename_ok($file)) {
  $fail(404);
}

/* Always block dangerous extensions */
$ext = strtolower((string)pathinfo($file, PATHINFO_EXTENSION));
if ($ext === '' || preg_match('/^(php|phtml|phar|cgi|pl|py|js|html|htm)$/i', $ext)) {
  $fail(404);
}

/* Allowlist (keep aligned with attachments engine) */
$allowed = [
  'jpg','jpeg','png','webp','gif','svg',
  'mp4','webm','mov','m4v',
  'mp3','wav','ogg','m4a',
  'pdf','txt','md','csv',
  'doc','docx','ppt','pptx','xls','xlsx',
  'zip'
];
if (!in_array($ext, $allowed, true)) {
  $fail(404);
}

/* Resolve base root */
$base = '';

if ($scope === 'private') {
  if (defined('PRIVATE_PATH') && (string)PRIVATE_PATH !== '') {
    $base = rtrim((string)PRIVATE_PATH, "/\\") . '/subjects-media';
  } elseif (defined('APP_ROOT') && (string)APP_ROOT !== '') {
    $base = rtrim((string)APP_ROOT, "/\\") . '/private/subjects-media';
  }
} else { // public
  if (defined('PUBLIC_PATH') && (string)PUBLIC_PATH !== '') {
    $base = rtrim((string)PUBLIC_PATH, "/\\") . '/subjects-media';
  } else {
    $base = rtrim(dirname(__DIR__), "/\\") . '/public/subjects-media';
  }
}

if ($base === '' || !is_dir($base)) {
  $fail(404);
}

$target = $base . '/' . $subject . '/' . $page . '/' . $file;

$real_base = @realpath($base);
$real_file = @realpath($target);

if (!is_string($real_base) || $real_base === '' || !is_string($real_file) || $real_file === '') {
  $fail(404);
}
$real_base = rtrim(str_replace('\\', '/', $real_base), '/') . '/';
$real_file_norm = str_replace('\\', '/', $real_file);

if (!str_starts_with($real_file_norm, $real_base)) {
  $fail(404);
}
if (!is_file($real_file) || !is_readable($real_file)) {
  $fail(404);
}

$size = (int)@filesize($real_file);
if ($size <= 0) {
  $fail(404);
}

/* Content-Type */
$mime = 'application/octet-stream';
try {
  if (class_exists('finfo')) {
    $fi = new finfo(FILEINFO_MIME_TYPE);
    $m = $fi->file($real_file);
    if (is_string($m) && $m !== '') $mime = $m;
  }
} catch (Throwable $e) {
  // ignore
}

/* Disposition:
 * - Default inline unless dl=1 OR legacy in=0
 */
$as_attachment = false;
if ($dl !== null) {
  $as_attachment = ($dl === '1');
} elseif ($inline !== null) {
  $as_attachment = ((int)$inline !== 1);
}

$disp = $as_attachment ? 'attachment' : 'inline';
$safe_name = str_replace(["\r","\n",'"'], '', basename($file));

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)$size);
header('Content-Disposition: ' . $disp . '; filename="' . $safe_name . '"');

/* Cache (safe) */
header('Cache-Control: public, max-age=86400');

@readfile($real_file);
exit;
