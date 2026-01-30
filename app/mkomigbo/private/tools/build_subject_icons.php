<?php
declare(strict_types=1);

/**
 * build_subject_icons.php (DB-free, cPanel-safe)
 *
 * Writes to:
 *   /public/lib/images/mk-logo.svg
 *   /public/lib/images/igbo-calendar.svg
 *   /public/lib/images/subjects/{slug}.svg   (19 subjects)
 *
 * No DB required. No initialize.php required.
 */

@ini_set('display_errors', '1');
error_reporting(E_ALL);

$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : dirname(__DIR__, 2); // .../app/mkomigbo
if ($appRoot === '' || !is_dir($appRoot)) {
  echo "APP_ROOT invalid.\n";
  exit(1);
}

/* Find real public root: /home/.../public_html/public */
$publicRoot = '';
$base = $appRoot;
for ($i=0; $i<=12; $i++) {
  $cand = rtrim($base, "/\\") . '/public';
  if (is_dir($cand)) { $publicRoot = $cand; break; }
  $p = dirname($base);
  if ($p === $base) break;
  $base = $p;
}
if ($publicRoot === '' || !is_dir($publicRoot)) {
  echo "PUBLIC root not found (expected sibling /public).\n";
  exit(1);
}

function initials(string $name, string $slug): string {
  $name = trim($name);
  if ($name === '') $name = $slug;
  $parts = preg_split('/\s+/', $name) ?: [$name];
  $i = '';
  foreach ($parts as $p) {
    $p = preg_replace('/[^A-Za-z0-9]/', '', $p) ?? $p;
    if ($p !== '') $i .= strtoupper($p[0]);
    if (strlen($i) >= 2) break;
  }
  if ($i === '') $i = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $slug) ?? $slug, 0, 2));
  return substr($i, 0, 2);
}

function colorFromSlug(string $slug): string {
  return '#' . substr(sha1($slug), 0, 6);
}

function ensureDir(string $dir): void {
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

function writeSvg(string $path, string $title, string $sub, string $slug): void {
  $bg   = colorFromSlug($slug);
  $txt  = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
  $subt = htmlspecialchars($sub, ENT_QUOTES, 'UTF-8');
  $ini  = htmlspecialchars(initials($title, $slug), ENT_QUOTES, 'UTF-8');

  $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360" role="img" aria-label="{$txt}">
  <defs>
    <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
      <stop offset="0" stop-color="{$bg}" stop-opacity="0.95"/>
      <stop offset="1" stop-color="#111827" stop-opacity="0.95"/>
    </linearGradient>
  </defs>
  <rect width="640" height="360" rx="28" fill="url(#g)"/>
  <circle cx="104" cy="100" r="56" fill="rgba(255,255,255,0.14)"/>
  <text x="104" y="112" text-anchor="middle" font-family="system-ui,-apple-system,Segoe UI,Roboto,Arial" font-size="34" font-weight="800" fill="white">{$ini}</text>
  <text x="56" y="210" font-family="system-ui,-apple-system,Segoe UI,Roboto,Arial" font-size="34" font-weight="900" fill="white">{$txt}</text>
  <text x="56" y="248" font-family="system-ui,-apple-system,Segoe UI,Roboto,Arial" font-size="16" font-weight="600" fill="rgba(255,255,255,0.86)">{$subt}</text>
</svg>
SVG;

  @file_put_contents($path, $svg);
}

/* Subject list (slug => label) */
$subjects = [
  'history'       => 'History',
  'slavery'       => 'Slavery',
  'people'        => 'People',
  'persons'       => 'Persons',
  'culture'       => 'Culture',
  'religion'      => 'Religion',
  'spirituality'  => 'Spirituality',
  'tradition'     => 'Tradition',
  'language1'     => 'Language 1',
  'language2'     => 'Language 2',
  'struggles'     => 'Struggles',
  'biafra'        => 'Biafra',
  'nigeria'       => 'Nigeria',
  'africa'        => 'Africa',
  'uk'            => 'UK',
  'europe'        => 'Europe',
  'arabs'         => 'Arabs',
  'scripts'       => 'Scripts',
  'platforms'     => 'Platforms',
];

$imgRoot = $publicRoot . '/lib/images';
$subDir  = $imgRoot . '/subjects';
ensureDir($imgRoot);
ensureDir($subDir);

/* Project + calendar */
writeSvg($imgRoot . '/mk-logo.svg', 'Mkomi Igbo', 'Project logo', 'mkomi-igbo');
writeSvg($imgRoot . '/igbo-calendar.svg', 'Igbo Calendar', 'Calendar module icon', 'igbo-calendar');

/* Subjects */
$created = 0;
foreach ($subjects as $slug => $label) {
  $path = $subDir . '/' . $slug . '.svg';
  if (!is_file($path)) $created++;
  writeSvg($path, $label, 'Subject', $slug);
}

echo "PUBLIC_ROOT: {$publicRoot}\n";
echo "Subjects written: " . count($subjects) . "\n";
echo "Subjects created (new): {$created}\n";
echo "Wrote: {$imgRoot}/mk-logo.svg\n";
echo "Wrote: {$imgRoot}/igbo-calendar.svg\n";
echo "Wrote: {$subDir}/*.svg\n";
