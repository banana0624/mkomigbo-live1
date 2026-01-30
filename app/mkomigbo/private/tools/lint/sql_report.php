<?php
declare(strict_types=1);

/**
 * /private/tools/lint/sql_report.php
 * Basic SQL keyword scan (read-only).
 *
 * - Scans APP_ROOT/private + project /public (auto-detected)
 * - Excludes /private/tools/** to prevent self-flagging
 * - Outputs plain text (PRE-friendly)
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

/* ---------------------------------------------------------
   Locate APP_ROOT (must already be defined by runner/init)
--------------------------------------------------------- */
$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
if ($appRoot === '' || !is_dir($appRoot)) {
  echo "APP_ROOT missing or invalid.\n";
  return;
}

/* ---------------------------------------------------------
   Determine roots: private + public (sibling search upward)
--------------------------------------------------------- */
$roots = [];

$private = $appRoot . '/private';
if (is_dir($private)) $roots[] = realpath($private) ?: $private;

$publicFound = '';
$base = $appRoot;
for ($i = 0; $i <= 8; $i++) {
  $cand = rtrim($base, "/\\") . '/public';
  if (is_dir($cand)) { $publicFound = $cand; break; }
  $parent = dirname($base);
  if ($parent === $base) break;
  $base = $parent;
}
if ($publicFound !== '') $roots[] = realpath($publicFound) ?: $publicFound;

if (!$roots) {
  echo "No scan roots found.\n";
  return;
}

/* ---------------------------------------------------------
   Exclusions (prevents self-flagging + noise)
--------------------------------------------------------- */
$excludeDirNeedles = [
  str_replace('\\','/', $appRoot . '/private/tools/'), // key: exclude tools
  str_replace('\\','/', $appRoot . '/vendor/'),
  str_replace('\\','/', $appRoot . '/logs/'),
  str_replace('\\','/', $appRoot . '/private/logs/'),
  str_replace('\\','/', $appRoot . '/private/cache/'),
  str_replace('\\','/', $appRoot . '/cache/'),
];

$excludePathRegexes = [
  '~/(?:staff_tools\.bak_[^/]+|\.bak(?:/|$)|backup(?:/|$)|backups(?:/|$))/~i',
  '~/(?:node_modules|\.git|\.svn|\.hg|\.idea|\.vscode)/~i',
  '~/(?:tmp|temp)(?:/|$)~i',
];

$pathIsExcluded = static function(string $path) use ($excludeDirNeedles, $excludePathRegexes): bool {
  $p = str_replace('\\','/', $path);
  foreach ($excludeDirNeedles as $needle) {
    if ($needle !== '' && stripos($p, $needle) !== false) return true;
  }
  foreach ($excludePathRegexes as $rx) {
    if (@preg_match($rx, $p)) return true;
  }
  return false;
};

/* ---------------------------------------------------------
   Scan config
--------------------------------------------------------- */
$exts = ['sql','php','phtml','inc','txt','md'];  // include embedded SQL in PHP
$maxBytesPerFile = 2_000_000;

$rules = [
  ['label' => 'Contains DROP TABLE (review)',  're' => '~\bDROP\s+TABLE\b~i'],
  ['label' => 'Contains TRUNCATE (review)',    're' => '~\bTRUNCATE\b~i'],
  ['label' => 'Contains ALTER TABLE (review)','re' => '~\bALTER\s+TABLE\b~i'],
];

$scanned = 0;
$hits = [];

/* ---------------------------------------------------------
   Walk files
--------------------------------------------------------- */
foreach ($roots as $root) {
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
  );

  foreach ($it as $fileInfo) {
    /** @var SplFileInfo $fileInfo */
    $path = $fileInfo->getPathname();
    if ($pathIsExcluded($path)) continue;
    if (!$fileInfo->isFile() || !$fileInfo->isReadable()) continue;

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, $exts, true)) continue;

    $size = (int)$fileInfo->getSize();
    if ($size <= 0) continue;
    if ($size > $maxBytesPerFile) continue;

    $content = @file_get_contents($path);
    if (!is_string($content) || $content === '') continue;

    $scanned++;

    foreach ($rules as $r) {
      if (preg_match($r['re'], $content)) {
        $hits[] = $r['label'] . ' :: ' . $path;
      }
    }
  }
}

/* ---------------------------------------------------------
   Output
--------------------------------------------------------- */
echo "Scanned: " . $scanned . " file(s)\n";
if (!$hits) {
  echo "No SQL keyword hits.\n";
  return;
}
foreach ($hits as $h) {
  echo "- " . $h . "\n";
}
