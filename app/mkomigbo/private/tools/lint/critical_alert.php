<?php
declare(strict_types=1);

/**
 * /private/tools/lint/critical_alert.php
 * Reads latest quick_scan JSON and raises/clears a CRITICAL flag.
 * Non-destructive: only writes under APP_ROOT/logs/tools/
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
if ($appRoot === '' || !is_dir($appRoot)) { echo "APP_ROOT missing.\n"; return; }

$dir = $appRoot . '/logs/tools';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }

$glob = @glob($dir . '/quick_scan_*.json');
if (!is_array($glob) || !$glob) { echo "No quick_scan JSON found.\n"; return; }

usort($glob, static fn($a,$b) => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
$path = (string)$glob[0];

$raw = @file_get_contents($path);
$data = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($data)) { echo "Invalid JSON: {$path}\n"; return; }

$health = is_array($data['health'] ?? null) ? $data['health'] : [];
$status = (string)($health['status'] ?? 'UNKNOWN');

$sev = is_array($health['severity_counts'] ?? null) ? $health['severity_counts'] : [];
$crit = (int)($sev['critical'] ?? 0);

$isCritical = ($status === 'CRITICAL' || $crit > 0);

$flag = $dir . '/CRITICAL.flag';
$log  = $dir . '/critical_alerts.log';
$now  = gmdate('c');

if ($isCritical) {
  $payload = [
    'at' => $now,
    'status' => $status,
    'critical' => $crit,
    'json' => basename($path),
  ];
  @file_put_contents($flag, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  @file_put_contents($log, "[{$now}] CRITICAL status={$status} critical={$crit} json=" . basename($path) . "\n", FILE_APPEND);
  echo "CRITICAL: flag raised.\n";
} else {
  if (is_file($flag)) {
    @unlink($flag);
    @file_put_contents($log, "[{$now}] Cleared CRITICAL flag. json=" . basename($path) . "\n", FILE_APPEND);
  }
  echo "OK: no critical.\n";
}
