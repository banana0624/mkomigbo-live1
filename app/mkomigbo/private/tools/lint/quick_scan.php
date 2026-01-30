<?php
declare(strict_types=1);

/**
 * /private/tools/lint/quick_scan.php
 *
 * Robust read-only diagnostics scan:
 * - Scans APP_ROOT/private + project /public (auto-detected)
 * - Excludes /private/tools/** (prevents self-flagging and tool noise)
 * - Excludes backups like staff_tools.bak_*
 * - Writes timestamped JSON report to APP_ROOT/logs/tools/
 * - Outputs either:
 *    - HTML report (render=html|auto)
 *    - Plain-text (PRE-friendly)
 *
 * Safety: read-only project scan; only writes JSON report into /logs/tools.
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$startedAt  = microtime(true);
$startedIso = gmdate('c');

/* ---------------------------------------------------------
   Locate APP_ROOT (safe)
--------------------------------------------------------- */
$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
if ($appRoot === '' || !is_dir($appRoot)) {
  echo "APP_ROOT missing or invalid.\n";
  return;
}

/* ---------------------------------------------------------
   Determine scan roots (private + public)
--------------------------------------------------------- */
$roots = [];

/* private root */
$private = $appRoot . '/private';
if (is_dir($private)) {
  $roots['private'] = realpath($private) ?: $private;
}

/* public root: search upward for a sibling /public */
$publicFound = '';
$base = $appRoot;
for ($i = 0; $i <= 8; $i++) {
  $cand = rtrim($base, "/\\") . '/public';
  if (is_dir($cand)) { $publicFound = $cand; break; }
  $parent = dirname($base);
  if ($parent === $base) break;
  $base = $parent;
}
if ($publicFound !== '') {
  $roots['public'] = realpath($publicFound) ?: $publicFound;
}

if (!$roots) {
  echo "No scan roots found.\n";
  return;
}

/* ---------------------------------------------------------
   Exclusions (critical to accuracy + performance)
--------------------------------------------------------- */
$excludeDirNeedles = [
  // Never scan tools directory (prevents self-flagging & internal noise)
  str_replace('\\', '/', $appRoot . '/private/tools/'),

  // Noise / size / not relevant to code health checks
  str_replace('\\', '/', $appRoot . '/vendor/'),
  str_replace('\\', '/', $appRoot . '/logs/'),
  str_replace('\\', '/', $appRoot . '/private/logs/'),
  str_replace('\\', '/', $appRoot . '/private/cache/'),
  str_replace('\\', '/', $appRoot . '/cache/'),
];

/* exclude backup-like dirs anywhere under private */
$excludePathRegexes = [
  '~/(?:staff_tools\.bak_[^/]+|\.bak(?:/|$)|backup(?:/|$)|backups(?:/|$))/~i',
  '~/(?:node_modules|\.git|\.svn|\.hg|\.idea|\.vscode)/~i',
  '~/(?:tmp|temp)(?:/|$)~i',
];

/* quick helper */
$pathIsExcluded = static function(string $path) use ($excludeDirNeedles, $excludePathRegexes): bool {
  $p = str_replace('\\', '/', $path);

  foreach ($excludeDirNeedles as $needle) {
    if ($needle !== '' && stripos($p, $needle) !== false) return true;
  }
  foreach ($excludePathRegexes as $rx) {
    if (@preg_match($rx, $p)) return true;
  }
  return false;
};

/* ---------------------------------------------------------
   Scan config
--------------------------------------------------------- */
$scanExts = [
  'php','phtml','html','htm','css','js','json','sql','md','txt','xml','svg','yml','yaml','htaccess'
];

$maxBytesPerFile = 2_000_000; // 2MB cap
$maxFindings     = 1200;      // hard cap
$maxPerRule      = 120;
$maxListInHtml   = 40;

/* ---------------------------------------------------------
   Rules (avoid self-flagging by excluding /private/tools/)
--------------------------------------------------------- */
$needleEval   = 'ev' . 'al(';
$needleB64    = 'base64_' . 'decode(';
$needleShell  = 'shell_' . 'exec(';

/* Patterns with severity */
$rules = [
  // Security & execution hazards
  [
    'id' => 'php_eval',
    'label' => 'Suspicious: eval(',
    'severity' => 'critical',
    'needle' => $needleEval,
    'ext' => ['php','phtml'],
  ],
  [
    'id' => 'php_base64_decode',
    'label' => 'Suspicious: base64_decode(',
    'severity' => 'critical',
    'needle' => $needleB64,
    'ext' => ['php','phtml'],
  ],
  [
    'id' => 'php_shell_exec',
    'label' => 'Suspicious: shell_exec(',
    'severity' => 'high',
    'needle' => $needleShell,
    'ext' => ['php','phtml'],
  ],
  [
    'id'       => 'php_system',
    'label'    => 'Suspicious: system()/exec()/passthru()',
    'severity' => 'high',
    'ext'      => ['php','phtml'],
    'match'    => static function (string $content): bool {
      // Match global function calls only, NOT object methods like $pdo->exec()
      // Catches:  exec(, system(, passthru(
      // Skips:    ->exec(
      return (bool)preg_match('/(?<!->)\b(?:system|exec|passthru)\s*\(/i', $content);
    },
  ],

  // Merge conflict markers
  [
    'id' => 'merge_conflict',
    'label' => 'Merge conflict markers (<<<<<<< / ======= / >>>>>>>)',
    'severity' => 'critical',
    'regex' => '~^(<<<<<<<|=======|>>>>>>>)~m',
    'ext' => null,
  ],

  // Encoding / BOM
  [
    'id' => 'utf8_bom',
    'label' => 'UTF-8 BOM detected',
    'severity' => 'medium',
    'needle_bytes' => "\xEF\xBB\xBF",
    'ext' => null,
  ],

  // PHP short tags (can break on some hosts)
  [
    'id' => 'php_short_open',
    'label' => 'PHP short open tag found (<?)',
    'severity' => 'medium',
    'regex' => '~<\?(?!php|=)~i',
    'ext' => ['php','phtml'],
  ],

  // HTML issues (heuristics)
  [
    'id' => 'html_empty_href',
    'label' => 'HTML: empty href=""',
    'severity' => 'low',
    // STRICT: only matches real href="" or href='' in actual HTML.
    // Avoids false-positives on PHP strings like: href="' . h($href) . '"
    'regex' => '~<a[^>]+href=(["\x27])\s*\1~i',
    'ext' => ['html','htm','php','phtml'],
  ],
  [
    'id' => 'html_missing_alt_img',
    'label' => 'HTML: possible <img> without alt= (heuristic)',
    'severity' => 'low',
    'regex' => '~<img\b(?![^>]*\balt=)[^>]*>~i',
    'ext' => ['html','htm','php','phtml'],
  ],

  // CSS issues (heuristics)
  [
    'id' => 'css_unbalanced_braces',
    'label' => 'CSS: unbalanced braces (heuristic)',
    'severity' => 'medium',
    'custom' => 'css_unbalanced',
    'ext' => ['css'],
  ],
  [
    'id' => 'css_double_semicolon',
    'label' => 'CSS: double semicolon (;;)',
    'severity' => 'low',
    'needle' => ';;',
    'ext' => ['css'],
  ],

  // SQL hazards (review flags)
  [
    'id' => 'sql_drop_table',
    'label' => 'SQL: DROP TABLE present (review)',
    'severity' => 'high',
    'regex' => '~\bdrop\s+table\b~i',
    'ext' => ['sql','php','phtml'],
  ],
  [
    'id' => 'sql_truncate',
    'label' => 'SQL: TRUNCATE present (review)',
    'severity' => 'high',
    'regex' => '~\btruncate\b~i',
    'ext' => ['sql','php','phtml'],
  ],
];

/* ---------------------------------------------------------
   Internal state
--------------------------------------------------------- */
$stats = [
  'started_at' => $startedIso,
  'app_root'   => $appRoot,
  'roots'      => $roots,
  'files_scanned' => 0,
  'bytes_read'    => 0,
  'counts_by_ext' => [],
  'zero_byte_files' => 0,
  'unreadable_files' => 0,
  'findings_total' => 0,
  'findings_by_severity' => ['critical'=>0,'high'=>0,'medium'=>0,'low'=>0],
  'findings_by_rule' => [],
  'excluded_files' => 0,
];

$findings = []; // ['severity','rule_id','rule','file','detail']
$severityRank = ['critical'=>4,'high'=>3,'medium'=>2,'low'=>1];

$addFinding = static function(string $severity, string $ruleId, string $ruleLabel, string $file, string $detail = '') use (
  &$findings, &$stats, $maxFindings
): void {
  if ($stats['findings_total'] >= $maxFindings) return;

  $findings[] = [
    'severity' => $severity,
    'rule_id'  => $ruleId,
    'rule'     => $ruleLabel,
    'file'     => $file,
    'detail'   => $detail,
  ];

  $stats['findings_total']++;
  $stats['findings_by_severity'][$severity] = (int)($stats['findings_by_severity'][$severity] ?? 0) + 1;

  if (!isset($stats['findings_by_rule'][$ruleId])) {
    $stats['findings_by_rule'][$ruleId] = ['label'=>$ruleLabel,'severity'=>$severity,'count'=>0];
  }
  $stats['findings_by_rule'][$ruleId]['count']++;
};

/* Walk files (with exclusions) */
$walk = static function(string $dir) use ($scanExts, $pathIsExcluded, &$stats): array {
  $files = [];
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
  );

  foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $path = $f->getPathname();

    if ($pathIsExcluded($path)) { $stats['excluded_files']++; continue; }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === '' && basename($path) === '.htaccess') $ext = 'htaccess';
    if (!in_array($ext, $scanExts, true)) continue;

    $files[] = $path;
  }
  return $files;
};

/* Read file (bounded) */
$readFile = static function(string $path, int $maxBytes, int &$bytesRead): ?string {
  $size = @filesize($path);
  if (!is_int($size) || $size < 0) return null;

  $fh = @fopen($path, 'rb');
  if (!$fh) return null;

  $data = @fread($fh, min($size, $maxBytes));
  @fclose($fh);

  if (!is_string($data)) return null;
  $bytesRead += strlen($data);
  return $data;
};

/* ---------------------------------------------------------
   Run scan
--------------------------------------------------------- */
foreach ($roots as $rootName => $dir) {
  $dir = (string)$dir;
  echo "Scanning {$rootName}: {$dir}\n";

  $files = $walk($dir);
  foreach ($files as $path) {

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === '' && basename($path) === '.htaccess') $ext = 'htaccess';

    $stats['counts_by_ext'][$ext] = (int)($stats['counts_by_ext'][$ext] ?? 0) + 1;

    $size = @filesize($path);
    if ($size === 0) {
      $stats['zero_byte_files']++;
      $addFinding('medium', 'zero_byte', 'Zero-byte file', $path, '0 bytes');
      continue;
    }

    if (!is_readable($path)) {
      $stats['unreadable_files']++;
      $addFinding('high', 'unreadable', 'Unreadable file', $path, 'not readable');
      continue;
    }

    $data = $readFile($path, $maxBytesPerFile, $stats['bytes_read']);
    $stats['files_scanned']++;

    if ($data === null || $data === '') continue;

    foreach ($rules as $rule) {
      $rid    = (string)$rule['id'];
      $rlabel = (string)$rule['label'];
      $sev    = (string)$rule['severity'];

      $allowedExt = $rule['ext'] ?? null;
      if (is_array($allowedExt) && !in_array($ext, $allowedExt, true)) continue;

      $currentCount = (int)($stats['findings_by_rule'][$rid]['count'] ?? 0);
      if ($currentCount >= $maxPerRule) continue;

      // Custom CSS brace check
      if (($rule['custom'] ?? '') === 'css_unbalanced' && $ext === 'css') {
        $open  = substr_count($data, '{');
        $close = substr_count($data, '}');
        if ($open !== $close) {
          $addFinding($sev, $rid, $rlabel, $path, "{={$open} }={$close}");
        }
        continue;
      }

      // Byte needle (BOM)
      if (isset($rule['needle_bytes']) && is_string($rule['needle_bytes'])) {
        if (strncmp($data, $rule['needle_bytes'], strlen($rule['needle_bytes'])) === 0) {
          $addFinding($sev, $rid, $rlabel, $path, 'BOM present');
        }
        continue;
      }

      // Needle
      if (isset($rule['needle']) && is_string($rule['needle'])) {
        if (stripos($data, $rule['needle']) !== false) {
          $addFinding($sev, $rid, $rlabel, $path);
        }
        continue;
      }

      // Match callback (IMPORTANT: supports php_system rule)
      if (isset($rule['match']) && is_callable($rule['match'])) {
        try {
          if ((bool)call_user_func($rule['match'], $data)) {
            $addFinding($sev, $rid, $rlabel, $path);
          }
        } catch (Throwable $e) {
          // ignore broken rules; tool must remain safe
        }
        continue;
      }

      // Regex
      if (isset($rule['regex']) && is_string($rule['regex'])) {
        if (@preg_match($rule['regex'], $data)) {
          $addFinding($sev, $rid, $rlabel, $path);
        }
        continue;
      }
    }

    if ($stats['findings_total'] >= $maxFindings) {
      $addFinding('low', 'truncated', 'Findings truncated', $path, 'Reached max findings cap');
      break 2;
    }
  }

  echo "\n";
}

/* ---------------------------------------------------------
   Compute health score
--------------------------------------------------------- */
$crit = (int)($stats['findings_by_severity']['critical'] ?? 0);
$high = (int)($stats['findings_by_severity']['high'] ?? 0);
$med  = (int)($stats['findings_by_severity']['medium'] ?? 0);
$low  = (int)($stats['findings_by_severity']['low'] ?? 0);

$score = 100;
$score -= min(80, $crit * 28);
$score -= min(45, $high * 9);
$score -= min(28, $med * 3);
$score -= min(12, $low * 1);
if ($score < 0) $score = 0;

$status = 'OK';
if ($crit > 0) $status = 'CRITICAL';
elseif ($high > 0) $status = 'AT RISK';
elseif ($med > 0) $status = 'WARN';

/* Sort findings by severity then file */
usort($findings, static function(array $a, array $b) use ($severityRank): int {
  $ra = $severityRank[$a['severity']] ?? 0;
  $rb = $severityRank[$b['severity']] ?? 0;
  if ($ra !== $rb) return $rb <=> $ra;
  return strcmp((string)$a['file'], (string)$b['file']);
});

/* Top rules summary */
$rulesSummary = array_values($stats['findings_by_rule']);
usort($rulesSummary, static function(array $a, array $b) use ($severityRank): int {
  $ra = $severityRank[$a['severity']] ?? 0;
  $rb = $severityRank[$b['severity']] ?? 0;
  if ($ra !== $rb) return $rb <=> $ra;
  return (int)$b['count'] <=> (int)$a['count'];
});

/* Build JSON report */
$durationMs = (int)round((microtime(true) - $startedAt) * 1000);

$report = [
  'meta' => [
    'tool' => 'lint/quick_scan',
    'version' => '1.3',
    'started_at' => $startedIso,
    'duration_ms' => $durationMs,
  ],
  'health' => [
    'status' => $status,
    'score'  => $score,
    'severity_counts' => $stats['findings_by_severity'],
  ],
  'stats' => [
    'app_root' => $stats['app_root'],
    'roots' => $stats['roots'],
    'files_scanned' => $stats['files_scanned'],
    'bytes_read' => $stats['bytes_read'],
    'counts_by_ext' => $stats['counts_by_ext'],
    'zero_byte_files' => $stats['zero_byte_files'],
    'unreadable_files' => $stats['unreadable_files'],
    'excluded_files' => $stats['excluded_files'],
    'findings_total' => $stats['findings_total'],
  ],
  'top_rules' => array_slice($rulesSummary, 0, 15),
  'findings' => array_slice($findings, 0, 600),
];

/* Write JSON report file */
$reportPath = '';
$logsDir = $appRoot . '/logs/tools';
if (!is_dir($logsDir)) {
  @mkdir($logsDir, 0755, true);
}
if (is_dir($logsDir) && is_writable($logsDir)) {
  $fname = 'quick_scan_' . gmdate('Ymd_His') . '.json';
  $reportPath = $logsDir . '/' . $fname;
  @file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/* Render mode */
$wantHtml = false;
if (isset($_GET['render']) && is_string($_GET['render'])) {
  $r = strtolower(trim($_GET['render']));
  if ($r === 'html' || $r === 'auto') $wantHtml = true;
}
if (isset($_POST['render']) && is_string($_POST['render'])) {
  $r = strtolower(trim($_POST['render']));
  if ($r === 'html' || $r === 'auto') $wantHtml = true;
}

/* ---------------------------------------------------------
   HTML output
--------------------------------------------------------- */
if ($wantHtml) {
  $badge = ($status === 'OK') ? 'ok' : (($status === 'WARN') ? 'warn' : (($status === 'AT RISK') ? 'risk' : 'crit'));

  echo "<!doctype html><html><head><meta charset='utf-8'>";
  echo "<meta name='viewport' content='width=device-width,initial-scale=1'>";
  echo "<title>Lint Quick Scan</title>";
  echo "<style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;padding:16px;background:#f7f7fb;color:#111}
    .card{background:#fff;border:1px solid rgba(0,0,0,.10);border-radius:14px;padding:14px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
    .row{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between}
    .h{font-size:18px;font-weight:800;margin:0}
    .muted{color:#555}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:800;border:1px solid rgba(0,0,0,.12)}
    .pill.ok{background:#eefbf1}
    .pill.warn{background:#fff7e8}
    .pill.risk{background:#ffecec}
    .pill.crit{background:#ffdede}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{border-top:1px solid rgba(0,0,0,.08);padding:8px 6px;text-align:left;vertical-align:top;font-size:13px}
    th{color:#444;font-weight:800}
    code{background:#f2f2f7;padding:2px 6px;border-radius:8px}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:10px}
    .kpi{background:#fff;border:1px solid rgba(0,0,0,.10);border-radius:14px;padding:12px}
    .kpi .n{font-size:22px;font-weight:900}
    .kpi .l{color:#555;font-size:12px}
    .small{font-size:12px}
    .list{margin:8px 0 0 0;padding-left:18px}
  </style>";
  echo "</head><body>";

  echo "<div class='card'>";
  echo "<div class='row'>";
  echo "<div>";
  echo "<h1 class='h'>Lint Quick Scan</h1>";
  echo "<div class='muted small'>Started: " . htmlspecialchars($startedIso, ENT_QUOTES, 'UTF-8') . " • Duration: " . (int)$durationMs . " ms</div>";
  echo "</div>";
  echo "<div class='pill {$badge}'>Status: " . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . " • Score: " . (int)$score . "/100</div>";
  echo "</div>";

  echo "<div class='grid'>";
  echo "<div class='kpi'><div class='n'>" . (int)$stats['files_scanned'] . "</div><div class='l'>Files scanned</div></div>";
  echo "<div class='kpi'><div class='n'>" . (int)$stats['excluded_files'] . "</div><div class='l'>Excluded</div></div>";
  echo "<div class='kpi'><div class='n'>" . (int)$stats['findings_total'] . "</div><div class='l'>Findings</div></div>";
  echo "<div class='kpi'><div class='n'>" . (int)$crit . "</div><div class='l'>Critical</div></div>";
  echo "<div class='kpi'><div class='n'>" . (int)$high . "</div><div class='l'>High</div></div>";
  echo "<div class='kpi'><div class='n'>" . (int)$med . "</div><div class='l'>Medium</div></div>";
  echo "<div class='kpi'><div class='n'>" . (int)$low . "</div><div class='l'>Low</div></div>";
  echo "</div>";

  echo "<div style='margin-top:10px' class='small muted'>";
  echo "<div><strong>Roots:</strong> ";
  $pairs = [];
  foreach ($roots as $name => $dir) { $pairs[] = htmlspecialchars($name . '=' . $dir, ENT_QUOTES, 'UTF-8'); }
  echo implode(" • ", $pairs);
  echo "</div>";
  if ($reportPath !== '') {
    echo "<div><strong>JSON saved:</strong> <code>" . htmlspecialchars($reportPath, ENT_QUOTES, 'UTF-8') . "</code></div>";
  } else {
    echo "<div><strong>JSON saved:</strong> <em>not written</em> (logs dir missing or not writable)</div>";
  }
  echo "</div>";

  echo "<h2 class='h' style='margin-top:14px;font-size:16px'>Top Issues</h2>";
  if (!$rulesSummary) {
    echo "<div class='muted'>No issues detected.</div>";
  } else {
    echo "<table><thead><tr><th>Severity</th><th>Issue</th><th>Count</th><th>Examples</th></tr></thead><tbody>";
    foreach (array_slice($rulesSummary, 0, 12) as $rrow) {
      $sev = htmlspecialchars((string)$rrow['severity'], ENT_QUOTES, 'UTF-8');
      $lab = htmlspecialchars((string)$rrow['label'], ENT_QUOTES, 'UTF-8');
      $cnt = (int)$rrow['count'];

      $examples = [];
      $ruleId = '';
      foreach ($stats['findings_by_rule'] as $rid => $meta) {
        if (($meta['label'] ?? '') === ($rrow['label'] ?? '')) { $ruleId = (string)$rid; break; }
      }
      if ($ruleId !== '') {
        $n = 0;
        foreach ($findings as $f) {
          if (($f['rule_id'] ?? '') !== $ruleId) continue;
          $examples[] = htmlspecialchars((string)$f['file'], ENT_QUOTES, 'UTF-8');
          if (++$n >= $maxListInHtml) break;
        }
      }

      $pillClass = ($sev === 'critical') ? 'crit' : (($sev === 'high') ? 'risk' : (($sev === 'medium') ? 'warn' : 'ok'));

      echo "<tr>";
      echo "<td><span class='pill {$pillClass}'>{$sev}</span></td>";
      echo "<td>{$lab}</td>";
      echo "<td>{$cnt}</td>";
      echo "<td>";
      if ($examples) {
        echo "<ul class='list'>";
        foreach (array_slice($examples, 0, 8) as $ex) {
          echo "<li><code>{$ex}</code></li>";
        }
        echo "</ul>";
      } else {
        echo "<span class='muted'>—</span>";
      }
      echo "</td>";
      echo "</tr>";
    }
    echo "</tbody></table>";
  }

  echo "<h2 class='h' style='margin-top:14px;font-size:16px'>Embedded JSON Report</h2>";
  echo "<pre style='white-space:pre-wrap;word-break:break-word;border:1px solid rgba(0,0,0,.10);border-radius:14px;padding:10px;background:#fff'>";
  echo htmlspecialchars(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
  echo "</pre>";

  echo "</body></html>";
  return;
}

/* ---------------------------------------------------------
   Plain-text output (PRE-friendly)
--------------------------------------------------------- */
echo "Lint Quick Scan\n";
echo "Started: {$startedIso}\n";
echo "Duration: {$durationMs} ms\n";
echo "Status: {$status}\n";
echo "Score: {$score}/100\n";
echo "Roots:\n";
foreach ($roots as $name => $dir) {
  echo "  - {$name}: {$dir}\n";
}
echo "Files scanned: " . (int)$stats['files_scanned'] . "\n";
echo "Excluded: " . (int)$stats['excluded_files'] . "\n";
echo "Findings: " . (int)$stats['findings_total'] . "\n";
echo "Severity: critical={$crit} high={$high} medium={$med} low={$low}\n";
if ($reportPath !== '') {
  echo "JSON saved: {$reportPath}\n";
} else {
  echo "JSON saved: not written (logs/tools missing or not writable)\n";
}

echo "\nTop Issues:\n";
foreach (array_slice($rulesSummary, 0, 12) as $rrow) {
  $sev = (string)$rrow['severity'];
  $lab = (string)$rrow['label'];
  $cnt = (int)$rrow['count'];
  echo "- {$sev} • {$cnt} • {$lab}\n";
}

echo "\nEmbedded JSON Report:\n";
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
