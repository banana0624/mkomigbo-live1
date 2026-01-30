<?php
declare(strict_types=1);

/**
 * /private/tools/diagnostics/attachments_integrity_scan.php
 *
 * Read-only diagnostic:
 * - DB local rows -> file exists + boundary-safe under /public_html/lib/uploads/page_files
 * - Disk files -> row exists in DB (orphan files)
 * - External rows -> validate + normalization check using private/functions/page_attachments_external.php
 *
 * Safe: does NOT delete or modify anything.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$ts = gmdate('Y-m-d\TH:i:s\Z');

if (!defined('APP_ROOT') || !is_string(APP_ROOT) || APP_ROOT === '') {
  // If runner didn't define APP_ROOT, try to locate it relative to this file.
  $guess = realpath(__DIR__ . '/../../'); // .../private/tools -> .../private
  $guess = $guess ? realpath($guess . '/..') : false; // .../app/mkomigbo
  if ($guess) define('APP_ROOT', $guess);
}

$appRoot = rtrim((string)APP_ROOT, "/\\");
$init = $appRoot . '/private/assets/initialize.php';
if (is_file($init)) {
  require_once $init;
}

if (!function_exists('db') || !(db() instanceof PDO)) {
  echo "Attachments Integrity Scan\n";
  echo "Started: {$ts}\n";
  echo "ERROR: db() not available. initialize.php not loaded?\n";
  exit;
}

$pdo = db();

/* uploads root matches open.php/download.php contract */
$publicHtml = realpath(dirname(dirname($appRoot))); // /home/.../public_html
$uploadsRoot = $publicHtml ? realpath($publicHtml . '/lib/uploads/page_files') : false;

echo "Attachments Integrity Scan\n";
echo "Started: {$ts}\n";
echo "APP_ROOT: {$appRoot}\n";
echo "Public HTML: " . ($publicHtml ?: '(unknown)') . "\n";
echo "Uploads root: " . ($uploadsRoot ?: '(missing)') . "\n";
echo str_repeat('-', 60) . "\n";

if (!$uploadsRoot || !is_dir($uploadsRoot)) {
  echo "ERROR: uploads root missing. Expected /public_html/lib/uploads/page_files\n";
  exit;
}

$uploadsRootNorm = rtrim(str_replace('\\','/',$uploadsRoot), '/');

/* schema tolerant column check */
$colExists = static function(PDO $pdo, string $table, string $col): bool {
  static $cache = [];
  $k = strtolower($table.'.'.$col);
  if (array_key_exists($k, $cache)) return (bool)$cache[$k];
  try {
    $st = $pdo->prepare("
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
        AND COLUMN_NAME = ?
      LIMIT 1
    ");
    $st->execute([$table, $col]);
    $cache[$k] = (bool)$st->fetchColumn();
    return (bool)$cache[$k];
  } catch (Throwable $e) {
    $cache[$k] = false;
    return false;
  }
};

$has = [];
foreach ([
  'id','page_id',
  'is_external','external_url','external_host',
  'stored_path','file_path','stored_name','original_name','created_at'
] as $c) {
  $has[$c] = $colExists($pdo, 'page_files', $c);
}

if (!$has['id'] || !$has['page_id']) {
  echo "ERROR: page_files table missing required columns id/page_id.\n";
  exit;
}

/* Load external validator (if present) */
$validatorOk = false;
$validatorPath = $appRoot . '/private/functions/page_attachments_external.php';
if (is_file($validatorPath)) {
  require_once $validatorPath;
  if (function_exists('mk_pagefile__validate_external_url')) $validatorOk = true;
}

$cols = ['id','page_id'];
foreach (array_keys($has) as $c) {
  if ($c === 'id' || $c === 'page_id') continue;
  if (!empty($has[$c])) $cols[] = $c;
}

$sql = "SELECT " . implode(', ', array_unique($cols)) . " FROM page_files ORDER BY id ASC";
$rows = [];
try {
  $st = $pdo->query($sql);
  $rows = $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
} catch (Throwable $e) {
  echo "ERROR: Failed to query page_files.\n";
  exit;
}

$issues = [];
$counts = [
  'rows_total' => count($rows),
  'rows_external' => 0,
  'rows_local' => 0,
  'local_ok' => 0,
  'local_missing' => 0,
  'local_outside_root' => 0,
  'local_unresolvable' => 0,
  'external_ok' => 0,
  'external_invalid' => 0,
  'external_not_normalized' => 0,
  'disk_files_total' => 0,
  'disk_orphans' => 0,
];

$normLocalRel = static function(string $uploadsRootNorm, array $row): string {
  $stored = trim((string)($row['stored_path'] ?? ''));
  $filep  = trim((string)($row['file_path'] ?? ''));
  $rel = $stored !== '' ? $stored : $filep;
  if ($rel === '') return '';

  $rel = str_replace('\\','/',$rel);

  // If it's like "/lib/uploads/page_files/3/foo.pdf", strip prefix to "3/foo.pdf"
  $needle = '/lib/uploads/page_files/';
  if (strlen($rel) > strlen($needle) && strpos($rel, $needle) === 0) {
    return ltrim(substr($rel, strlen($needle)), '/');
  }

  // If it's absolute under uploads root, strip to relative
  if (strpos($rel, $uploadsRootNorm . '/') === 0) {
    return ltrim(substr($rel, strlen($uploadsRootNorm)), '/');
  }

  // If it's already relative like "3/foo.pdf" keep it
  if ($rel !== '' && $rel[0] !== '/') return ltrim($rel, '/');

  return $rel;
};

$dbLocalRelSet = []; // map rel->true for disk orphan check

foreach ($rows as $r) {
  $id = (int)($r['id'] ?? 0);
  $pid = (int)($r['page_id'] ?? 0);

  $isExternal = false;
  if (array_key_exists('is_external', $r)) $isExternal = ((int)($r['is_external'] ?? 0) === 1);
  if (!$isExternal && !empty($r['external_url'] ?? '')) $isExternal = true;

  if ($isExternal) {
    $counts['rows_external']++;
    $u = trim((string)($r['external_url'] ?? ''));
    if ($u === '' || !$validatorOk) {
      if ($u === '') {
        $counts['external_invalid']++;
        $issues[] = ['type'=>'external_missing_url','id'=>$id,'page_id'=>$pid,'detail'=>'external_url empty'];
      } else {
        // validator missing: not a failure, but report
        $issues[] = ['type'=>'external_validator_missing','id'=>$id,'page_id'=>$pid,'detail'=>'validator not available'];
      }
      continue;
    }

    $v = mk_pagefile__validate_external_url($u);
    if (empty($v['ok']) || empty($v['url'])) {
      $counts['external_invalid']++;
      $issues[] = ['type'=>'external_invalid','id'=>$id,'page_id'=>$pid,'detail'=>(string)($v['error'] ?? 'invalid'), 'url'=>$u];
      continue;
    }

    $counts['external_ok']++;
    $norm = (string)$v['url'];
    if ($norm !== $u) {
      $counts['external_not_normalized']++;
      $issues[] = ['type'=>'external_not_normalized','id'=>$id,'page_id'=>$pid,'detail'=>'stored url differs from normalized', 'stored'=>$u, 'norm'=>$norm];
    }

    continue;
  }

  // local
  $counts['rows_local']++;

  $rel = $normLocalRel($uploadsRootNorm, $r);
  if ($rel === '') {
    $counts['local_unresolvable']++;
    $issues[] = ['type'=>'local_missing_path','id'=>$id,'page_id'=>$pid,'detail'=>'stored_path/file_path empty'];
    continue;
  }

  $dbLocalRelSet[$rel] = true;

  // compute absolute
  $abs = $uploadsRootNorm . '/' . ltrim($rel, '/');
  $real = realpath($abs);

  if (!$real || !is_file($real)) {
    $counts['local_missing']++;
    $issues[] = ['type'=>'local_file_missing','id'=>$id,'page_id'=>$pid,'detail'=>'file not found', 'rel'=>$rel];
    continue;
  }

  $realNorm = str_replace('\\','/',$real);
  if (strpos($realNorm, $uploadsRootNorm . '/') !== 0) {
    $counts['local_outside_root']++;
    $issues[] = ['type'=>'local_outside_root','id'=>$id,'page_id'=>$pid,'detail'=>'realpath outside uploads root', 'real'=>$realNorm];
    continue;
  }

  $counts['local_ok']++;
}

/* Disk orphan scan */
$it = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($uploadsRoot, FilesystemIterator::SKIP_DOTS),
  RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($it as $file) {
  /** @var SplFileInfo $file */
  if (!$file->isFile()) continue;
  $p = str_replace('\\','/',$file->getRealPath() ?: '');
  if ($p === '') continue;
  if (substr($p, -9) === '.htaccess') continue;

  $counts['disk_files_total']++;
  if (strpos($p, $uploadsRootNorm . '/') !== 0) continue;

  $rel = ltrim(substr($p, strlen($uploadsRootNorm)), '/');
  if ($rel === '') continue;

  if (empty($dbLocalRelSet[$rel])) {
    $counts['disk_orphans']++;
    $issues[] = ['type'=>'disk_orphan','detail'=>'file exists on disk but no DB row', 'rel'=>$rel];
  }
}

/* Report */
echo "Rows total:     {$counts['rows_total']}\n";
echo " - External:    {$counts['rows_external']}\n";
echo " - Local:       {$counts['rows_local']}\n";
echo "Local OK:       {$counts['local_ok']}\n";
echo "Local missing:  {$counts['local_missing']}\n";
echo "Local outside:  {$counts['local_outside_root']}\n";
echo "Local no path:  {$counts['local_unresolvable']}\n";
echo "External OK:    {$counts['external_ok']}\n";
echo "External bad:   {$counts['external_invalid']}\n";
echo "External not normalized: {$counts['external_not_normalized']}\n";
echo "Disk files:     {$counts['disk_files_total']}\n";
echo "Disk orphans:   {$counts['disk_orphans']}\n";
echo str_repeat('-', 60) . "\n";

$max = 60;
if (!$issues) {
  echo "No issues found.\n";
} else {
  echo "Issues (showing up to {$max}): " . count($issues) . "\n";
  $shown = 0;
  foreach ($issues as $i) {
    if ($shown >= $max) break;
    echo "- " . json_encode($i, JSON_UNESCAPED_SLASHES) . "\n";
    $shown++;
  }
}

/* Optional JSON save */
$logDir = $appRoot . '/logs/tools';
if (is_dir($logDir) && is_writable($logDir)) {
  $out = [
    'meta' => [
      'tool' => 'diagnostics/attachments_integrity_scan',
      'started_at' => $ts,
      'app_root' => $appRoot,
      'uploads_root' => $uploadsRootNorm,
      'validator_loaded' => $validatorOk,
    ],
    'counts' => $counts,
    'issues' => $issues,
  ];
  $fn = $logDir . '/attachments_integrity_' . gmdate('Ymd_His') . '.json';
  @file_put_contents($fn, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  echo "\nJSON saved: {$fn}\n";
}
