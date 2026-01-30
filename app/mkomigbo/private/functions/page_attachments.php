<?php
declare(strict_types=1);

/**
 * /private/functions/page_attachments.php
 * Secure attachment resolver + streamer (read-only).
 *
 * Contract:
 * - Input is a DB row from page_files with keys:
 *   page_id, stored_name, filename, mime, is_external, external_url
 *
 * Security goals:
 * - Prevent path traversal and boundary escapes
 * - Enforce safe external redirects (no SSRF)
 * - Enforce content-type allowlist and safe headers
 * - Stream large files safely (Range support)
 */

if (!defined('PRIVATE_PATH') || !is_string(PRIVATE_PATH) || PRIVATE_PATH === '') {
  throw new RuntimeException('PRIVATE_PATH is not defined.');
}

/* ---------------------------------------------------------
   Policy knobs (safe defaults)
--------------------------------------------------------- */

/**
 * External URL allowlist.
 * - Empty array means: allow any valid http/https URL (still blocks private/local hosts below).
 * - To lock down, add allowed hostnames:
 *   ['mkomigbo.com', 'www.mkomigbo.com', 'cdn.example.com']
 */
function mk_attachment_external_allowed_hosts(): array {
  return [];
}

/**
 * Allowed MIME types for INLINE display.
 * Everything else becomes download (attachment).
 */
function mk_attachment_inline_mimes(): array {
  return [
    'application/pdf',
    'image/png',
    'image/jpeg',
    'image/webp',
    'image/gif',
    'text/plain',
  ];
}

/**
 * Allowed MIME types overall.
 * If sniffed mime is not in this list, force octet-stream.
 */
function mk_attachment_allowed_mimes(): array {
  return [
    'application/pdf',
    'image/png',
    'image/jpeg',
    'image/webp',
    'image/gif',
    'text/plain',
    'audio/mpeg',
    'audio/mp3',
    'audio/wav',
    'audio/x-wav',
    'audio/ogg',
    'video/mp4',
    'video/webm',
    'application/zip',
    'application/x-zip-compressed',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  ];
}

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */

function mk_page_attachment_base_dir(int $pageId): string {
  $pageId = max(0, (int)$pageId);
  return rtrim(PRIVATE_PATH, '/\\') . '/uploads/pages/' . $pageId;
}

function mk_page_attachment_path(int $pageId, string $storedName): string {
  $base = mk_page_attachment_base_dir($pageId);
  return $base . '/' . basename($storedName);
}

function mk_attachment_fail(int $code, string $msg = ''): never {
  http_response_code($code);
  header('Content-Type: text/plain; charset=utf-8');
  if ($msg !== '') echo $msg;
  exit;
}

function mk_attachment_resolve_local_path(int $pageId, string $storedName): string {
  $pageId = max(1, (int)$pageId);

  $storedName = trim((string)$storedName);
  if ($storedName === '') mk_attachment_fail(404, "File not found.\n");

  if (!preg_match('~^[A-Za-z0-9][A-Za-z0-9._-]{0,240}$~', basename($storedName))) {
    mk_attachment_fail(404, "File not found.\n");
  }

  $base = mk_page_attachment_base_dir($pageId);
  $target = mk_page_attachment_path($pageId, $storedName);

  $baseReal = realpath($base);
  $fileReal = realpath($target);

  if ($baseReal === false || $fileReal === false) {
    mk_attachment_fail(404, "File not found.\n");
  }

  $baseReal = rtrim(str_replace('\\', '/', $baseReal), '/') . '/';
  $fileRealNorm = str_replace('\\', '/', $fileReal);

  if (strpos($fileRealNorm, $baseReal) !== 0) {
    mk_attachment_fail(404, "File not found.\n");
  }

  if (!is_file($fileRealNorm) || !is_readable($fileRealNorm)) {
    mk_attachment_fail(404, "File not found.\n");
  }

  return $fileRealNorm;
}

function mk_attachment_validate_external_url(?string $url): string {
  $url = trim((string)$url);
  if ($url === '') mk_attachment_fail(404, "Link not available.\n");

  $url = filter_var($url, FILTER_SANITIZE_URL);
  if (!is_string($url) || $url === '') mk_attachment_fail(404, "Link not available.\n");

  $parts = parse_url($url);
  if (!is_array($parts)) mk_attachment_fail(404, "Link not available.\n");

  $scheme = strtolower((string)($parts['scheme'] ?? ''));
  if ($scheme !== 'http' && $scheme !== 'https') mk_attachment_fail(404, "Link not available.\n");

  if (!filter_var($url, FILTER_VALIDATE_URL)) mk_attachment_fail(404, "Link not available.\n");

  $host = strtolower((string)($parts['host'] ?? ''));
  if ($host === '') mk_attachment_fail(404, "Link not available.\n");

  $allow = mk_attachment_external_allowed_hosts();
  if (is_array($allow) && count($allow) > 0 && !in_array($host, $allow, true)) {
    mk_attachment_fail(404, "Link not available.\n");
  }

  if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
    mk_attachment_fail(404, "Link not available.\n");
  }

  if (filter_var($host, FILTER_VALIDATE_IP)) {
    $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
    if (!filter_var($host, FILTER_VALIDATE_IP, $flags)) {
      mk_attachment_fail(404, "Link not available.\n");
    }
  }

  return $url;
}

function mk_attachment_sniff_mime(string $path): string {
  $mime = '';
  if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
      $m = finfo_file($fi, $path);
      finfo_close($fi);
      if (is_string($m)) $mime = trim($m);
    }
  }
  return $mime !== '' ? $mime : 'application/octet-stream';
}

function mk_attachment_safe_filename(string $name, string $fallback = 'download'): string {
  $name = trim((string)$name);
  if ($name === '') $name = $fallback;

  $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?? $name;
  $name = str_replace(['"', "'"], '', $name);
  $name = trim($name);

  if ($name === '') $name = $fallback;
  return $name;
}

/* ---------------------------------------------------------
   Main streamer (Range capable)
--------------------------------------------------------- */
function mk_page_attachment_stream(array $file): never {
  $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
  $isHead = ($method === 'HEAD');

  // Never allow caches to store attachment responses (safe default)
  header('Cache-Control: private, no-store, no-cache, must-revalidate');
  header('Pragma: no-cache');
  header('Expires: 0');

  $isExternal = !empty($file['is_external']);
  if ($isExternal) {
    $url = mk_attachment_validate_external_url($file['external_url'] ?? null);
    header('Location: ' . str_replace(["\r", "\n"], '', $url), true, 302);
    exit;
  }

  $pageId = (int)($file['page_id'] ?? 0);
  $stored = (string)($file['stored_name'] ?? '');
  $orig   = (string)($file['filename'] ?? 'download');

  $path = mk_attachment_resolve_local_path($pageId, $stored);

  $sniffed = mk_attachment_sniff_mime($path);
  $allowed = mk_attachment_allowed_mimes();
  $mime = in_array($sniffed, $allowed, true) ? $sniffed : 'application/octet-stream';

  $inlineAllowed = mk_attachment_inline_mimes();
  $disposition = in_array($mime, $inlineAllowed, true) ? 'inline' : 'attachment';

  $dlName = mk_attachment_safe_filename($orig, basename($stored) ?: 'download');

  // Security headers
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: no-referrer');
  header('X-Frame-Options: SAMEORIGIN');

  if ($disposition === 'inline') {
    header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; media-src 'self'; style-src 'unsafe-inline';");
  }

  $size = @filesize($path);
  if (!is_int($size) || $size < 0) $size = null;

  header('Content-Type: ' . $mime);
  header('Content-Disposition: ' . $disposition . '; filename="' . $dlName . '"');
  header('Accept-Ranges: bytes');

  // Range support (best-effort)
  $range = (string)($_SERVER['HTTP_RANGE'] ?? '');
  $start = 0;
  $end = ($size !== null) ? ($size - 1) : null;

  if ($size !== null && $range !== '' && preg_match('/bytes=(\d*)-(\d*)/i', $range, $m)) {
    $r1 = ($m[1] !== '') ? (int)$m[1] : null;
    $r2 = ($m[2] !== '') ? (int)$m[2] : null;

    if ($r1 !== null && $r1 >= 0 && $r1 < $size) {
      $start = $r1;
    }
    if ($r2 !== null && $r2 >= $start && $r2 < $size) {
      $end = $r2;
    }

    if ($start > 0 || ($end !== null && $end < ($size - 1))) {
      http_response_code(206);
      header("Content-Range: bytes {$start}-{$end}/{$size}");
    }
  }

  if ($size !== null && $end !== null) {
    $len = ($end - $start) + 1;
    header('Content-Length: ' . $len);
  }

  if ($isHead) exit;

  while (ob_get_level() > 0) { @ob_end_clean(); }

  $fp = @fopen($path, 'rb');
  if (!$fp) mk_attachment_fail(404, "File not found.\n");

  if ($start > 0) {
    @fseek($fp, $start);
  }

  $remaining = ($size !== null && $end !== null) ? (($end - $start) + 1) : null;
  $chunk = 1024 * 1024; // 1MB

  while (!feof($fp)) {
    if ($remaining !== null && $remaining <= 0) break;

    $read = ($remaining !== null) ? min($chunk, $remaining) : $chunk;
    $buf = fread($fp, $read);
    if ($buf === false || $buf === '') break;

    echo $buf;

    if ($remaining !== null) {
      $remaining -= strlen($buf);
    }
  }

  fclose($fp);
  exit;
}
