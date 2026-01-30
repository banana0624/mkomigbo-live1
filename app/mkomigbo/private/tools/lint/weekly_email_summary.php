<?php
declare(strict_types=1);

/**
 * /private/tools/lint/weekly_email_summary.php
 * Builds a weekly summary from quick_scan_history.csv and sends via mail().
 * If no recipient configured, prints the email to STDOUT.
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
if ($appRoot === '' || !is_dir($appRoot)) { echo "APP_ROOT missing.\n"; return; }

$dir = $appRoot . '/logs/tools';
$csv = $dir . '/quick_scan_history.csv';
if (!is_file($csv)) { echo "Missing CSV history: {$csv}\n"; return; }

$rows = [];
if (($fh = @fopen($csv, 'rb')) !== false) {
  $header = fgetcsv($fh);
  if (is_array($header)) {
    while (($r = fgetcsv($fh)) !== false) {
      $row = [];
      foreach ($header as $i => $k) { $row[(string)$k] = $r[$i] ?? ''; }
      $rows[] = $row;
    }
  }
  fclose($fh);
}

if (!$rows) { echo "No rows in history.\n"; return; }

$since = time() - (7 * 24 * 3600);
$week = [];
foreach ($rows as $r) {
  $t = strtotime((string)($r['started_at'] ?? ''));
  if ($t !== false && $t >= $since) $week[] = $r;
}
if (!$week) { echo "No scans in last 7 days.\n"; return; }

$latest = $week[0];
foreach ($week as $r) {
  if (strtotime((string)$r['started_at']) > strtotime((string)$latest['started_at'])) $latest = $r;
}

$scores = [];
$critSum = 0;
$critMax = 0;
foreach ($week as $r) {
  $s = (int)($r['score'] ?? 0);
  $scores[] = $s;
  $c = (int)($r['critical'] ?? 0);
  $critSum += $c;
  if ($c > $critMax) $critMax = $c;
}

sort($scores);
$min = $scores[0];
$max = $scores[count($scores)-1];
$avg = (int)round(array_sum($scores) / max(1, count($scores)));

$subject = 'Mkomigbo Weekly Health Summary (Tools)';
$body = "";
$body .= "Mkomigbo Weekly Health Summary\n";
$body .= "==============================\n\n";
$body .= "Scans in last 7 days: " . count($week) . "\n";
$body .= "Score: min={$min}, avg={$avg}, max={$max}\n";
$body .= "Critical findings: total={$critSum}, peak={$critMax}\n\n";
$body .= "Latest scan:\n";
$body .= "- started_at: " . (string)($latest['started_at'] ?? '') . "\n";
$body .= "- status:     " . (string)($latest['status'] ?? '') . "\n";
$body .= "- score:      " . (string)($latest['score'] ?? '') . "\n";
$body .= "- critical:   " . (string)($latest['critical'] ?? '') . "\n";
$body .= "- json:       " . (string)($latest['json_path'] ?? '') . "\n\n";
$body .= "Tip: open /staff/tools/ to view snapshot + run Quick Scan for details.\n";

$to = (string)getenv('MK_TOOLS_EMAIL_TO');
$from = (string)getenv('MK_TOOLS_EMAIL_FROM');

if ($to === '') {
  echo "No MK_TOOLS_EMAIL_TO set. Email not sent.\n\n";
  echo "SUBJECT: {$subject}\n\n{$body}\n";
  return;
}

$headers = [];
if ($from !== '') $headers[] = "From: {$from}";
$headers[] = "Content-Type: text/plain; charset=UTF-8";

$ok = @mail($to, $subject, $body, implode("\r\n", $headers));
echo $ok ? "OK: weekly email sent to {$to}\n" : "FAIL: mail() failed (hosting mail config).\n";
