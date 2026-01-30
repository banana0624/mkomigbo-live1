<?php
declare(strict_types=1);

/**
 * /public/igbo-calendar/download.php
 * Download an export snapshot of the Igbo Calendar.
 *
 * Route:
 *   /igbo-calendar/download/  -> (via .htaccess) download.php
 *
 * Query:
 *   ?year=2026
 *   ?format=json|csv   (default json)
 *
 * Export strategy (auto-detect):
 * 1) If an app-level export function exists, use it.
 * 2) Else if a calendar engine exists, compute a year snapshot (best-effort).
 * 3) Else export a minimal diagnostic snapshot (still a valid download).
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

/* ---------------------------------------------------------
   Never allow stray output before headers
--------------------------------------------------------- */
if (function_exists('ob_get_level')) {
  while (ob_get_level() > 0) { @ob_end_clean(); }
}

/* ---------------------------------------------------------
   Locate initialize.php (bounded upward scan)
--------------------------------------------------------- */
$init = null;
$dir = __DIR__;
for ($i = 0; $i <= 12; $i++) {
  $candidates = [
    $dir . '/../../app/mkomigbo/private/assets/initialize.php', // /public/igbo-calendar/
    $dir . '/../app/mkomigbo/private/assets/initialize.php',
    $dir . '/app/mkomigbo/private/assets/initialize.php',
    $dir . '/private/assets/initialize.php',
  ];
  foreach ($candidates as $try) {
    if (is_file($try)) { $init = $try; break 2; }
  }
  $dir = dirname($dir);
}
if ($init && is_file($init)) {
  require_once $init;
}

/* ---------------------------------------------------------
   Inputs
--------------------------------------------------------- */
$year = (int)($_GET['year'] ?? (int)gmdate('Y'));
if ($year < 1900 || $year > 2200) { $year = (int)gmdate('Y'); }

$format = strtolower((string)($_GET['format'] ?? 'json'));
if (!in_array($format, ['json', 'csv'], true)) { $format = 'json'; }

/* ---------------------------------------------------------
   Export helpers
--------------------------------------------------------- */
function mk_csv_escape(string $v): string {
  $needs = (strpos($v, ',') !== false) || (strpos($v, '"') !== false) || (strpos($v, "\n") !== false) || (strpos($v, "\r") !== false);
  if ($needs) {
    $v = str_replace('"', '""', $v);
    return '"' . $v . '"';
  }
  return $v;
}

function mk_array_to_csv(array $rows): string {
  // expects array of associative arrays with same keys
  if (!$rows) return "";
  $keys = array_keys($rows[0]);
  $out = [];
  $out[] = implode(',', array_map('mk_csv_escape', $keys));
  foreach ($rows as $r) {
    $line = [];
    foreach ($keys as $k) {
      $val = $r[$k] ?? '';
      if (is_bool($val)) $val = $val ? '1' : '0';
      if (is_null($val)) $val = '';
      if (is_array($val) || is_object($val)) $val = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      $line[] = mk_csv_escape((string)$val);
    }
    $out[] = implode(',', $line);
  }
  return implode("\n", $out) . "\n";
}

/* ---------------------------------------------------------
   Build payload (best-effort, no guessing required)
--------------------------------------------------------- */
$appMeta = [
  'exported_at' => gmdate('c'),
  'app' => 'Igbo Calendar',
  'scope' => '/igbo-calendar/',
  'start_url' => '/igbo-calendar/?pwa=1',
  'year' => $year,
  'format' => $format,
];

$engineUsed = 'none';
$data = null;

/**
 * 1) Preferred: explicit export function you may add in your codebase.
 * If you create it later, this download endpoint automatically upgrades.
 *
 * Suggested signatures:
 *   mk_igbo_calendar_export_payload(int $year): array
 *   igbo_calendar_export_payload(int $year): array
 */
if (function_exists('mk_igbo_calendar_export_payload')) {
  try {
    $data = mk_igbo_calendar_export_payload($year);
    $engineUsed = 'mk_igbo_calendar_export_payload';
  } catch (Throwable $e) {
    $data = null;
  }
} elseif (function_exists('igbo_calendar_export_payload')) {
  try {
    $data = igbo_calendar_export_payload($year);
    $engineUsed = 'igbo_calendar_export_payload';
  } catch (Throwable $e) {
    $data = null;
  }
}

/**
 * 2) Next: try common engine class names (non-fatal if absent).
 * We do NOT assume your internal API—only call if it exists.
 */
if ($data === null) {
  $candidates = [
    'IgboCalendarYear',
    'MkIgboCalendarYear',
    'Mkomigbo\\IgboCalendarYear',
  ];

  foreach ($candidates as $class) {
    if (class_exists($class)) {
      try {
        $obj = new $class($year);

        // Try common methods
        if (method_exists($obj, 'toArray')) {
          $data = $obj->toArray();
          $engineUsed = $class . '::toArray';
          break;
        }
        if (method_exists($obj, 'export')) {
          $data = $obj->export();
          $engineUsed = $class . '::export';
          break;
        }
        if (method_exists($obj, 'getYear')) {
          $data = $obj->getYear();
          $engineUsed = $class . '::getYear';
          break;
        }

        // As a last resort, export public properties
        $data = get_object_vars($obj);
        $engineUsed = $class . '::get_object_vars';
        break;

      } catch (Throwable $e) {
        $data = null;
      }
    }
  }
}

/**
 * 3) Fallback: minimal snapshot (still useful for debugging and users)
 */
if ($data === null) {
  $engineUsed = 'fallback';
  $data = [
    'note' => 'Calendar export engine not detected. This is a minimal snapshot.',
    'request' => [
      'uri' => $_SERVER['REQUEST_URI'] ?? null,
      'host' => $_SERVER['HTTP_HOST'] ?? null,
      'https' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ],
  ];
}

$payload = [
  'meta' => $appMeta + [
    'engine' => $engineUsed,
    'php' => PHP_VERSION,
  ],
  'data' => $data,
];

/* ---------------------------------------------------------
   Render output
--------------------------------------------------------- */
$basename = 'igbo-calendar-export-' . $year . '-' . gmdate('Ymd-His');

if ($format === 'csv') {
  // CSV requires tabular rows; if engine gave complex data, we wrap a simple row
  $rows = [];

  if (is_array($data) && isset($data[0]) && is_array($data[0])) {
    // looks like list-of-rows already
    $rows = $data;
  } else {
    $rows = [[
      'year' => $year,
      'engine' => $engineUsed,
      'exported_at' => $appMeta['exported_at'],
      'data_json' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]];
  }

  $csv = mk_array_to_csv($rows);
  if ($csv === '') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Export failed.";
    exit;
  }

  $filename = $basename . '.csv';

  header('Content-Type: text/csv; charset=utf-8');
  header('X-Content-Type-Options: nosniff');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
  header('Content-Length: ' . (string)strlen($csv));

  echo $csv;
  exit;
}

// JSON
$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if ($json === false) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Export failed.";
  exit;
}

$filename = $basename . '.json';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: ' . (string)strlen($json));

echo $json;
exit;
