<?php
declare(strict_types=1);

/**
 * /private/tools/lint/php_syntax.php
 * Runs "php -l" for PHP syntax validation across APP_ROOT and public.
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$root = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
if ($root === '' || !is_dir($root)) { echo "APP_ROOT missing.\n"; return; }

$public = dirname($root) . '/public';
$dirs = [];
if (is_dir($root)) $dirs[] = realpath($root);
if (is_dir($public)) $dirs[] = realpath($public);
$dirs = array_values(array_filter($dirs));

$php = PHP_BINARY ?: 'php';

$files = [];
foreach ($dirs as $dir) {
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
  );
  foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $path = $f->getPathname();
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['php','phtml'], true)) continue;
    $files[] = $path;
  }
}

echo "PHP files: " . count($files) . "\n";
echo "Using: " . $php . "\n\n";

$bad = 0;
foreach ($files as $path) {
  $cmd = escapeshellcmd($php) . ' -l ' . escapeshellarg($path) . ' 2>&1';
  $out = [];
  $rc = 0;
  @exec($cmd, $out, $rc);
  if ($rc !== 0) {
    $bad++;
    echo "FAIL: {$path}\n";
    echo implode("\n", $out) . "\n\n";
  }
}

if ($bad === 0) {
  echo "OK: no PHP syntax errors found.\n";
} else {
  echo "DONE: {$bad} file(s) failed PHP syntax check.\n";
}
