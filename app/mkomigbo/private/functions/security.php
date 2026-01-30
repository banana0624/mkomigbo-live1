<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/functions/security.php
 *
 * Centralized security + performance helpers:
 * - Deterministic public caching headers for sessionless public GET/HEAD
 * - Lazy sessions (only when you actually need CSRF/forms/auth)
 * - CSRF helpers
 * - Safe output helpers
 * - Rate limiting (APCu optional)
 * - Upload validation + quarantine/approve pipeline
 * - Operational logs
 *
 * IMPORTANT LOAD ORDER (belongs in /public/_init.php, not here):
 *   security.php MUST load before csrf.php
 */

/* ---------------------------------------------------------
   Internal helpers (safe, no redeclare)
--------------------------------------------------------- */
if (!function_exists('mk_https_request')) {
  function mk_https_request(): bool {
    return
      (!empty($_SERVER['HTTPS']) && strtolower((string)($_SERVER['HTTPS'])) !== 'off')
      || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443')
      || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
  }
}

if (!function_exists('mk__session_active')) {
  function mk__session_active(): bool {
    return function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE;
  }
}

if (!function_exists('mk__session_id_present')) {
  function mk__session_id_present(): bool {
    return function_exists('session_id') && (string)session_id() !== '';
  }
}

if (!function_exists('mk__has_any_cookie')) {
  /**
   * Conservative policy:
   * - If ANY cookie exists, do not public-cache.
   */
  function mk__has_any_cookie(): bool {
    if (!empty($_SERVER['HTTP_COOKIE'])) return true;
    if (isset($_COOKIE) && is_array($_COOKIE) && count($_COOKIE) > 0) return true;
    return false;
  }
}

if (!function_exists('mk_client_ip')) {
  function mk_client_ip(): string
  {
    // Conservative: prefer REMOTE_ADDR; do not trust XFF unless you control the proxy chain.
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return $ip !== '' ? $ip : '0.0.0.0';
  }
}

if (!function_exists('mk_client_ua')) {
  function mk_client_ua(): string
  {
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '') return 'ua:none';
    // Clamp to prevent huge keys/logs
    return 'ua:' . substr($ua, 0, 160);
  }
}

/* ---------------------------------------------------------
   Cache debug toggle
--------------------------------------------------------- */
if (!function_exists('mk__cache_debug_enabled')) {
  function mk__cache_debug_enabled(): bool {
    if (defined('MK_CACHE_DEBUG') && MK_CACHE_DEBUG === true) return true;
    $v = $_GET['__cache_debug'] ?? null;
    if (is_string($v)) $v = trim($v);
    return ($v === '1' || $v === 'true' || $v === 'yes');
  }
}

/* ---------------------------------------------------------
   Deterministic public cache headers
--------------------------------------------------------- */
if (!function_exists('mk_public_cache_headers')) {
  function mk_public_cache_headers(int $seconds = 300): void
  {
    $dbg = mk__cache_debug_enabled();

    if ($dbg && !headers_sent()) {
      header('X-MK-Cache-Debug: enter');
    }

    if (headers_sent($file, $line)) {
      if ($dbg) {
        error_log('[MK_CACHE] headers_sent at ' . basename((string)$file) . ':' . (int)$line);
      }
      return;
    }

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($seconds < 0) $seconds = 0;
    if ($seconds > 86400) $seconds = 86400;

    if (function_exists('header_remove')) {
      header_remove('Cache-Control');
      header_remove('Pragma');
      header_remove('Expires');
      header_remove('ETag');
      header_remove('Last-Modified');
    }

    if (!in_array($method, ['GET', 'HEAD'], true)) {
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Pragma: no-cache');
      header('Expires: 0');
      header('Vary: Accept-Encoding');

      if ($dbg) header('X-MK-Cache-Policy: no-store (non-idempotent)');
      return;
    }

    $hasCookie     = mk__has_any_cookie();
    $sessionActive = mk__session_active();
    $hasSessionId  = mk__session_id_present();

    if ($hasCookie || $sessionActive || $hasSessionId) {
      header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
      header('Pragma: no-cache');
      header('Expires: 0');
      header('Vary: Accept-Encoding');

      if ($dbg) {
        header('X-MK-Cache-Policy: no-store (cookie/session)');
        header('X-MK-Cache-Has-Cookie: ' . ($hasCookie ? '1' : '0'));
        header('X-MK-Cache-Session-Active: ' . ($sessionActive ? '1' : '0'));
        header('X-MK-Cache-Session-Id: ' . ($hasSessionId ? '1' : '0'));
      }
      return;
    }

    header('Cache-Control: public, max-age=' . $seconds . ', s-maxage=' . $seconds);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
    header('Vary: Accept-Encoding');

    if ($dbg) header('X-MK-Cache-Policy: public');
  }
}

/* ---------------------------------------------------------
   Optional: force no-store for sensitive pages
--------------------------------------------------------- */
if (!function_exists('mk_no_store_headers')) {
  function mk_no_store_headers(): void
  {
    if (headers_sent()) return;

    if (function_exists('header_remove')) {
      header_remove('Cache-Control');
      header_remove('Pragma');
      header_remove('Expires');
      header_remove('ETag');
      header_remove('Last-Modified');
      header_remove('Vary');
    }

    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Vary: Accept-Encoding');
  }
}

/* ---------------------------------------------------------
   Lazy session start
--------------------------------------------------------- */
if (!function_exists('mk_ensure_session')) {
  function mk_ensure_session(): void
  {
    if (mk__session_active()) return;

    if (function_exists('mk_session_start')) {
      mk_session_start(true);
      if (mk__session_active()) return;
    }

    if (headers_sent()) return;

    $https = mk_https_request();
    @session_set_cookie_params([
      'lifetime' => 0,
      'path'     => '/',
      'secure'   => $https ? true : false,
      'httponly' => true,
      'samesite' => 'Lax',
    ]);

    @session_start();
  }
}

/* --------------------
   CSRF Protection (lazy-session)
-------------------- */
if (!function_exists('csrf_token')) {
  function csrf_token(): string
  {
    mk_ensure_session();
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
      $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf'];
  }
}

if (!function_exists('csrf_verify')) {
  function csrf_verify($token): bool
  {
    mk_ensure_session();
    $sess = (string)($_SESSION['csrf'] ?? '');
    $tok  = is_string($token) ? $token : (string)($token ?? '');
    if ($sess === '' || $tok === '') return false;
    return hash_equals($sess, $tok);
  }
}

/* --------------------
   Safe Output
-------------------- */
if (!function_exists('e')) {
  function e($text): string
  {
    return htmlspecialchars((string)($text ?? ''), ENT_QUOTES, 'UTF-8');
  }
}

/* --------------------
   Rate Limiting (APCu optional)
-------------------- */
if (!function_exists('mk_rate_limit_key')) {
  function mk_rate_limit_key(string $key): string
  {
    $base = 'mkrl:' . $key;
    $ip   = mk_client_ip();
    $ua   = mk_client_ua();
    return $base . '|ip:' . $ip . '|' . $ua;
  }
}

if (!function_exists('rate_limit')) {
  function rate_limit(string $key, int $max, int $windowSeconds): bool
  {
    if ($max <= 0 || $windowSeconds <= 0) return true;

    if (!function_exists('apcu_fetch') || !function_exists('apcu_store')) {
      return true;
    }

    $key = mk_rate_limit_key($key);
    $now = time();

    $bucket = apcu_fetch($key);
    if (!is_array($bucket) || !isset($bucket['count'], $bucket['reset']) || (int)$bucket['reset'] <= $now) {
      $bucket = ['count' => 0, 'reset' => $now + $windowSeconds];
    }

    if ((int)$bucket['count'] >= $max) return false;

    $bucket['count'] = (int)$bucket['count'] + 1;
    apcu_store($key, $bucket, $windowSeconds);
    return true;
  }
}

/* --------------------
   File Upload Validation Helpers
-------------------- */
if (!function_exists('upload_allowed_mimes')) {
  function upload_allowed_mimes(): array
  {
    // Strong allowlist: broad coverage without “anything goes”.
    return [
      // Images
      'image/png'                => 'png',
      'image/jpeg'               => 'jpg',
      'image/webp'               => 'webp',
      'image/gif'                => 'gif',

      // Documents
      'application/pdf'          => 'pdf',
      'text/plain'               => 'txt',
      'text/csv'                 => 'csv',
      'application/json'         => 'json',
      'application/xml'          => 'xml',
      'text/xml'                 => 'xml',
      'application/rtf'          => 'rtf',
      'application/epub+zip'     => 'epub',

      // Office (common)
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 'xlsx',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation'=> 'pptx',

      // Audio
      'audio/mpeg'               => 'mp3',
      'audio/mp4'                => 'm4a',
      'audio/ogg'                => 'ogg',
      'audio/wav'                => 'wav',
      'audio/webm'               => 'webm',

      // Video
      'video/mp4'                => 'mp4',
      'video/webm'               => 'webm',
      'video/ogg'                => 'ogv',

      // Archives (optional; keep if you really use it)
      'application/zip'          => 'zip',
    ];
  }
}

if (!function_exists('upload_size_limits')) {
  function upload_size_limits(): array
  {
    return [
      // Images
      'image/png'       => 12 * 1024 * 1024,
      'image/jpeg'      => 12 * 1024 * 1024,
      'image/webp'      => 12 * 1024 * 1024,
      'image/gif'       => 8  * 1024 * 1024,

      // Documents
      'application/pdf' => 30 * 1024 * 1024,
      'text/plain'      => 3  * 1024 * 1024,
      'text/csv'        => 8  * 1024 * 1024,
      'application/json'=> 5  * 1024 * 1024,
      'application/xml' => 5  * 1024 * 1024,
      'text/xml'        => 5  * 1024 * 1024,
      'application/rtf' => 10 * 1024 * 1024,
      'application/epub+zip' => 30 * 1024 * 1024,
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 25 * 1024 * 1024,
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 25 * 1024 * 1024,
      'application/vnd.openxmlformats-officedocument.presentationml.presentation'=> 35 * 1024 * 1024,

      // Audio
      'audio/mpeg'      => 80 * 1024 * 1024,
      'audio/mp4'       => 80 * 1024 * 1024,
      'audio/ogg'       => 80 * 1024 * 1024,
      'audio/wav'       => 120 * 1024 * 1024,
      'audio/webm'      => 80 * 1024 * 1024,

      // Video
      'video/mp4'       => 250 * 1024 * 1024,
      'video/webm'      => 250 * 1024 * 1024,
      'video/ogg'       => 250 * 1024 * 1024,

      // Archives
      'application/zip' => 250 * 1024 * 1024,
    ];
  }
}

if (!function_exists('upload_paths')) {
  function upload_paths(): array
  {
    $quarantine = realpath(__DIR__ . '/../tmp');
    if (!is_string($quarantine) || $quarantine === '') $quarantine = __DIR__ . '/../tmp';

    $approved = realpath(__DIR__ . '/../assets/uploads');
    if (!is_string($approved) || $approved === '') $approved = __DIR__ . '/../assets/uploads';

    return [
      'quarantine' => $quarantine,
      'approved'   => $approved,
    ];
  }
}

if (!function_exists('ensure_upload_dirs')) {
  function ensure_upload_dirs(): void
  {
    $p = upload_paths();
    if (!is_dir($p['quarantine'])) @mkdir($p['quarantine'], 0700, true);
    if (!is_dir($p['approved']))   @mkdir($p['approved'],   0750, true);
  }
}

if (!function_exists('detect_mime')) {
  function detect_mime(string $tmpFile): string
  {
    if (!is_file($tmpFile)) return 'application/octet-stream';
    if (!class_exists('finfo')) return 'application/octet-stream';

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $m = $finfo->file($tmpFile);
    return is_string($m) && $m !== '' ? $m : 'application/octet-stream';
  }
}

if (!function_exists('generate_safe_filename')) {
  function generate_safe_filename(string $ext): string
  {
    $ext = strtolower(trim($ext));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'bin';
    return bin2hex(random_bytes(16)) . '.' . $ext;
  }
}

if (!function_exists('reencode_image_if_needed')) {
  function reencode_image_if_needed(string $path, string $mime): void
  {
    if (!is_file($path)) return;

    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
      $img = @imagecreatefromjpeg($path);
      if ($img) {
        @imagejpeg($img, $path, 90);
        imagedestroy($img);
      }
    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
      $img = @imagecreatefrompng($path);
      if ($img) {
        @imagepng($img, $path, 6);
        imagedestroy($img);
      }
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp') && function_exists('imagewebp')) {
      $img = @imagecreatefromwebp($path);
      if ($img) {
        @imagewebp($img, $path, 80);
        imagedestroy($img);
      }
    }
  }
}

if (!function_exists('safe_upload')) {
  /**
   * safe_upload($file, $publish=true)
   *
   * - If $publish=true (default): quarantine -> approved (your current behavior)
   * - If $publish=false: stays in quarantine (useful when “no upload provisions yet” operationally)
   */
  function safe_upload(array $file, bool $publish = true): array
  {
    ensure_upload_dirs();
    $paths   = upload_paths();
    $allowed = upload_allowed_mimes();
    $limits  = upload_size_limits();

    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
      throw new RuntimeException('Upload error.');
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
      throw new RuntimeException('Invalid upload source.');
    }

    $mime = detect_mime($tmp);
    if (!isset($allowed[$mime])) {
      throw new RuntimeException('Unsupported file type.');
    }

    $maxSize = (int)($limits[$mime] ?? (10 * 1024 * 1024));
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxSize) {
      throw new RuntimeException('File too large for type.');
    }

    $safeName = generate_safe_filename((string)$allowed[$mime]);
    $qPath    = rtrim($paths['quarantine'], '/\\') . DIRECTORY_SEPARATOR . $safeName;

    if (!@move_uploaded_file($tmp, $qPath)) {
      throw new RuntimeException('Failed to move to quarantine.');
    }

    // Image sanitization (best-effort)
    reencode_image_if_needed($qPath, $mime);

    if (!$publish) {
      $finalSize = @filesize($qPath);
      if ($finalSize === false) $finalSize = $size;

      return [
        'filename' => $safeName,
        'mime'     => $mime,
        'size'     => (int)$finalSize,
        'path'     => $qPath,
        'status'   => 'quarantine',
      ];
    }

    $aPath = rtrim($paths['approved'], '/\\') . DIRECTORY_SEPARATOR . $safeName;
    if (!@rename($qPath, $aPath)) {
      @unlink($qPath);
      throw new RuntimeException('Failed to publish file.');
    }

    $finalSize = @filesize($aPath);
    if ($finalSize === false) $finalSize = $size;

    return [
      'filename' => $safeName,
      'mime'     => $mime,
      'size'     => (int)$finalSize,
      'path'     => $aPath,
      'status'   => 'approved',
    ];
  }
}

/* --------------------
   Operational Safeguards (Moderation/Alerts/Run IDs)
-------------------- */
if (!function_exists('mk__ensure_log_dir')) {
  function mk__ensure_log_dir(string $filePath): void
  {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
      @mkdir($dir, 0750, true);
    }
  }
}

if (!function_exists('log_moderation_event')) {
  function log_moderation_event($actorId, string $action, $targetId, string $details = ''): void
  {
    $logFile = __DIR__ . '/../logs/moderation.log';
    mk__ensure_log_dir($logFile);

    $timestamp = gmdate('Y-m-d H:i:s') . 'Z';
    $entry = sprintf(
      "[%s] Actor:%s Action:%s Target:%s Details:%s\n",
      $timestamp,
      (string)($actorId ?? ''),
      $action,
      (string)($targetId ?? ''),
      $details
    );
    @file_put_contents($logFile, $entry, FILE_APPEND);
  }
}

if (!function_exists('generate_run_id')) {
  function generate_run_id(): string
  {
    return gmdate('Ymd_His') . '_' . bin2hex(random_bytes(4));
  }
}

if (!function_exists('log_alert')) {
  function log_alert(string $message): void
  {
    $logFile = __DIR__ . '/../logs/alerts.log';
    mk__ensure_log_dir($logFile);

    $timestamp = gmdate('Y-m-d H:i:s') . 'Z';
    $entry = sprintf("[%s] WARNING: %s\n", $timestamp, $message);
    @file_put_contents($logFile, $entry, FILE_APPEND);
  }
}
