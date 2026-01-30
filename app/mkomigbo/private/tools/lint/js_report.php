<?php
declare(strict_types=1);

/**
 * /private/tools/lint/js_report.php
 * Read-only JS heuristic scanner.
 *
 * What it checks:
 * - inline eval / Function constructor
 * - new Function(...)
 * - document.write
 * - suspicious innerHTML assignments
 * - missing "use strict" (info)
 *
 * Output: PRE-friendly text.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
if ($appRoot === '') {
  // best guess: .../app/mkomigbo/private/tools/lint/js_report.php
  $guess = dirname(__DIR__, 3);
  if (is_dir($guess)) $appRoot = $guess;
}
if ($appRoot === '' || !is_dir($appRoot)) {
  echo "APP_ROOT missing/invalid\n";
  return;
}

$publicRoot = dirname($appRoot) . '/public';
if (!is_dir($publicRoot)) {
  // fallback: common layout you use
  $publicRoot = dirname(dirname($appRoot)) . '/public';
}

$roots = [];
if (is_dir($publicRoot)) $roots[] = $publicRoot;
$roots[] = $appRoot;

$patterns = [
  'eval'          => '/\beval\s*\(/i',
  'Function_ctor' => '/\bnew\s+Function\s*\(|\bFunction\s*\(/i',
  'doc_write'     => '/\bdocument\.write\s*\(/i',
  'innerHTML'     => '/\b(innerHTML|outerHTML)\s*=\s*/i',
];

$scanExt = ['js', 'mjs', 'cjs'];

$files = [];
$seen = [];

$addFile = static function(string $f) use (&$files, &$seen): void {
  $r = realpath($f);
  if (!$r || isset($seen[$r])) return;
  $seen[$r] = true;
  $files[] = $r;
};

$walk = static function(string $root) use (&$addFile, $scanExt): void {
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
  );
  foreach ($it as $fi) {
    /** @var SplFileInfo $fi */
    if (!$fi->isFile()) continue;
    $ext = strtolower($fi->getExtension());
    if (!in_array($ext, $scanExt, true)) continue;
    $addFile($fi->getPathname());
  }
};

foreach ($roots as $r) {
  if (is_dir($r)) $walk($r);
}

sort($files);

$findings = [];
$info = [];

foreach ($files as $f) {
  $c = @file_get_contents($f);
  if (!is_string($c) || $c === '') continue;

  foreach ($patterns as $name => $rx) {
    if (preg_match($rx, $c)) {
      $findings[] = "{$name} :: {$f}";
    }
  }

  // info: missing strict mode in top of file (not an error)
  if (!preg_match('/^[\s;]*(["\'])use strict\1/m', $c)) {
    $info[] = "no_use_strict :: {$f}";
  }
}

echo "JS Report (Heuristic)\n";
echo "Scanned: " . count($files) . " file(s)\n";
echo "----------------------------------------\n";

if (!$findings) {
  echo "[ OK ] No high-risk JS patterns found.\n";
} else {
  echo "[WARN] Potentially risky patterns:\n";
  foreach ($findings as $x) echo "- {$x}\n";
}

echo "----------------------------------------\n";
echo "Info (optional hardening):\n";
$max = min(25, count($info));
for ($i=0; $i<$max; $i++) echo "- {$info[$i]}\n";
if (count($info) > $max) echo "- ... (" . (count($info) - $max) . " more)\n";
