<?php
declare(strict_types=1);
require_once __DIR__ . '/../_init.php';

require_once __DIR__ . '/../_init.php';


/**
 * /public/attachments/file.php
 * Public secure attachment delivery (page_files), ONLY for published pages.
 *
 * Usage:
 *   /attachments/file.php?id=123
 *   /attachments/file.php?id=123&dl=1   (force download)
 *   /attachments/file.php?id=123&inline=1 (try inline)
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

/* Bootstrap (public side) */
if (!defined('APP_ROOT')) {
  define('APP_ROOT', dirname(__DIR__, 2) . '/app/mkomigbo');
}
// [patched] removed legacy initialize path reference
if (!is_file($init)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Init not found.\n";
  exit;
}
require_once $init;
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* Helpers (minimal) */
if (!function_exists('pf__table_exists')) {
  function pf__table_exists(PDO $pdo, string $table): bool {
    static $cache = [];
    $k = strtolower($table);
    if (array_key_exists($k, $cache)) return (bool)$cache[$k];
    $st = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
    $st->execute([$table]);
    $cache[$k] = (bool)$st->fetchColumn();
    return (bool)$cache[$k];
  }
}
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $k = strtolower($table . '.' . $column);
    if (array_key_exists($k, $cache)) return (bool)$cache[$k];
    $st = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
    $st->execute([$table, $column]);
    $cache[$k] = (bool)$st->fetchColumn();
    return (bool)$cache[$k];
  }
}
if (!function_exists('pf__clean_filename')) {
  function pf__clean_filename(string $name, string $fallback = 'download.bin'): string {
    $name = trim($name);
    if ($name === '') return $fallback;
    $name = str_replace(["\r","\n","\0"], ' ', $name);
    $name = preg_replace('/[^\pL\pN\.\-\_\(\)\[\]\s]+/u', '_', $name) ?? $name;
    $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
    $name = trim($name);
    return ($name !== '') ? $name : $fallback;
  }
}
if (!function_exists('pf__guess_mime')) {
  function pf__guess_mime(string $path): string {
    $mime = '';
    if (function_exists('finfo_open')) {
      $fi = @finfo_open(FILEINFO_MIME_TYPE);
      if ($fi) {
        $m = @finfo_file($fi, $path);
        if (is_string($m)) $mime = $m;
        @finfo_close($fi);
      }
    }
    if ($mime === '') $mime = 'application/octet-stream';
    return $mime;
  }
}
if (!function_exists('pf__stream_file')) {
  function pf__stream_file(string $diskPath, string $downloadName, string $mime, bool $asAttachment, bool $isPublicCache): void {
    if (!is_file($diskPath) || !is_readable($diskPath)) {
      http_response_code(404);
      header('Content-Type: text/plain; charset=utf-8');
      echo "File not found.\n";
      exit;
    }

    $size = filesize($diskPath);
    if (!is_int($size) || $size < 0) $size = 0;

    $mtime = @filemtime($diskPath);
    $etag = '"' . sha1($diskPath . '|' . (string)$size . '|' . (string)$mtime) . '"';

    header('X-Content-Type-Options: nosniff');
    header('Accept-Ranges: bytes');
    header('ETag: ' . $etag);
    if (is_int($mtime) && $mtime > 0) {
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    }

    if ($isPublicCache) {
      header('Cache-Control: public, max-age=86400');
    } else {
      header('Cache-Control: private, no-store, max-age=0');
      header('Pragma: no-cache');
      header('Expires: 0');
    }

    $dispType = $asAttachment ? 'attachment' : 'inline';
    $safeName = pf__clean_filename($downloadName, 'download.bin');
    $utf8 = rawurlencode($safeName);

    header("Content-Type: {$mime}");
    header("Content-Disposition: {$dispType}; filename=\"{$safeName}\"; filename*=UTF-8''{$utf8}");

    $range = (string)($_SERVER['HTTP_RANGE'] ?? '');
    if ($size > 0 && preg_match('/bytes=(\d*)-(\d*)/i', $range, $m)) {
      $start = ($m[1] !== '') ? (int)$m[1] : 0;
      $end   = ($m[2] !== '') ? (int)$m[2] : ($size - 1);

      if ($start < 0) $start = 0;
      if ($end >= $size) $end = $size - 1;
      if ($end < $start) {
        http_response_code(416);
        header("Content-Range: bytes */{$size}");
        exit;
      }

      $len = ($end - $start) + 1;
      http_response_code(206);
      header("Content-Range: bytes {$start}-{$end}/{$size}");
      header("Content-Length: {$len}");

      $fp = @fopen($diskPath, 'rb');
      if (!$fp) { http_response_code(500); exit; }
      @fseek($fp, $start);

      $buf = 1024 * 1024;
      $sent = 0;
      while (!feof($fp) && $sent < $len) {
        $left = $len - $sent;
        $read = ($left > $buf) ? $buf : $left;
        $data = @fread($fp, $read);
        if ($data === false || $data === '') break;
        echo $data;
        $sent += strlen($data);
        @flush();
      }
      @fclose($fp);
      exit;
    }

    header("Content-Length: {$size}");

    $fp = @fopen($diskPath, 'rb');
    if (!$fp) { http_response_code(500); exit; }

    $buf = 1024 * 1024;
    while (!feof($fp)) {
      $data = @fread($fp, $buf);
      if ($data === false) break;
      echo $data;
      @flush();
    }
    @fclose($fp);
    exit;
  }
}

/* DB */
$pdo = (function_exists('db') && db() instanceof PDO) ? db() : null;
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "DB not available.\n";
  exit;
}

/* Validate tables */
if (!pf__table_exists($pdo, 'page_files') || !pf__table_exists($pdo, 'pages')) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Not available.\n";
  exit;
}

/* Required columns */
foreach (['id','page_id','original_name','stored_name','file_path'] as $c) {
  if (!pf__column_exists($pdo, 'page_files', $c)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not available.\n";
    exit;
  }
}

/* Determine publish column on pages */
$has_is_public = pf__column_exists($pdo, 'pages', 'is_public');
$has_visible   = pf__column_exists($pdo, 'pages', 'visible');
$pub_col = $has_is_public ? 'is_public' : ($has_visible ? 'visible' : null);

if ($pub_col === null) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Public downloads disabled.\n";
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Bad request.\n";
  exit;
}

$hasMime = pf__column_exists($pdo, 'page_files', 'mime_type');
$sel = "pf.id, pf.page_id, pf.original_name, pf.stored_name, pf.file_path"
     . ($hasMime ? ", pf.mime_type" : "")
     . ", p.`{$pub_col}` AS pub_value"
     . " FROM page_files pf"
     . " JOIN pages p ON p.id = pf.page_id"
     . " WHERE pf.id = :id"
     . " LIMIT 1";

$st = $pdo->prepare("SELECT {$sel}");
$st->execute([':id' => $id]);
$row = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$row) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Not found.\n";
  exit;
}

/* Enforce: only published pages */
if ((int)($row['pub_value'] ?? 0) !== 1) {
  http_response_code(403);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Not authorized.\n";
  exit;
}

/* Resolve disk path safely */
$upload_root = rtrim(APP_ROOT . '/private/uploads/page_files', DIRECTORY_SEPARATOR);
$rootReal = (is_dir($upload_root)) ? realpath($upload_root) : false;
if ($rootReal === false) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Storage not configured.\n";
  exit;
}

$file_path = (string)($row['file_path'] ?? '');
$file_path = str_replace(["\0", "\\", "\r", "\n"], ['', '/', '', ''], $file_path);
$file_path = ltrim($file_path, '/');

$disk = $rootReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file_path);
$real = realpath($disk);

if ($real === false || strpos($real, $rootReal . DIRECTORY_SEPARATOR) !== 0) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
  echo "File missing.\n";
  exit;
}

$name = (string)($row['original_name'] ?? 'download.bin');
$mime = $hasMime ? (string)($row['mime_type'] ?? '') : '';
if ($mime === '') $mime = pf__guess_mime($real);

$dl = ((string)($_GET['dl'] ?? '') === '1');
$inline = ((string)($_GET['inline'] ?? '') === '1');
$asAttachment = $dl ? true : ($inline ? false : true);

/* Public endpoint: allow caching */
pf__stream_file($real, $name, $mime, $asAttachment, true);
