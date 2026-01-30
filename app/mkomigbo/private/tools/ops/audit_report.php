<?php
declare(strict_types=1);

/**
 * audit_report.php (CLI)
 * Reads latest project_audit_*.json and prints FAIL/WARN counts + top codes.
 *
 * Usage:
 *   php audit_report.php
 */

$logsDir = "/home/mkomigbo/public_html/app/mkomigbo/logs";

$latest = trim((string)shell_exec("ls -1t " . escapeshellarg($logsDir) . "/project_audit_*.json 2>/dev/null | head -n 1"));
if ($latest === "" || !is_file($latest)) {
  fwrite(STDERR, "No audit JSON found in: {$logsDir}\n");
  exit(1);
}

$j = json_decode((string)file_get_contents($latest), true);
if (!is_array($j)) {
  fwrite(STDERR, "Bad JSON: {$latest}\n");
  exit(1);
}

$find = $j["findings"] ?? [];
if (!is_array($find)) $find = [];

$fail = $find["fail"] ?? [];
$warn = $find["warn"] ?? [];
$info = $find["info"] ?? [];

echo "AUDIT: {$latest}\n\n";

echo "FAIL:\n";
if (!is_array($fail) || count($fail) === 0) {
  echo "  (none)\n";
} else {
  $codes = [];
  foreach ($fail as $it) {
    $code = (string)($it["code"] ?? "FAIL");
    $codes[$code] = ($codes[$code] ?? 0) + 1;
  }
  arsort($codes);
  foreach ($codes as $c => $n) echo "  " . str_pad((string)$n, 4, " ", STR_PAD_LEFT) . "  {$c}\n";
}

echo "\nWARN:\n";
if (!is_array($warn) || count($warn) === 0) {
  echo "  (none)\n";
} else {
  $codes = [];
  foreach ($warn as $it) {
    $code = (string)($it["code"] ?? "WARN");
    $codes[$code] = ($codes[$code] ?? 0) + 1;
  }
  arsort($codes);
  foreach ($codes as $c => $n) echo "  " . str_pad((string)$n, 4, " ", STR_PAD_LEFT) . "  {$c}\n";
}

echo "\nCOUNTS:\n";
echo "  fail=" . (is_array($fail) ? count($fail) : 0) . "\n";
echo "  warn=" . (is_array($warn) ? count($warn) : 0) . "\n";
echo "  info=" . (is_array($info) ? count($info) : 0) . "\n";
