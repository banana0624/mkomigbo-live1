<?php
declare(strict_types=1);

/**
 * /public/platforms/view.php
 * Platforms router + safe placeholder controller.
 *
 * Routes:
 * - /platforms/                -> index.php
 * - /platforms/{slug}/         -> loads /platforms/{slug}/index.php if present
 * - /platforms/view.php?slug=x -> 301 -> /platforms/x/
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/_init.php';

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('pf__u')) {
  function pf__u(string $path): string { return function_exists('url_for') ? (string)url_for($path) : $path; }
}
if (!function_exists('pf__redirect_301')) {
  function pf__redirect_301(string $to): void {
    $to = str_replace(["\r","\n"], '', $to);
    header('Location: ' . $to, true, 301);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Moved: " . $to;
    exit;
  }
}

/* Resolve slug */
$slug = '';
if (isset($_GET['slug']) && is_scalar($_GET['slug'])) $slug = strtolower(trim((string)$_GET['slug']));
elseif (isset($_GET['key']) && is_scalar($_GET['key'])) $slug = strtolower(trim((string)$_GET['key']));

if ($slug === '') {
  $uri  = (string)($_SERVER['REQUEST_URI'] ?? '/platforms/');
  $path = (string)(parse_url($uri, PHP_URL_PATH) ?: '/platforms/');
  $path = rtrim($path, '/') . '/';
  if (preg_match('~^/platforms/([^/]+)/~i', $path, $m)) $slug = strtolower((string)$m[1]);
}

$slugOk = static function(string $s): bool {
  return ($s !== '') && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
};

/* /platforms/ -> index */
if ($slug === '') {
  require __DIR__ . '/index.php';
  exit;
}

/* Canonical redirect for controller hits */
$path_only = strtolower((string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: ''));
if (strpos($path_only, '/platforms/view.php') !== false && $slugOk($slug)) {
  pf__redirect_301(pf__u('/platforms/' . rawurlencode($slug) . '/'));
}

/* Invalid slug => 404 */
if (!$slugOk($slug)) {
  http_response_code(404);
  $slug = '';
}

/* Try to load platform index */
$crash_note = '';
if ($slug !== '') {
  $targetDir = __DIR__ . '/' . $slug;
  $targetIdx = $targetDir . '/index.php';

  if (is_dir($targetDir) && is_file($targetIdx)) {
    try {
      require $targetIdx;
      exit;
    } catch (Throwable $e) {
      $crash_note = $e->getMessage();
      http_response_code(503);
    }
  }
}

/* Placeholder */
$pretty = ($slug !== '') ? ucwords(str_replace(['-','_'], ' ', $slug)) : 'Platform';

$page_title = $pretty . ' — Platforms — Mkomi Igbo';
$page_desc  = ($crash_note !== '')
  ? 'This platform is temporarily unavailable.'
  : 'This platform is coming soon.';

$extra_css = [ pf__u('/lib/css/public.css'), pf__u('/lib/css/platforms.css') ];

$GLOBALS['page_title'] = $page_title;
$GLOBALS['page_desc']  = $page_desc;
$GLOBALS['nav_active'] = 'platforms';
$GLOBALS['active_nav'] = 'platforms';
$GLOBALS['extra_css']  = $extra_css;

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => 'platforms',
      'active_nav' => 'platforms',
      'extra_css'  => $extra_css,
    ]);
  } catch (Throwable $e) { /* ignore */ }
}

/* Header */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}
if (!$header_ok) {
  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html lang='en'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($page_title) . "</title></head><body>";
}

$platforms_url = pf__u('/platforms/');
$home_url      = pf__u('/');

?>
<main class="container mk-page">

  <div class="mk-page-actions">
    <a class="mk-btn mk-btn--ghost" href="<?= h($platforms_url); ?>">← Back to Platforms</a>
    <a class="mk-btn mk-btn--ghost" href="<?= h($home_url); ?>">Home</a>
  </div>

  <header class="mk-hero mk-hero--compact">
    <div class="mk-hero__bar" aria-hidden="true"></div>
    <div class="mk-hero__inner">
      <h1 class="mk-hero__title"><?= h($pretty); ?></h1>

      <?php if ($crash_note !== ''): ?>
        <p class="mk-muted mk-lede">This platform is temporarily unavailable. Please try again later.</p>
        <div class="mk-alert mk-alert--danger">
          <strong>Runtime error:</strong> <?= h($crash_note); ?>
        </div>
      <?php else: ?>
        <p class="mk-muted mk-lede">This platform is coming soon. When it launches, you’ll find it here.</p>
      <?php endif; ?>

      <div class="mk-hero__actions">
        <a class="mk-btn" href="<?= h($platforms_url); ?>">← Back to Platforms</a>
        <a class="mk-btn mk-btn--ghost" href="<?= h($home_url); ?>">Home</a>
      </div>
    </div>
  </header>

</main>

<?php
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_footer.php'); } catch (Throwable $e) {}
} else {
  echo "</body></html>";
}
