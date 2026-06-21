<?php
declare(strict_types=1);

/**
 * /private/tools/lint/cron_critical_alert.php
 * Cron: checks latest quick_scan JSON; if CRITICAL, writes a flag file.
 *
 * Output:
 * - APP_ROOT/logs/tools/critical_alert.flag (only when critical)
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!defined('APP_ROOT')) {
  // // DISABLED_APP_ROOT (DISABLED_AUTO_FIX), '/home/mkomigbo/public_html/app/mkomigbo');
}

$appRoot = rtrim((string)APP_ROOT, "/\\");
if ($appRoot === '' || !is_dir($appRoot)) {
  fwrite(STDOUT, "APP_ROOT invalid: {$appRoot}\n");
  exit(1);
}

$logsDir = $appRoot . '/logs/tools';
if (!is_dir($logsDir)) { @mkdir($logsDir, 0755, true); }

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
if (!is_string($raw) || trim($raw) === '') exit(0);

$data = json_decode($raw, true);
if (!is_array($data)) exit(0);

$health = $data['health'] ?? [];
$meta   = $data['meta'] ?? [];

$status = (is_array($health) && isset($health['status'])) ? strtoupper((string)$health['status']) : 'UNKNOWN';
$score  = (is_array($health) && isset($health['score'])) ? (int)$health['score'] : 0;

$sev = (is_array($health) && isset($health['severity_counts']) && is_array($health['severity_counts']))
  ? $health['severity_counts'] : [];

$critical = isset($sev['critical']) ? (int)$sev['critical'] : 0;

$startedAt = (is_array($meta) && isset($meta['started_at'])) ? (string)$meta['started_at'] : gmdate('c');

$isCritical = ($status === 'CRITICAL' || $critical > 0);

$flag = $logsDir . '/critical_alert.flag';

if ($isCritical) {
  $msg = [
    'time' => gmdate('c'),
    'scan_started_at' => $startedAt,
    'status' => $status,
    'score' => $score,
    'critical' => $critical,
    'json' => basename($latest),
  ];
  @file_put_contents($flag, json_encode($msg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}

exit(0);
