<?php
declare(strict_types=1);

/**
 * /private/tools/lint/file_integrity.php
 * Finds zero-byte/unreadable files + suspicious extensions under public.
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$root = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
if ($root === '') { echo "APP_ROOT missing.\n"; return; }

$public = dirname($root) . '/public';
$dirs = [];
if (is_dir($public)) $dirs[] = realpath($public);
$dirs = array_values(array_filter($dirs));
if (!$dirs) { echo "Public dir not found.\n"; return; }

$suspiciousExt = ['php','phtml','phar','cgi','pl','py','sh','exe','dll','so'];
$findings = [];

foreach ($dirs as $dir) {
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
  );

  foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $path = $f->getPathname();

    $size = @filesize($path);
    if ($size === 0) {
      $findings[] = ['type' => 'zero-byte', 'file' => $path, 'detail' => '0 bytes'];
      continue;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, $suspiciousExt, true)) {
      $findings[] = ['type' => 'suspicious-ext', 'file' => $path, 'detail' => $ext];
    }

    // unreadable check
    if (!is_readable($path)) {
      $findings[] = ['type' => 'unreadable', 'file' => $path, 'detail' => 'not readable'];
    }
  }
}

echo "Public scanned: " . implode(", ", $dirs) . "\n";
if (!$findings) { echo "No integrity findings.\n"; return; }

foreach (array_slice($findings, 0, 300) as $x) {
  echo "- [{$x['type']}] {$x['detail']} :: {$x['file']}\n";
}
if (count($findings) > 300) echo "(Truncated. Total: " . count($findings) . ")\n";
