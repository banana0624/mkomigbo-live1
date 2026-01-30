<?php
declare(strict_types=1);

/**
 * /private/tools/lint/cron_export_history_csv.php
 * Cron: export latest quick_scan JSON -> append CSV history row.
 *
 * Non-destructive. Writes:
 * - APP_ROOT/logs/tools/quick_scan_history.csv
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!defined('APP_ROOT')) {
  define('APP_ROOT', '/home/mkomigbo/public_html/app/mkomigbo');
}

$appRoot = rtrim((string)APP_ROOT, "/\\");
if ($appRoot === '' || !is_dir($appRoot)) {
  fwrite(STDOUT, "APP_ROOT invalid: {$appRoot}\n");
  exit(1);
}

$logsDir = $appRoot . '/logs/tools';
if (!is_dir($logsDir)) { @mkdir($logsDir, 0755, true); }

if (!is_dir($logsDir) || !is_writable($logsDir)) {
  fwrite(STDOUT, "logs/tools missing or not writable: {$logsDir}\n");
  exit(1);
}

$glob = @glob($logsDir . '/quick_scan_*.json');
if (!is_array($glob) || !$glob) {
  fwrite(STDOUT, "No quick_scan JSON found.\n");
  exit(0);
}

usort($glob, static function($a, $b) {
  $ta = @filemtime((string)$a) ?: 0;
  $tb = @filemtime((string)$b) ?: 0;
  return $tb <=> $ta;
});

$latest = (string)$glob[0];
$raw = @file_get_contents($latest);
if (!is_string($raw) || trim($raw) === '') {
  fwrite(STDOUT, "Cannot read JSON: {$latest}\n");
  exit(1);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
  fwrite(STDOUT, "Invalid JSON: {$latest}\n");
  exit(1);
}

$meta = $data['meta'] ?? [];
$health = $data['health'] ?? [];
$sev = is_array($health) && isset($health['severity_counts']) && is_array($health['severity_counts'])
  ? $health['severity_counts']
  : [];

$startedAt = (is_array($meta) && isset($meta['started_at'])) ? (string)$meta['started_at'] : '';
$duration  = (is_array($meta) && isset($meta['duration_ms'])) ? (int)$meta['duration_ms'] : 0;

$status = (is_array($health) && isset($health['status'])) ? (string)$health['status'] : 'UNKNOWN';
$score  = (is_array($health) && isset($health['score'])) ? (int)$health['score'] : 0;

$critical = isset($sev['critical']) ? (int)$sev['critical'] : 0;
$high     = isset($sev['high']) ? (int)$sev['high'] : 0;
$medium   = isset($sev['medium']) ? (int)$sev['medium'] : 0;
$low      = isset($sev['low']) ? (int)$sev['low'] : 0;

if ($startedAt === '') {
  fwrite(STDOUT, "Missing started_at in JSON.\n");
  exit(1);
}

$csv = $logsDir . '/quick_scan_history.csv';
$header = ['started_at','status','score','critical','high','medium','low','duration_ms','json_file'];

$exists = is_file($csv);

/* de-dupe: if started_at already exists, do nothing */
if ($exists) {
  $fh = @fopen($csv, 'rb');
  if ($fh) {
    $hdr = fgetcsv($fh);
    while (($r = fgetcsv($fh)) !== false) {
      $idx = array_search('started_at', (array)$hdr, true);
      if ($idx !== false && isset($r[$idx]) && (string)$r[$idx] === $startedAt) {
        fclose($fh);
        exit(0);
      }
    }
    fclose($fh);
  }
}

$fh = @fopen($csv, $exists ? 'ab' : 'wb');
if (!$fh) {
  fwrite(STDOUT, "Cannot open CSV: {$csv}\n");
  exit(1);
}

if (!$exists) {
  fputcsv($fh, $header);
}

$row = [
  $startedAt,
  $status,
  (string)$score,
  (string)$critical,
  (string)$high,
  (string)$medium,
  (string)$low,
  (string)$duration,
  basename($latest),
];

fputcsv($fh, $row);
fclose($fh);

exit(0);
