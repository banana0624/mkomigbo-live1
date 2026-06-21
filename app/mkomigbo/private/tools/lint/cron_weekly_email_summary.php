<?php
declare(strict_types=1);

/**
 * /private/tools/lint/cron_weekly_email_summary.php
 * Cron: weekly email summary from quick_scan_history.csv
 *
 * Requires env var:
 * - MK_TOOLS_EMAIL_TO="you@example.com"
 *
 * Safe: if mail() not available or recipient missing -> exits quietly.
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!defined('APP_ROOT')) {
  // // DISABLED_APP_ROOT (DISABLED_AUTO_FIX), '/home/mkomigbo/public_html/app/mkomigbo');
}

$appRoot = rtrim((string)APP_ROOT, "/\\");
if ($appRoot === '' || !is_dir($appRoot)) exit(0);

$to = getenv('MK_TOOLS_EMAIL_TO');
$to = is_string($to) ? trim($to) : '';
if ($to === '' || strpos($to, '@') === false) exit(0);

$csv = $appRoot . '/logs/tools/quick_scan_history.csv';
if (!is_file($csv)) exit(0);

$fh = @fopen($csv, 'rb');
if (!$fh) exit(0);

$header = fgetcsv($fh);
if (!is_array($header)) { fclose($fh); exit(0); }

$rows = [];
while (($r = fgetcsv($fh)) !== false) {
  $row = [];
  foreach ($header as $i => $k) $row[(string)$k] = $r[$i] ?? '';
  $rows[] = $row;
}
fclose($fh);

if (!$rows) exit(0);

$rows = array_slice($rows, -min(30, count($rows)));

$last = $rows[count($rows)-1];
$lastStatus = (string)($last['status'] ?? 'UNKNOWN');
$lastScore  = (int)($last['score'] ?? 0);
$lastCrit   = (int)($last['critical'] ?? 0);
$lastAt     = (string)($last['started_at'] ?? '');

$critTotal = 0;
$minScore = 100;
$maxScore = 0;

foreach ($rows as $r) {
  $critTotal += (int)($r['critical'] ?? 0);
  $s = (int)($r['score'] ?? 0);
  if ($s < $minScore) $minScore = $s;
  if ($s > $maxScore) $maxScore = $s;
}

$subject = "Mkomigbo Tools: Weekly Scan Summary (latest {$lastStatus} {$lastScore}/100)";
$body = [];
$body[] = "Mkomigbo Weekly Tools Summary";
$body[] = "UTC generated: " . gmdate('c');
$body[] = "";
$body[] = "Latest scan:";
$body[] = "  started_at: {$lastAt}";
$body[] = "  status:     {$lastStatus}";
$body[] = "  score:      {$lastScore}/100";
$body[] = "  critical:   {$lastCrit}";
$body[] = "";
$body[] = "Last " . count($rows) . " records:";
$body[] = "  min score: {$minScore}";
$body[] = "  max score: {$maxScore}";
$body[] = "  total critical count (sum): {$critTotal}";
$body[] = "";
$body[] = "Tip: Open /staff/tools/ to view the Health Snapshot + Trend Preview.";

$headers = "From: Mkomigbo Tools <no-reply@" . php_uname('n') . ">\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

@mail($to, $subject, implode("\n", $body), $headers);

exit(0);
