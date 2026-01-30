<?php
declare(strict_types=1);

/**
 * /private/tools/lint/export_history_csv.php
 * Appends latest quick_scan JSON summary to a CSV history.
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
if ($appRoot === '' || !is_dir($appRoot)) { echo "APP_ROOT missing.\n"; return; }

$dir = $appRoot . '/logs/tools';
if (!is_dir($dir)) { echo "logs/tools missing.\n"; return; }

$glob = @glob($dir . '/quick_scan_*.json');
if (!is_array($glob) || !$glob) { echo "No quick_scan JSON found.\n"; return; }

usort($glob, static fn($a,$b) => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
$path = (string)$glob[0];

$raw = @file_get_contents($path);
if (!is_string($raw) || $raw === '') { echo "Cannot read: {$path}\n"; return; }

$data = json_decode($raw, true);
if (!is_array($data)) { echo "Invalid JSON: {$path}\n"; return; }

$meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
$health= is_array($data['health'] ?? null) ? $data['health'] : [];
$stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];

$started = (string)($meta['started_at'] ?? '');
$durMs   = (int)($meta['duration_ms'] ?? 0);

$status  = (string)($health['status'] ?? 'UNKNOWN');
$score   = (int)($health['score'] ?? -1);

$sev = is_array($health['severity_counts'] ?? null) ? $health['severity_counts'] : [];
$crit = (int)($sev['critical'] ?? 0);
$high = (int)($sev['high'] ?? 0);
$med  = (int)($sev['medium'] ?? 0);
$low  = (int)($sev['low'] ?? 0);

$filesScanned = (int)($stats['files_scanned'] ?? 0);
$findTotal    = (int)($stats['findings_total'] ?? 0);

$outCsv = $dir . '/quick_scan_history.csv';
$isNew = !is_file($outCsv);

$fh = @fopen($outCsv, 'ab');
if (!$fh) { echo "Cannot write CSV: {$outCsv}\n"; return; }

if ($isNew) {
  fputcsv($fh, ['started_at','duration_ms','status','score','critical','high','medium','low','files_scanned','findings_total','json_path']);
}

fputcsv($fh, [$started,$durMs,$status,$score,$crit,$high,$med,$low,$filesScanned,$findTotal,basename($path)]);
fclose($fh);

echo "OK: appended to CSV: {$outCsv}\n";
