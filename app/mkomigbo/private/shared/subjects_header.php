<?php
declare(strict_types=1);

/**
 * /private/shared/subjects_header.php
 * Public Subjects section header.
 *
 * Ensures:
 * - $active_nav = 'subjects'
 * - Subjects CSS bundle loads (correct order)
 * - Cache busting via ?v=filemtime (important because CSS is served immutable)
 *
 * Optional (set before include):
 * - $extra_css   (array|string) additional css after bundle
 * - $page_title  (string)
 * - $page_desc   (string)
 */

$active_nav = 'subjects';
$nav_active = 'subjects';

if (!isset($page_title) || !is_string($page_title) || trim($page_title) === '') {
  $page_title = 'Subjects • Mkomi Igbo';
}

if (!isset($page_desc) || !is_string($page_desc) || trim($page_desc) === '') {
  $page_desc = 'Browse topics and explore their pages.';
}

/* Normalize extra_css to array */
if (!isset($extra_css)) {
  $extra_css = [];
} elseif (is_string($extra_css)) {
  $extra_css = [ $extra_css ];
} elseif (!is_array($extra_css)) {
  $extra_css = [];
}

/* ---------------------------------------------------------
   Helper: normalize css path/url
--------------------------------------------------------- */
$norm = static function ($p): ?string {
  if (!is_string($p)) return null;
  $p = trim($p);
  if ($p === '') return null;

  // external absolute URL allowed
  if (preg_match('~^https?://~i', $p)) return $p;

  // local path normalized to /...
  if ($p[0] !== '/') $p = '/' . $p;
  return $p;
};

/* ---------------------------------------------------------
   Helper: asset versioning (?v=filemtime) for local paths only
--------------------------------------------------------- */
$ver = static function (string $href): string {
  // only version local /paths
  if ($href === '' || $href[0] !== '/') return $href;

  $docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
  if ($docRoot === '') return $href;

  $file = rtrim($docRoot, '/') . $href;
  if (!is_file($file)) return $href;

  $mtime = @filemtime($file);
  if (!$mtime) return $href;

  $sep = (strpos($href, '?') !== false) ? '&' : '?';
  return $href . $sep . 'v=' . (int)$mtime;
};

/* ---------------------------------------------------------
   Subjects bundle (authoritative order)
   bridge -> grid -> public -> legacy (optional)
--------------------------------------------------------- */
$bundle = [
  '/lib/css/subjects-mk-bridge.css',
  '/lib/css/subjects-grid.css',
  '/lib/css/subjects-public.css',
  '/lib/css/subjects.css', // legacy helpers (safe, keep last)
];

/* Merge bundle + extra, normalize, de-dupe */
$final = [];
$seen  = [];

$push = static function (?string $href) use (&$final, &$seen, $ver): void {
  if ($href === null) return;
  $k = strtolower($href);
  if (isset($seen[$k])) return;
  $seen[$k] = true;
  $final[] = $ver($href);
};

foreach ($bundle as $b) {
  $push($norm($b));
}
foreach ($extra_css as $x) {
  $push($norm($x));
}

$extra_css = $final;

/* Delegate to public_header.php */
$public_header = __DIR__ . '/public_header.php';
if (!is_file($public_header)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "public_header.php not found\nExpected: {$public_header}\n";
  exit;
}

require $public_header;
