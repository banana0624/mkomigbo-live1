<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../public/_init.php';

$base = '/home/mkomigbo/public_html/public/subjects/pages';

$subjects = [];
try {
  $pdo = function_exists('db') ? db() : null;
  if ($pdo instanceof PDO) {
    $subjects = $pdo->query("SELECT id, slug, name FROM subjects ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
} catch (Throwable $e) {}

echo "== Canonical subjects (DB) ==\n";
foreach ($subjects as $s) {
  echo (int)$s['id'] . "  " . $s['slug'] . "  " . ($s['name'] ?? '') . "\n";
}

echo "\n== Filesystem folders ==\n";
$dirs = glob($base . '/*', GLOB_ONLYDIR) ?: [];
$fs = [];
foreach ($dirs as $d) $fs[] = basename($d);
sort($fs);
echo implode("\n", $fs) . "\n";

echo "\n== Orphans (folder exists, not in DB canonical) ==\n";
$canon = array_map(fn($r) => strtolower((string)($r['slug'] ?? '')), $subjects);
$canon = array_filter($canon);
$canonSet = array_fill_keys($canon, true);

foreach ($fs as $slug) {
  if (!isset($canonSet[$slug])) echo $slug . "\n";
}

echo "\nDone.\n";
