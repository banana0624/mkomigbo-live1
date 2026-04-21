<?php
declare(strict_types=1);

/**
 * /public/platforms/view.php
 * Platforms router + safe fallback placeholder.
 *
 * Routes:
 * - /platforms/                -> index.php
 * - /platforms/{slug}/         -> loads /platforms/{slug}/index.php if present
 * - /platforms/view.php?slug=x -> 301 -> /platforms/x/
 *
 * If a platform index is missing or crashes:
 * - render a safe placeholder via _platform_template.php (no header/footer here)
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/_init.php';

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

/* Invalid slug => 404 placeholder */
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
  } else {
    http_response_code(404);
  }
}

/* ---------------------------------------------------------
   Fallback placeholder rendered via _platform_template.php
--------------------------------------------------------- */
$pretty = ($slug !== '') ? ucwords(str_replace(['-','_'], ' ', $slug)) : 'Platform';

$pf = [
  'title'  => $pretty,
  'desc'   => ($crash_note !== '')
    ? 'This platform is temporarily unavailable. Please try again later.'
    : 'This platform is coming soon.',
  'slug'   => ($slug !== '') ? $slug : '',
  'status' => 'preview',
  'robots' => 'noindex, nofollow',
];

$platform_body_html = '';
if ($crash_note !== '') {
  $platform_body_html = '<section class="pf-section"><div class="pf-section__title"><h2>Status</h2></div>'
    . '<div class="mk-alert mk-alert--danger"><strong>Runtime error:</strong> '
    . htmlspecialchars($crash_note, ENT_QUOTES, 'UTF-8')
    . '</div></section>';
}

require __DIR__ . '/_platform_template.php';
exit;
