<?php
declare(strict_types=1);

/**
 * /private/shared/public_header.php
 * Public header (single source of truth).
 *
 * Optional inputs:
 *   $page_title (string)
 *   $page_desc  (string)
 *   $nav_active (string) home|subjects|platforms|contributors|igbo-calendar|staff
 *   $extra_css  (array)  additional hrefs (public-relative) to load
 *   $extra_head (string) extra <head> tags (manifest, meta, etc.)
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!headers_sent()) {
  header('Content-Type: text/html; charset=utf-8');
  header('X-Content-Type-Options: nosniff');
}

if (!isset($page_title) || !is_string($page_title) || trim($page_title) === '') {
  $page_title = 'Mkomi Igbo';
}
if (!isset($page_desc) || !is_string($page_desc)) {
  $page_desc = '';
}
if (!isset($nav_active) || !is_string($nav_active)) {
  $nav_active = '';
}
if (!isset($extra_css) || !is_array($extra_css)) {
  $extra_css = (isset($GLOBALS['extra_css']) && is_array($GLOBALS['extra_css'])) ? $GLOBALS['extra_css'] : [];
}
if (!isset($extra_head) || !is_string($extra_head)) {
  $extra_head = (isset($GLOBALS['extra_head']) && is_string($GLOBALS['extra_head'])) ? $GLOBALS['extra_head'] : '';
}

if (!function_exists('h')) {
  function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('url_for')) {
  function url_for(string $path): string { return $path; }
}

$req = (string)($_SERVER['REQUEST_URI'] ?? '');
$isSubjects = ($nav_active === 'subjects') || (strpos($req, '/subjects/') !== false);
$isCalendar = ($nav_active === 'igbo-calendar') || (strpos($req, '/igbo-calendar/') !== false);

/* ---------------------------------------------------------
   Build CSS list
--------------------------------------------------------- */
$css = ['/lib/css/ui.css'];

/* Subjects CSS (only when needed) */
if ($isSubjects) {
  foreach (['/lib/css/subjects.css','/lib/css/subjects-public.css','/lib/css/subjects-grid.css','/lib/css/subjects-mk-bridge.css'] as $f) {
    $css[] = $f;
  }
}

/* Calendar CSS (only when needed) */
if ($isCalendar) {
  $css[] = '/igbo-calendar/igbo-calendar.css';
  $css[] = '/igbo-calendar/install/install.css'; // optional (ok if 404)
}

/* Caller-provided css */
foreach ($extra_css as $f) {
  if (is_string($f) && $f !== '') $css[] = $f;
}

/* De-dup */
$css = array_values(array_unique($css));

/* ---------------------------------------------------------
   Cache-busting for immutable CSS
   - Your server sends: max-age=31536000, immutable
   - Therefore the URL must change when the file changes
--------------------------------------------------------- */
$doc_root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), "/\\");
$with_ver = static function(string $href) use ($doc_root): string {
  $href = trim($href);
  if ($href === '') return $href;

  // Only version local absolute-path URLs ("/lib/css/..", "/igbo-calendar/..")
  if ($href[0] !== '/') return $href;

  // Do not double-version
  if (strpos($href, '?') !== false) return $href;

  // Map URL path -> filesystem path under DOCUMENT_ROOT
  if ($doc_root !== '') {
    $abs = $doc_root . $href;
    if (is_file($abs)) {
      return $href . '?v=' . rawurlencode((string)filemtime($abs));
    }
  }

  return $href;
};

$asset = static function(string $path) use ($with_ver): string {
  // Version then url_for then escape
  $v = $with_ver($path);
  return h(url_for($v));
};

/* Default icons */
$appleTouchIcon = '/igbo-calendar/icons/icon-192.png';
$faviconSvg     = '/lib/images/mk-logo.svg';

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <title><?= h($page_title) ?></title>

  <?php if ($page_desc !== ''): ?>
    <meta name="description" content="<?= h($page_desc) ?>">
  <?php endif; ?>

  <?php foreach ($css as $href): ?>
    <link rel="stylesheet" href="<?= $asset($href) ?>">
  <?php endforeach; ?>

  <?php
    // Allow pages to inject extra head tags (manifest, meta, etc.) exactly once
    if (!empty($extra_head)) {
      echo "\n" . $extra_head . "\n";
    }
  ?>

  <link rel="icon" href="<?= $asset($faviconSvg) ?>" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= $asset($appleTouchIcon) ?>">
</head>
<body class="mk-body">

<?php
$nav_file = __DIR__ . '/public_nav.php';
if (is_file($nav_file)) include $nav_file;
?>
<main class="mk-main">
