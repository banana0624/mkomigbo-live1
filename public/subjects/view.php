<?php
declare(strict_types=1);

/**
 * /public/subjects/view.php
 * Canonical subjects router:
 *   /subjects/                  -> /public/subjects/index.php
 *   /subjects/{subject-slug}/   -> /public/subjects/subject.php
 *   /subjects/{subject}/{page}/ -> /public/subjects/page.php
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

    // Layout contract (headers may read globals even if mk_view_set missing)
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
      } catch (Throwable $e) { /* ignore */ }
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
    } catch (Throwable $e) {
      // fall through
    }

    if (!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
    echo "404 - {$title}\n{$message}";
    exit;
  }
}

/* ---------------------------------------------------------
   Parse route
--------------------------------------------------------- */
$uriPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (!is_string($uriPath) || $uriPath === '') $uriPath = '/';
$uriPath = rtrim($uriPath, '/') . '/';

$rel = '';
if (strpos($uriPath, '/subjects/') === 0) {
  $rel = substr($uriPath, strlen('/subjects/'));
} elseif (strpos($uriPath, '/public/subjects/') === 0) { // dev/legacy safety
  $rel = substr($uriPath, strlen('/public/subjects/'));
} else {
  mk_subjects_router_404();
}

$rel = trim((string)$rel, '/');
$parts = ($rel === '') ? [] : explode('/', $rel);

/* /subjects/ -> index */
if (count($parts) === 0) {
  require __DIR__ . '/index.php';
  exit;
}

$subjectSlug = strtolower((string)($parts[0] ?? ''));
$pageSlug    = strtolower((string)($parts[1] ?? ''));

/* slug hardening */
$slugOk = static function(string $s): bool {
  return ($s !== '') && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
};

if (!$slugOk($subjectSlug) || ($pageSlug !== '' && !$slugOk($pageSlug))) {
  mk_subjects_router_404('Not found', 'Invalid URL slug.');
}

/* subject landing */
if ($pageSlug === '') {
  $_GET['slug'] = $subjectSlug;
  require __DIR__ . '/subject.php';
  exit;
}

/* subject page */
$_GET['subject'] = $subjectSlug;
$_GET['slug']    = $pageSlug;
require __DIR__ . '/page.php';
exit;
