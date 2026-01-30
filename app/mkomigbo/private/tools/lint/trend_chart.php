<?php
declare(strict_types=1);

/**
 * /private/tools/lint/trend_chart.php
 *
 * Renders a clean HTML+SVG trend chart from:
 *   APP_ROOT/logs/tools/quick_scan_history.csv
 *
 * No external libraries. Read-only. Safe.
 *
 * Expected CSV header:
 * started_at,status,score,critical,high,medium,low,duration_ms,json_file
 *
 * Rendering:
 * - HTML (render=html or render=auto) => full page with cards + SVG + tooltip + table
 * - Otherwise => plain-text summary
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

/* ---------------------------------------------------------
   Single CLI-safe APP_ROOT bootstrap (self-detect)
   - Works in CLI and via /staff/tools/run.php
   - Optional env override: MK_APP_ROOT
--------------------------------------------------------- */
if (!defined('APP_ROOT') || !is_string(APP_ROOT) || trim((string)APP_ROOT) === '') {

  $env = getenv('MK_APP_ROOT');
  if (is_string($env) && $env !== '' && is_dir($env)) {
    define('APP_ROOT', rtrim($env, "/\\"));
  } else {
    // This file should be at: APP_ROOT/private/tools/lint/trend_chart.php
    $dir = __DIR__;
    $found = '';

    for ($i = 0; $i <= 12; $i++) {
      $cand = $dir; // candidate APP_ROOT
      $priv = $cand . '/private';
      $init = $priv . '/assets/initialize.php';
      $logs = $cand . '/logs';

      if (is_dir($priv) && is_dir($logs) && is_file($init)) {
        $found = $cand;
        break;
      }

      $parent = dirname($dir);
      if ($parent === $dir) break;
      $dir = $parent;
    }

    if ($found !== '') {
      define('APP_ROOT', rtrim($found, "/\\"));
    }
  }
}

/* Final validation */
$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
if ($appRoot === '' || !is_dir($appRoot)) {
  header('Content-Type: text/plain; charset=UTF-8');
  echo "APP_ROOT missing or invalid.\n";
  echo "Tip: set MK_APP_ROOT or ensure this file lives under APP_ROOT/private/tools/lint/.\n";
  return;
}

/* ---------------------------------------------------------
   Config
--------------------------------------------------------- */
$logsDir = $appRoot . '/logs/tools';
$csvPath = $logsDir . '/quick_scan_history.csv';

$wantHtml = false;
$render = '';
if (isset($_GET['render']) && is_string($_GET['render'])) $render = strtolower(trim($_GET['render']));
if (isset($_POST['render']) && is_string($_POST['render'])) $render = strtolower(trim($_POST['render']));
if ($render === 'html' || $render === 'auto') $wantHtml = true;

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */
$h = static function(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
};

$clamp = static function(int $v, int $min, int $max): int {
  return max($min, min($max, $v));
};

$formatUtc = static function(string $iso): string {
  $t = strtotime($iso);
  if (!$t) return $iso;
  return gmdate('Y-m-d H:i', $t) . ' UTC';
};

$parseCsv = static function(string $path, int $maxRows = 220): array {
  if (!is_file($path)) return ['ok'=>false, 'reason'=>'CSV missing', 'rows'=>[]];

  $fh = @fopen($path, 'rb');
  if (!$fh) return ['ok'=>false, 'reason'=>'Cannot open CSV', 'rows'=>[]];

  $header = fgetcsv($fh);
  if (!is_array($header) || !$header) {
    fclose($fh);
    return ['ok'=>false, 'reason'=>'Invalid CSV header', 'rows'=>[]];
  }

  $rows = [];
  while (($r = fgetcsv($fh)) !== false) {
    $row = [];
    foreach ($header as $i => $k) {
      $row[(string)$k] = $r[$i] ?? '';
    }
    $rows[] = $row;
    if (count($rows) > 5000) break; // sanity
  }
  fclose($fh);

  if (!$rows) return ['ok'=>false, 'reason'=>'CSV has no rows', 'rows'=>[]];

  usort($rows, static function($a, $b) {
    return strtotime((string)($a['started_at'] ?? '')) <=> strtotime((string)($b['started_at'] ?? ''));
  });

  $rows = array_slice($rows, -max(6, min($maxRows, 500)));

  $out = [];
  foreach ($rows as $r) {
    $started = (string)($r['started_at'] ?? '');
    if ($started === '') continue;

    $score = (int)($r['score'] ?? 0);
    $score = max(0, min(100, $score));

    $status = trim((string)($r['status'] ?? 'UNKNOWN'));
    $status = strtoupper($status);

    $critical = (int)($r['critical'] ?? 0);
    $high     = (int)($r['high'] ?? 0);
    $medium   = (int)($r['medium'] ?? 0);
    $low      = (int)($r['low'] ?? 0);
    $duration = (int)($r['duration_ms'] ?? 0);
    $jsonFile = (string)($r['json_file'] ?? '');

    $isCritical = ($critical > 0 || $status === 'CRITICAL');

    $out[] = [
      'started_at'   => $started,
      'label'        => gmdate('Y-m-d H:i', strtotime($started) ?: 0) . ' UTC',
      'ts'           => strtotime($started) ?: 0,
      'status'       => $status,
      'score'        => $score,
      'critical'     => $critical,
      'high'         => $high,
      'medium'       => $medium,
      'low'          => $low,
      'duration_ms'  => $duration,
      'json_file'    => $jsonFile,
      'is_critical'  => $isCritical,
    ];
  }

  if (count($out) < 2) return ['ok'=>false, 'reason'=>'Not enough data points (need >= 2 rows)', 'rows'=>$out];

  return ['ok'=>true, 'reason'=>'', 'rows'=>$out];
};

/* ---------------------------------------------------------
   Load
--------------------------------------------------------- */
$parsed = $parseCsv($csvPath, 260);
$rows = $parsed['rows'] ?? [];

if (!$parsed['ok']) {
  if ($wantHtml) {
    echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>";
    echo "<title>Quick Scan Trend</title>";
    echo "<style>
      body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f7f7fb;margin:0;padding:16px;color:#111}
      .card{background:#fff;border:1px solid rgba(0,0,0,.10);border-radius:14px;padding:14px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
      .h{margin:0;font-weight:900;font-size:18px}
      .muted{color:#555}
      code{background:#f2f2f7;padding:2px 6px;border-radius:8px}
    </style></head><body>";
    echo "<div class='card'>";
    echo "<h1 class='h'>Quick Scan Trend</h1>";
    echo "<p class='muted' style='margin:8px 0 0 0;'>No trend available: <strong>" . $h((string)$parsed['reason']) . "</strong></p>";
    echo "<p class='muted' style='margin:8px 0 0 0;'>Expected CSV: <code>" . $h($csvPath) . "</code></p>";
    echo "</div></body></html>";
    return;
  }

  echo "Quick Scan Trend\n";
  echo "No trend available: " . (string)$parsed['reason'] . "\n";
  echo "Expected CSV: {$csvPath}\n";
  return;
}

/* ---------------------------------------------------------
   Summary
--------------------------------------------------------- */
$n = count($rows);
$last = $rows[$n - 1];

$minScore = 100;
$maxScore = 0;
$criticalEvents = 0;

foreach ($rows as $r) {
  $s = (int)$r['score'];
  $minScore = min($minScore, $s);
  $maxScore = max($maxScore, $s);
  if (!empty($r['is_critical'])) $criticalEvents++;
}

$firstAt = (string)$rows[0]['started_at'];
$lastAt  = (string)$last['started_at'];

/* ---------------------------------------------------------
   Plain text mode
--------------------------------------------------------- */
if (!$wantHtml) {
  echo "Quick Scan Trend\n";
  echo "CSV: {$csvPath}\n";
  echo "Points: {$n}\n";
  echo "Range: " . $formatUtc($firstAt) . " -> " . $formatUtc($lastAt) . "\n";
  echo "Latest: {$last['status']} score={$last['score']}/100 critical={$last['critical']}\n";
  echo "Min score: {$minScore}  Max score: {$maxScore}\n";
  echo "Critical events: {$criticalEvents}\n";
  return;
}

/* ---------------------------------------------------------
   Premium HTML/SVG render (no libraries)
--------------------------------------------------------- */
$esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$w = 980;
$hSvg = 260;
$padL = 52;
$padR = 18;
$padT = 14;
$padB = 44;

$plotW = $w - $padL - $padR;
$plotH = $hSvg - $padT - $padB;

$minYScore = 0;
$maxYScore = 100;

$xAt = static function(int $i) use ($n, $padL, $plotW): float {
  if ($n <= 1) return (float)$padL;
  return $padL + ($i * ($plotW / ($n - 1)));
};

$yAt = static function(int $score) use ($padT, $plotH, $minYScore, $maxYScore): float {
  $score = max($minYScore, min($maxYScore, $score));
  $t = ($score - $minYScore) / max(1, ($maxYScore - $minYScore));
  return $padT + (1.0 - $t) * $plotH;
};

$poly = [];
$circles = [];
$critRings = [];

for ($i=0; $i<$n; $i++) {
  $p = $rows[$i];
  $x = $xAt($i);
  $y = $yAt((int)$p['score']);
  $poly[] = number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');

  $meta = [
    'label'    => (string)$p['label'],
    'score'    => (int)$p['score'],
    'status'   => (string)$p['status'],
    'critical' => (int)($p['critical'] ?? 0),
    'high'     => (int)($p['high'] ?? 0),
    'medium'   => (int)($p['medium'] ?? 0),
    'low'      => (int)($p['low'] ?? 0),
  ];

  $circles[] =
    "<circle class='pt' cx='{$x}' cy='{$y}' r='5' fill='rgba(17,24,39,.85)' data-meta='" . $esc(json_encode($meta)) . "'/>";

  if (!empty($p['is_critical'])) {
    $critRings[] = "<circle cx='{$x}' cy='{$y}' r='9' fill='none' stroke='rgba(239,68,68,.85)' stroke-width='2'/>";
  }
}

$polyStr = implode(' ', $poly);

$latestLabel  = (string)$last['label'];
$latestStatus = (string)$last['status'];
$latestScore  = (int)$last['score'];
$latestCrit   = (int)$last['critical'];

$tone = 'muted';
if ($latestStatus === 'OK') $tone = 'ok';
elseif ($latestStatus === 'WARN') $tone = 'warn';
elseif ($latestStatus === 'AT RISK') $tone = 'risk';
elseif ($latestStatus === 'CRITICAL') $tone = 'crit';

$firstLabel = (string)$rows[0]['label'];
$midLabel   = (string)$rows[(int)floor(($n-1)/2)]['label'];

echo "<!doctype html><html><head><meta charset='utf-8'>";
echo "<meta name='viewport' content='width=device-width,initial-scale=1'>";
echo "<title>Quick Scan Trend</title>";
echo "<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;padding:16px;background:#f7f7fb;color:#111}
  .wrap{max-width:1100px;margin:0 auto}
  .card{background:#fff;border:1px solid rgba(0,0,0,.10);border-radius:16px;padding:14px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
  .row{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap}
  h1{margin:0;font-size:18px;font-weight:900}
  .muted{color:#555}
  .pill{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:900;border:1px solid rgba(0,0,0,.12)}
  .pill.ok{background:#eefbf1;color:#14532d;border-color:rgba(34,197,94,.30)}
  .pill.warn{background:#fff7e8;color:#7c2d12;border-color:rgba(245,158,11,.32)}
  .pill.risk{background:#ffecec;color:#7f1d1d;border-color:rgba(239,68,68,.24)}
  .pill.crit{background:#ffdede;color:#7f1d1d;border-color:rgba(239,68,68,.34)}
  .pill.muted{background:rgba(0,0,0,.05);color:rgba(0,0,0,.70)}
  .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-top:10px}
  .k{background:#fff;border:1px solid rgba(0,0,0,.10);border-radius:14px;padding:12px}
  .k .n{font-size:20px;font-weight:900}
  .k .l{font-size:12px;color:#555}
  svg{display:block;width:100%;height:auto;background:#fff;border:1px solid rgba(0,0,0,.10);border-radius:16px}
  .gridline{stroke:rgba(0,0,0,.08);stroke-width:1}
  .axis{fill:rgba(0,0,0,.70);font-size:12px}
  .line{stroke:rgba(17,24,39,.85);stroke-width:3;fill:none}
  .pt{cursor:pointer}
  .tip{position:fixed;pointer-events:none;display:none;z-index:50;background:#111;color:#fff;padding:8px 10px;border-radius:10px;font-size:12px;max-width:320px}
  .tip b{display:block;margin-bottom:2px}
  table{width:100%;border-collapse:collapse;margin-top:10px}
  th,td{border-top:1px solid rgba(0,0,0,.08);padding:8px 6px;text-align:left;vertical-align:top;font-size:12px}
  th{color:#444;font-weight:900;background:#fafafe}
  code{background:#f2f2f7;padding:2px 6px;border-radius:8px}
</style>";
echo "</head><body><div class='wrap'>";

echo "<div class='card'>";
echo "<div class='row'>";
echo "  <div>";
echo "    <h1>Quick Scan Trend</h1>";
echo "    <div class='muted' style='margin-top:4px'>CSV: <code>{$esc($csvPath)}</code> • Points: {$n}</div>";
echo "  </div>";
echo "  <div class='pill {$tone}'>Status: {$esc($latestStatus)} • Score: {$latestScore}/100 • Critical: {$latestCrit}</div>";
echo "</div>";

echo "<div class='kpis'>";
echo "  <div class='k'><div class='n'>{$esc($firstLabel)}</div><div class='l'>First scan</div></div>";
echo "  <div class='k'><div class='n'>{$esc($midLabel)}</div><div class='l'>Middle</div></div>";
echo "  <div class='k'><div class='n'>{$esc($latestLabel)}</div><div class='l'>Latest</div></div>";
echo "</div>";

echo "<div style='margin-top:12px'>";
echo "<svg viewBox='0 0 {$w} {$hSvg}' role='img' aria-label='Quick scan score trend'>";

foreach ([0,25,50,75,100] as $v) {
  $y = $yAt((int)$v);
  echo "<line class='gridline' x1='{$padL}' y1='{$y}' x2='" . ($w-$padR) . "' y2='{$y}'/>";
  echo "<text class='axis' x='10' y='" . ($y+4) . "'>{$v}</text>";
}

$x0 = $xAt(0);
$xm = $xAt((int)floor(($n-1)/2));
$xL = $xAt($n-1);
echo "<text class='axis' x='{$x0}' y='" . ($hSvg-16) . "' text-anchor='start'>{$esc($firstLabel)}</text>";
echo "<text class='axis' x='{$xm}' y='" . ($hSvg-16) . "' text-anchor='middle'>{$esc($midLabel)}</text>";
echo "<text class='axis' x='{$xL}' y='" . ($hSvg-16) . "' text-anchor='end'>{$esc($latestLabel)}</text>";

echo "<polyline class='line' points='{$polyStr}'/>";
echo implode('', $critRings);
echo implode('', $circles);
echo "</svg>";
echo "</div>";

echo "<div class='muted' style='margin-top:10px;font-size:12px'>Hover a point to see details. Red rings indicate CRITICAL scans.</div>";

/* latest records table */
echo "<div style='margin-top:12px;font-weight:900'>Latest records</div>";
echo "<div style='overflow:auto;border:1px solid rgba(0,0,0,.10);border-radius:14px;background:#fff'>";
echo "<table>";
echo "<thead><tr>";
foreach (['started_at','status','score','critical','high','medium','low','duration_ms','json_file'] as $c) {
  echo "<th>{$esc($c)}</th>";
}
echo "</tr></thead><tbody>";

$tail = array_slice($rows, -min(12, count($rows)));
foreach ($tail as $r) {
  $bg = !empty($r['is_critical']) ? " style='background:rgba(239,68,68,.06)'" : "";
  echo "<tr{$bg}>";
  echo "<td>{$esc($formatUtc((string)$r['started_at']))}</td>";
  echo "<td><strong>{$esc((string)$r['status'])}</strong></td>";
  echo "<td>" . (int)$r['score'] . "</td>";
  echo "<td>" . (int)$r['critical'] . "</td>";
  echo "<td>" . (int)$r['high'] . "</td>";
  echo "<td>" . (int)$r['medium'] . "</td>";
  echo "<td>" . (int)$r['low'] . "</td>";
  echo "<td>" . (int)$r['duration_ms'] . "</td>";
  echo "<td><code>{$esc((string)$r['json_file'])}</code></td>";
  echo "</tr>";
}
echo "</tbody></table></div>";

echo "</div>"; // card

echo "<div class='tip' id='tip'></div>";

echo "<script>
  const tip = document.getElementById('tip');
  document.querySelectorAll('circle.pt').forEach(pt => {
    pt.addEventListener('mousemove', (e) => {
      let meta = {};
      try { meta = JSON.parse(pt.getAttribute('data-meta') || '{}'); } catch {}
      tip.style.display = 'block';
      tip.style.left = (e.clientX + 12) + 'px';
      tip.style.top  = (e.clientY + 12) + 'px';
      tip.innerHTML =
        '<b>' + (meta.label || '') + '</b>' +
        'Status: ' + (meta.status || '') + '<br>' +
        'Score: ' + (meta.score ?? '') + '/100<br>' +
        'Critical: ' + (meta.critical ?? 0) + '<br>' +
        'High: ' + (meta.high ?? 0) + ' • Medium: ' + (meta.medium ?? 0) + ' • Low: ' + (meta.low ?? 0);
    });
    pt.addEventListener('mouseleave', () => { tip.style.display = 'none'; });
  });
</script>";

echo "</div></body></html>";
return;
