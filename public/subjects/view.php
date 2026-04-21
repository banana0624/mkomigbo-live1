<?php
declare(strict_types=1);

/**
 * /public/subjects/view.php
 * Canonical subjects router:
 *   /subjects/                        -> /public/subjects/index.php
 *   /subjects/{subject-slug}/         -> /public/subjects/subject.php
 *   /subjects/{subject}/{page}/       -> /public/subjects/page.php
 *   /subjects/{subject}/{page}/media/{file}
 *   /subjects/{subject}/{page}/download/{token}
 *
 * Pretty handlers above are AUTHORITATIVE for Subjects attachments:
 * - Local files: PRIVATE_PATH/subjects-media/{subject}/{page}/{file}
 * - External links: links.json index at same folder (1-based; also supports 0-based)
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

/* ---------------------------------------------------------
   404 helper (never 500)
--------------------------------------------------------- */
if (!function_exists('mk_subjects_router_404')) {
  function mk_subjects_router_404(string $title = 'Page not found', string $message = 'The page you requested does not exist.'): void
  {
    http_response_code(404);

    $brand = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomi Igbo';
    $page_title = $title . ' • ' . $brand;

    $hh = static function(string $s): string {
      return function_exists('h') ? h($s) : htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };

    $GLOBALS['page_title'] = $page_title;
    $GLOBALS['page_desc']  = $message;
    $GLOBALS['active_nav'] = 'subjects';
    $GLOBALS['nav_active'] = 'subjects';

    if (function_exists('mk_view_set')) {
      try {
        mk_view_set([
          'page_title' => $page_title,
          'page_desc'  => $message,
          'active_nav' => 'subjects',
          'nav_active' => 'subjects',
        ]);
      } catch (Throwable $e) {}
    }

    try {
      if (function_exists('mk_require_shared')) {
        mk_require_shared('public_header.php');

        $back = function_exists('url_for') ? (string)url_for('/subjects/') : '/subjects/';
        $home = function_exists('url_for') ? (string)url_for('/') : '/';

        echo '<div class="container" style="padding:18px 0;">';
        echo '  <header class="mk-hero" style="margin-top:14px;">';
        echo '    <div class="mk-hero__bar" aria-hidden="true"></div>';
        echo '    <div class="mk-hero__inner">';
        echo '      <h1 class="mk-hero__title">' . $hh($title) . '</h1>';
        echo '      <p class="mk-muted" style="margin-top:8px;">' . $hh($message) . '</p>';
        echo '      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">';
        echo '        <a class="mk-btn" href="' . $hh($back) . '">← Back to Subjects</a>';
        echo '        <a class="mk-btn mk-btn--ghost" href="' . $hh($home) . '">Home</a>';
        echo '      </div>';
        echo '    </div>';
        echo '  </header>';
        echo '</div>';

        mk_require_shared('public_footer.php');
        exit;
      }
    } catch (Throwable $e) {}

    if (!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
    echo "404 - {$title}\n{$message}";
    exit;
  }
}

/* ---------------------------------------------------------
   Small helpers
--------------------------------------------------------- */
$deny = static function(int $code, string $msg): void {
  if (!headers_sent()) {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
  }
  echo $msg;
  exit;
};

$slugOk = static function(string $s): bool {
  return ($s !== '') && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
};

$subjects_media_base = static function(): string {
  if (defined('PRIVATE_PATH')) {
    $p = rtrim((string)PRIVATE_PATH, "/\\") . '/subjects-media';
    if (is_dir($p)) return rtrim($p, "/\\");
  }
  if (defined('APP_ROOT')) {
    $p = rtrim((string)APP_ROOT, "/\\") . '/private/subjects-media';
    if (is_dir($p)) return rtrim($p, "/\\");
  }
  return '';
};

$external_allowed = static function(string $url): bool {
  $url = trim($url);
  if ($url === '') return false;

  // Prefer your central allowlist if present
  foreach (['mk_external_url_allowed','mk_is_allowed_external_url','mk_allowlisted_external_url'] as $fn) {
    if (function_exists($fn)) {
      try { return (bool)$fn($url); } catch (Throwable $e) {}
    }
  }

  $p = @parse_url($url);
  if (!is_array($p)) return false;

  $scheme = strtolower((string)($p['scheme'] ?? ''));
  if ($scheme !== 'https') return false;

  $host = strtolower((string)($p['host'] ?? ''));
  $host = rtrim($host, '.');
  if ($host === '' || $host === 'localhost') return false;
  if (filter_var($host, FILTER_VALIDATE_IP)) return false;

  // Safe fallback allowlist
  $allow = [
    'wikipedia.org','wikimedia.org',
    'youtube.com','youtu.be',
    'archive.org',
    'facebook.com','instagram.com','x.com','twitter.com',
  ];

  foreach ($allow as $base) {
    $base = strtolower($base);
    if ($host === $base) return true;
    if (substr($host, -strlen('.'.$base)) === '.'.$base) return true;
  }
  return false;
};

$load_links = static function(string $dir): array {
  $file = rtrim($dir, "/\\") . '/links.json';
  if (!is_file($file) || !is_readable($file)) return [];

  $raw = @file_get_contents($file);
  if (!is_string($raw) || trim($raw) === '') return [];

  $data = json_decode($raw, true);
  if (!is_array($data)) return [];

  // support [{...}] or {"links":[...]}
  $arr = (isset($data['links']) && is_array($data['links'])) ? $data['links'] : $data;
  if (!is_array($arr)) return [];

  $out = [];
  foreach ($arr as $row) {
    if (is_array($row)) {
      $u = isset($row['url']) && is_string($row['url']) ? trim($row['url']) : '';
      if ($u !== '') $out[] = ['url' => $u];
    } elseif (is_string($row)) {
      $u = trim($row);
      if ($u !== '') $out[] = ['url' => $u];
    }
  }
  return $out;
};

$detect_mime = static function(string $abs): string {
  $mime = 'application/octet-stream';
  try {
    if (function_exists('finfo_open')) {
      $fi = @finfo_open(FILEINFO_MIME_TYPE);
      if ($fi) {
        $m = @finfo_file($fi, $abs);
        @finfo_close($fi);
        if (is_string($m) && $m !== '') $mime = $m;
      }
    }
  } catch (Throwable $e) {}
  return $mime;
};

$send_file = static function(string $abs, string $name, bool $asAttachment) use ($deny, $detect_mime): void {
  if (!is_file($abs) || !is_readable($abs)) $deny(404, 'File not found.');

  $mime = $detect_mime($abs);
  $size = @filesize($abs);
  if (!is_int($size) || $size < 0) $size = null;

  while (ob_get_level() > 0) { @ob_end_clean(); }

  http_response_code(200);
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('X-Frame-Options: SAMEORIGIN');
  header('Cache-Control: private, max-age=0, must-revalidate');
  header('Content-Type: ' . $mime);

  $disp = $asAttachment ? 'attachment' : 'inline';
  $safe = str_replace(["\r","\n"], '', $name);
  header("Content-Disposition: {$disp}; filename=\"" . rawurlencode($safe) . "\"; filename*=UTF-8''" . rawurlencode($safe));
  if ($size !== null && $size > 0) header('Content-Length: ' . (string)$size);

  $fh = @fopen($abs, 'rb');
  if (!$fh) $deny(500, 'Unable to open file.');

  fpassthru($fh);
  fclose($fh);
  exit;
};

/* ---------------------------------------------------------
   Parse route
--------------------------------------------------------- */
$uriPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (!is_string($uriPath) || $uriPath === '') $uriPath = '/';
$uriPath = rtrim($uriPath, '/') . '/';

if (strpos($uriPath, '/subjects/') !== 0 && strpos($uriPath, '/public/subjects/') !== 0) {
  mk_subjects_router_404();
}

$rel = (strpos($uriPath, '/subjects/') === 0)
  ? substr($uriPath, strlen('/subjects/'))
  : substr($uriPath, strlen('/public/subjects/'));

$rel = trim((string)$rel, '/');
$parts = ($rel === '') ? [] : explode('/', $rel);

/* /subjects/ -> index */
if (count($parts) === 0) {
  require __DIR__ . '/index.php';
  exit;
}

/* optional static pages (only if you want these paths) */
$first = strtolower((string)($parts[0] ?? ''));
if (count($parts) === 1) {
  if ($first === '_scripts') mk_subjects_router_404(); // block direct folder browsing
  // If you actually want /subjects/scripts/ and /subjects/writing/ you must keep these files.
  if ($first === 'scripts' && is_file(__DIR__ . '/_scripts/scripts.php')) { require __DIR__ . '/_scripts/scripts.php'; exit; }
  if ($first === 'writing' && is_file(__DIR__ . '/_scripts/writing.php')) { require __DIR__ . '/_scripts/writing.php'; exit; }
}

/* subject/page parsing */
$subjectSlug = strtolower((string)($parts[0] ?? ''));
$pageSlug    = strtolower((string)($parts[1] ?? ''));

if (!$slugOk($subjectSlug) || ($pageSlug !== '' && !$slugOk($pageSlug))) {
  mk_subjects_router_404('Not found', 'Invalid URL slug.');
}

/* ---------------------------------------------------------
   Canonical redirect (DB-driven, preserves ALL subpaths)
   This is the single authoritative PHP fallback.
--------------------------------------------------------- */
try {
  if (function_exists('mk_subject_alias_target')) {
    $canon = (string)mk_subject_alias_target($subjectSlug); // returns '' if not an alias
    $canon = strtolower(trim($canon));

    if ($canon !== '' && $canon !== $subjectSlug) {
      // Preserve everything after /subjects/{subject}/ (including page/media/download tokens)
      $tailParts = [];
      if (count($parts) >= 2) {
        $tailParts = array_slice($parts, 1); // keep page + action + token...
      }

      $dest = '/subjects/' . $canon . '/';
      if (!empty($tailParts)) {
        // IMPORTANT: do NOT decode/re-encode segments; keep them as URL path segments
        $dest .= implode('/', $tailParts);
        if (substr($dest, -1) !== '/') $dest .= '/';
      }

      if (function_exists('url_for')) {
        try { $dest = (string)url_for($dest); } catch (Throwable $e) {}
      }

      $dest = str_replace(["\r", "\n"], '', $dest);
      header('Cache-Control: no-store');
      header('Location: ' . $dest, true, 301);
      exit;
    }
  }
} catch (Throwable $e) {
  // governance must never 500 the router
}

/* ---------------------------------------------------------
   Pretty handlers:
   /subjects/{subject}/{page}/media/{token}
   /subjects/{subject}/{page}/download/{token}
--------------------------------------------------------- */
$action = strtolower((string)($parts[2] ?? ''));
$token  = (string)($parts[3] ?? '');

if (($action === 'media' || $action === 'download') && $pageSlug !== '' && $token !== '') {

  // hard-block traversal: token must be one segment
  if (strpos($token, "\0") !== false || strpos($token, '..') !== false || strpos($token, '\\') !== false || strpos($token, '/') !== false) {
    mk_subjects_router_404('Not found', 'Invalid file token.');
  }

  $base = $subjects_media_base();
  if ($base === '' || !is_dir($base)) $deny(500, 'Storage not configured.');

  $dir = $base . '/' . $subjectSlug . '/' . $pageSlug;

  $baseReal = realpath($base);
  $dirReal  = realpath($dir);
  if (!is_string($baseReal) || !is_string($dirReal)) $deny(404, 'Not found.');

  $baseReal = rtrim(str_replace('\\','/', $baseReal), '/');
  $dirReal  = rtrim(str_replace('\\','/', $dirReal), '/');

  if (strpos($dirReal . '/', $baseReal . '/') !== 0) $deny(404, 'Not found.');

  // download + numeric token => links.json redirect
  if ($action === 'download' && preg_match('/^\d{1,6}$/', $token)) {
    $links = $load_links($dirReal);
    if (empty($links)) $deny(404, 'Not found.');

    $i = (int)$token;

    // support 1-based first, then 0-based
    $cand = $links[$i - 1] ?? ($links[$i] ?? null);
    $url  = (is_array($cand) && isset($cand['url']) && is_string($cand['url'])) ? trim($cand['url']) : '';

    if ($url === '' || !$external_allowed($url)) $deny(403, 'External URL blocked.');

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Cache-Control: no-store');
    header('Location: ' . str_replace(["\r","\n"], '', $url), true, 302);
    exit;
  }

  // otherwise token treated as filename
  $file = rawurldecode($token);
  $basename = basename(str_replace(['\\'], ['/'], $file));
  if ($basename === '' || $basename === '.' || $basename === '..') $deny(404, 'Not found.');

  // block serving links.json
  if (strcasecmp($basename, 'links.json') === 0) $deny(403, 'Blocked.');

  // block executable-ish extensions
  if (preg_match('/\.(php|phtml|phar|cgi|pl|asp|aspx|js|html|htm|sh)$/i', $basename)) $deny(404, 'Not found.');

  $abs = $dirReal . '/' . $basename;
  $absReal = realpath($abs);
  if (!is_string($absReal)) $deny(404, 'File not found.');

  $absReal = str_replace('\\','/', $absReal);
  if (strpos($absReal . '/', $dirReal . '/') !== 0) $deny(404, 'Not found.');

  $send_file($absReal, $basename, ($action === 'download'));
}

/* subject landing */
if ($pageSlug === '') {
  $_GET['slug'] = $subjectSlug;

  try {
    require __DIR__ . '/subject.php';
  } catch (Throwable $e) {
    mk_subjects_router_404('Error', 'Unable to render subject.');
  }
  exit;
}

/* subject page */
$_GET['subject'] = $subjectSlug;
$_GET['slug']    = $pageSlug;

try {
  require __DIR__ . '/page.php';
} catch (Throwable $e) {
  mk_subjects_router_404('Error', 'Unable to render subject page.');
}
exit;
