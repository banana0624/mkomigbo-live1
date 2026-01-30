<?php
declare(strict_types=1);
// /home/mkomigbo/public_html/app/mkomigbo/private/tools/lint/css_report.php

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$root = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
$public = ($root !== '') ? (dirname($root) . '/public') : '';
$dirs = [];
if ($root !== '' && is_dir($root)) $dirs[] = realpath($root);
if ($public !== '' && is_dir($public)) $dirs[] = realpath($public);
$dirs = array_values(array_filter($dirs));

if (!$dirs) { echo "No dirs to scan.\n"; return; }

$files = [];
foreach ($dirs as $dir) {
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
  );
  foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $path = $f->getPathname();
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext !== 'css') continue;
    $files[] = $path;
  }
}

$issues = [];
foreach ($files as $path) {
  $s = @file_get_contents($path);
  if (!is_string($s) || $s === '') continue;

  $open = substr_count($s, '{');
  $close = substr_count($s, '}');
  if ($open !== $close) {
    $issues[] = ['file' => $path, 'issue' => "Unbalanced braces: {={$open} }={$close}"];
  }
  if (strpos($s, ';;') !== false) {
    $issues[] = ['file' => $path, 'issue' => 'Double semicolon (;;)' ];
  }
}

echo "Scanned CSS: " . count($files) . " file(s)\n";
if (!$issues) { echo "No basic CSS issues found.\n"; return; }

foreach (array_slice($issues, 0, 250) as $i) {
  echo "- {$i['issue']} :: {$i['file']}\n";
}
if (count($issues) > 250) echo "(Truncated. Total: " . count($issues) . ")\n";
