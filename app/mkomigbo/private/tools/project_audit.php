<?php
declare(strict_types=1);

/**
 * /private/tools/project_audit.php
 * Mkomigbo Project Audit (CLI-first)
 *
 * Scans:
 * - APP_ROOT   (app code):   .../public_html/app/mkomigbo
 * - PUBLIC_ROOT(web root):   .../public_html/public
 *
 * Checks:
 * - public tools/scripts in web root
 * - wrong initialize.php path offenders (real offenders, not docblocks)
 * - public endpoints touching dangerous private tooling
 * - optional PHP lint (proc_open permitting)
 * - DB schema health checks (if db() exists)
 * - writes JSON report to APP_ROOT/logs/
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

/* ---------------------------------------------------------
   Small, safe helpers
--------------------------------------------------------- */
function mk_now_utc(): string { return gmdate('Y-m-d\TH:i:s\Z'); }
function mk_out(string $s = ''): void { echo $s . PHP_EOL; }
function mk_hr(): void { mk_out(str_repeat('-', 60)); }
function mk_is_cli(): bool { return (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg'); }

function mk_norm_path(string $p): string {
  $p = str_replace('\\', '/', $p);
  $p = preg_replace('~/+~', '/', $p) ?? $p;
  return rtrim($p, '/');
}

function mk_relpath(string $base, string $path): string {
  $base = mk_norm_path($base);
  $path = mk_norm_path($path);
  if (strpos($path, $base) === 0) {
    $r = substr($path, strlen($base));
    if ($r === false) return $path;
    $r = ltrim($r, '/');
    return $r !== '' ? $r : '.';
  }
  return $path;
}

function mk_safe_json_write(string $file, string $json): bool {
  $dir = dirname($file);
  if (!is_dir($dir)) { @mkdir($dir, 0750, true); }
  if (!is_dir($dir) || !is_writable($dir)) return false;

  $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
  $ok = @file_put_contents($tmp, $json, LOCK_EX);
  if ($ok === false) return false;
  @chmod($tmp, 0640);
  return @rename($tmp, $file);
}

function mk_has_proc_open(): bool {
  if (!function_exists('proc_open')) return false;
  $disabled = (string)ini_get('disable_functions');
  if ($disabled === '') return true;
  $disabled = array_map('trim', explode(',', $disabled));
  return !in_array('proc_open', $disabled, true);
}

function mk_php_lint(string $file): array {
  if (!mk_has_proc_open()) return ['ok' => true, 'output' => '(lint skipped: proc_open disabled)'];

  $cmd = ['php', '-l', $file];
  $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
  $proc = @proc_open($cmd, $descriptors, $pipes);
  if (!is_resource($proc)) return ['ok' => true, 'output' => '(lint skipped: proc_open failed)'];

  @fclose($pipes[0]);
  $stdout = stream_get_contents($pipes[1]);
  $stderr = stream_get_contents($pipes[2]);
  @fclose($pipes[1]); @fclose($pipes[2]);
  $code = @proc_close($proc);

  $out = trim((string)$stdout . "\n" . (string)$stderr);
  $ok  = ($code === 0);
  return ['ok' => $ok, 'output' => $out !== '' ? $out : ($ok ? 'No syntax errors detected' : 'Syntax error')];
}

function mk_read_small(string $file, int $maxBytes = 262144): string {
  if (!is_file($file) || !is_readable($file)) return '';
  $fh = @fopen($file, 'rb');
  if (!$fh) return '';
  $data = @fread($fh, $maxBytes);
  @fclose($fh);
  return is_string($data) ? $data : '';
}

/**
 * Remove comments/strings to reduce false positives when scanning.
   * - Strips //... and block-comments (slash-star ... star-slash)
 * - Replaces quoted strings with "" (keeps structure)
 */
function mk_strip_noise(string $s): string {
  // Remove /* ... */ blocks
  $s = preg_replace('~/\*.*?\*/~s', '', $s) ?? $s;
  // Remove //... lines
  $s = preg_replace('~//.*$~m', '', $s) ?? $s;
  // Replace single and double-quoted strings
  $s = preg_replace('~("([^"\\\\]|\\\\.)*")~s', '""', $s) ?? $s;
  $s = preg_replace("~('([^'\\\\]|\\\\.)*')~s", "''", $s) ?? $s;
  return $s;
}

/* ---------------------------------------------------------
   Permissions (real checks)
--------------------------------------------------------- */
function mk_perm_bits(string $file): int {
  $p = @fileperms($file);
  return ($p === false) ? 0 : ($p & 0777);
}
function mk_perm_octal(string $file): string {
  $bits = mk_perm_bits($file);
  return $bits ? sprintf('%04o', $bits) : '';
}
function mk_is_world_writable(string $file): bool {
  $bits = mk_perm_bits($file);
  return (bool)($bits & 0002);
}
function mk_is_group_writable(string $file): bool {
  $bits = mk_perm_bits($file);
  return (bool)($bits & 0020);
}

/* ---------------------------------------------------------
   Resolve APP_ROOT + init (bounded scan)
--------------------------------------------------------- */
function mk_find_app_root(string $startDir, int $maxDepth = 10): ?string {
  $dir = mk_norm_path($startDir);
  for ($i = 0; $i <= $maxDepth; $i++) {
    $cand = $dir . '/private/assets/' . 'initialize.php';
    if (is_file($cand)) return $dir;

    $parent = dirname($dir);
    if ($parent === $dir || $parent === '' || $parent === '.') break;
    $dir = mk_norm_path($parent);
  }
  return null;
}

/**
 * Robust PUBLIC_ROOT resolver for your layout:
 * APP_ROOT: .../public_html/app/mkomigbo
 * PUBLIC_ROOT: .../public_html/public
 */
function mk_public_root_from_app_root(string $appRoot): string {
  $appRoot = mk_norm_path($appRoot);

  // Go up: mkomigbo -> app -> public_html
  $publicHtml = dirname(dirname($appRoot));
  $publicHtml = mk_norm_path($publicHtml);

  $cand = mk_norm_path($publicHtml . '/public');
  if (is_dir($cand)) return $cand;

  // last resort: common absolute for this server
  $cand2 = '/home/mkomigbo/public_html/public';
  if (is_dir($cand2)) return mk_norm_path($cand2);

  // fallback: return computed, even if missing (caller will warn)
  return $cand;
}

/* ---------------------------------------------------------
   Report structure
--------------------------------------------------------- */
$report = [
  'meta' => [
    'tool' => 'project_audit',
    'version' => '1.1.0',
    'timestamp_utc' => mk_now_utc(),
    'php_sapi' => PHP_SAPI,
    'php_version' => PHP_VERSION,
  ],
  'paths' => [
    'app_root' => null,
    'private_path' => null,
    'public_root' => null,
    'init' => null,
  ],
  'checks' => [],
  'findings' => [
    'fail' => [],
    'warn' => [],
    'info' => [],
  ],
  'scan' => [
    'app' => ['php_files' => 0, 'scanned' => 0, 'linted' => 0],
    'public' => ['php_files' => 0, 'scanned' => 0, 'linted' => 0],
    'excluded_dirs' => [],
  ],
  'db' => [
    'ok' => false,
    'database' => null,
    'tables' => [],
    'missing_tables' => [],
    'missing_columns' => [],
  ],
  'output' => [
    'json_report_path' => null,
  ],
];

function mk_add_finding(array &$report, string $level, string $code, string $message, string $path = '', array $extra = []): void {
  $row = array_merge(['code' => $code, 'message' => $message, 'path' => $path], $extra);
  $report['findings'][$level][] = $row;
}

/* ---------------------------------------------------------
   Start
--------------------------------------------------------- */
mk_out('Mkomigbo Project Audit');
mk_hr();

$script_dir = mk_norm_path(__DIR__);                 // .../private/tools
$app_root_guess = mk_find_app_root(dirname($script_dir, 2), 14); // start near .../private

if (!$app_root_guess) {
  $app_root_guess = mk_find_app_root($script_dir, 18);
}

if (!$app_root_guess) {
  $report['checks'][] = ['name' => 'Locate APP_ROOT', 'ok' => false, 'details' => 'Unable to locate private/assets/initialize.php by upward scan.'];
  mk_out('[FAIL] Could not locate APP_ROOT (private/assets/initialize.php not found).');
  exit(1);
}

$app_root = mk_norm_path($app_root_guess);
$init = $app_root . '/private/assets/' . 'initialize.php';

$report['paths']['app_root'] = $app_root;
$report['paths']['init'] = $init;

mk_out('APP_ROOT: ' . $app_root);
mk_out('INIT:     ' . $init);

if (!is_file($init)) {
  $report['checks'][] = ['name' => 'initialize.php exists', 'ok' => false, 'details' => $init];
  mk_out('');
  mk_out('[FAIL] initialize.php missing — ' . $init);
  exit(1);
}

$report['checks'][] = ['name' => 'initialize.php exists', 'ok' => true, 'details' => $init];
mk_out('');
mk_out('[ OK ] initialize.php found — ' . $init);

/* Load init */
try {
  require_once $init;
  $report['checks'][] = ['name' => 'initialize.php loaded', 'ok' => true, 'details' => 'Loaded successfully'];
  mk_out('[ OK ] initialize.php loaded');
} catch (Throwable $e) {
  $report['checks'][] = ['name' => 'initialize.php loaded', 'ok' => false, 'details' => $e->getMessage()];
  mk_out('[FAIL] initialize.php failed to load');
  exit(1);
}

/* Constants */
if (defined('APP_ROOT')) {
  $report['checks'][] = ['name' => 'APP_ROOT constant', 'ok' => true, 'details' => (string)APP_ROOT];
  mk_out('[ OK ] APP_ROOT constant — ' . (string)APP_ROOT);
} else {
  $report['checks'][] = ['name' => 'APP_ROOT constant', 'ok' => false, 'details' => 'APP_ROOT not defined by init'];
  mk_add_finding($report, 'warn', 'APP_ROOT_UNDEFINED', 'APP_ROOT not defined by initialize.php; audit will use discovered APP_ROOT.', $app_root);
  mk_out('[WARN] APP_ROOT constant not defined (using discovered APP_ROOT).');
}

if (defined('PRIVATE_PATH')) {
  $report['paths']['private_path'] = (string)PRIVATE_PATH;
  $report['checks'][] = ['name' => 'PRIVATE_PATH constant', 'ok' => true, 'details' => (string)PRIVATE_PATH];
  mk_out('[ OK ] PRIVATE_PATH constant — ' . (string)PRIVATE_PATH);
} else {
  $report['checks'][] = ['name' => 'PRIVATE_PATH constant', 'ok' => false, 'details' => 'PRIVATE_PATH not defined by init'];
  mk_add_finding($report, 'warn', 'PRIVATE_PATH_UNDEFINED', 'PRIVATE_PATH not defined by initialize.php.', $app_root . '/private');
  mk_out('[WARN] PRIVATE_PATH constant not defined.');
}

/* Public root (robust) */
$public_root = mk_public_root_from_app_root($app_root);
$report['paths']['public_root'] = $public_root;

if (is_dir($public_root)) {
  $report['checks'][] = ['name' => 'PUBLIC_ROOT exists', 'ok' => true, 'details' => $public_root];
  mk_out('[ OK ] PUBLIC_ROOT — ' . $public_root);
} else {
  $report['checks'][] = ['name' => 'PUBLIC_ROOT exists', 'ok' => false, 'details' => $public_root];
  mk_add_finding($report, 'warn', 'PUBLIC_ROOT_MISSING', 'Public root directory not found; public scan skipped.', $public_root);
  mk_out('[WARN] PUBLIC_ROOT missing — ' . $public_root . ' (public scan skipped)');
}

/* DB check */
$pdo = null;
try { if (function_exists('db')) { $pdo = db(); } } catch (Throwable $e) { $pdo = null; }

if ($pdo instanceof PDO) {
  $report['checks'][] = ['name' => 'db() returns PDO', 'ok' => true, 'details' => 'PDO connected'];
  mk_out('[ OK ] db() returns PDO');

  try {
    $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $report['db']['ok'] = true;
    $report['db']['database'] = $dbName;
    $report['checks'][] = ['name' => 'DATABASE()', 'ok' => true, 'details' => $dbName];
    mk_out('[ OK ] DATABASE() — ' . $dbName);
  } catch (Throwable $e) {
    mk_add_finding($report, 'warn', 'DB_NAME_QUERY_FAIL', 'Could not query DATABASE().', 'db', ['details' => $e->getMessage()]);
    mk_out('[WARN] Could not query DATABASE()');
  }
} else {
  $report['checks'][] = ['name' => 'db() returns PDO', 'ok' => false, 'details' => 'db() not available or not a PDO'];
  mk_add_finding($report, 'warn', 'DB_UNAVAILABLE', 'db() not available; DB checks skipped.', 'initialize.php/db()');
  mk_out('[WARN] db() not available (DB checks skipped)');
}

mk_hr();

/* ---------------------------------------------------------
   Scan configuration
--------------------------------------------------------- */
$exclude_dirs = [
  '/vendor',
  '/logs',
  '/private/cache',
  '/cache',
  '/tmp',
  '/.git',
  '/node_modules',
  '/public/assets',
  '/public/uploads',
];
$report['scan']['excluded_dirs'] = $exclude_dirs;

$danger_public_name_patterns = [
  'project_audit.php',
  'audit.php',
  'diag.php',
  'diagnostics.php',
  '_diag.php',
  'phpinfo.php',
];

/**
 * OFFENDERS:
 * Only the *real* wrong roots that previously broke your deployment.
 * (We deliberately avoid the generic substring "/private/assets/initialize.php"
 * because it appears legitimately inside APP_ROOT paths and docblocks.)
 */
$offender_strings = [
  '/home/mkomigbo/public_html/private/assets/' . 'initialize.php',
  '/public_html/private/assets/' . 'initialize.php',
  '/app/private/assets/' . 'initialize.php',
];

/* ---------------------------------------------------------
   Directory scanner
--------------------------------------------------------- */
function mk_should_exclude(string $base, string $path, array $exclude_dirs): bool {
  $base = mk_norm_path($base);
  $path = mk_norm_path($path);
  $rel = '/' . ltrim(mk_relpath($base, $path), '/');

  foreach ($exclude_dirs as $ex) {
    $ex = mk_norm_path($ex);
    if ($ex === '') continue;
    if (strpos($rel, $ex) === 0) return true;
  }
  return false;
}

function mk_list_php_files(string $root, array $exclude_dirs): array {
  $files = [];
  if (!is_dir($root)) return $files;

  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
  );

  foreach ($it as $fi) {
    /** @var SplFileInfo $fi */
    $path = (string)$fi->getPathname();

    if ($fi->isDir()) {
      if (mk_should_exclude($root, $path, $exclude_dirs)) {
        $it->next();
      }
      continue;
    }

    if (!$fi->isFile()) continue;
    $lower = strtolower($path);
    if (substr($lower, -4) !== '.php') continue;
    if (mk_should_exclude($root, $path, $exclude_dirs)) continue;

    $files[] = mk_norm_path($path);
  }

  sort($files);
  return $files;
}

/* ---------------------------------------------------------
   Scan: APP code
--------------------------------------------------------- */
mk_out('Scan: APP');
mk_hr();

$app_files = mk_list_php_files($app_root, $exclude_dirs);
$report['scan']['app']['php_files'] = count($app_files);
mk_out('PHP files: ' . count($app_files));

foreach ($app_files as $file) {
  $report['scan']['app']['scanned']++;

  $content = mk_read_small($file);
  if ($content === '') continue;

  // Scan with noise stripped to reduce false positives
  $scan = mk_strip_noise($content);

  foreach ($offender_strings as $needle) {
    if ($needle !== '' && strpos($scan, $needle) !== false) {
      mk_add_finding($report, 'warn', 'INIT_PATH_OFFENDER', 'File references a risky/incorrect initialize path: ' . $needle, $file);
      break;
    }
  }

  // Optional lint
  $lint = mk_php_lint($file);
  if ($lint['output'] !== '(lint skipped: proc_open disabled)' && $lint['output'] !== '(lint skipped: proc_open failed)') {
    $report['scan']['app']['linted']++;
    if (!$lint['ok']) {
      mk_add_finding($report, 'fail', 'PHP_SYNTAX_ERROR', 'PHP syntax error detected.', $file, ['details' => $lint['output']]);
    }
  }
}

mk_out('[ OK ] APP scan complete');
mk_hr();

/* ---------------------------------------------------------
   Scan: PUBLIC (web root)
--------------------------------------------------------- */
mk_out('Scan: PUBLIC');
mk_hr();

$public_files = [];
if (is_dir($public_root)) {
  $public_files = mk_list_php_files($public_root, $exclude_dirs);
}
$report['scan']['public']['php_files'] = count($public_files);
mk_out('PHP files: ' . count($public_files));

foreach ($public_files as $file) {
  $report['scan']['public']['scanned']++;

  $rel = mk_relpath($public_root, $file);
  $relLower = strtolower('/' . ltrim($rel, '/'));

  $content = mk_read_small($file);
  if ($content === '') continue;

  $scan = mk_strip_noise($content);

  // 1) Hard flag: sensitive tool scripts in public web root
  $lowerName = strtolower(basename($file));
  foreach ($danger_public_name_patterns as $badName) {
    if ($lowerName === strtolower($badName)) {
      mk_add_finding($report, 'fail', 'UNSAFE_PUBLIC_TOOL', 'Sensitive tool script is under public web root. Move to /private/tools and expose via staff-only runner endpoint.', $file, ['rel' => $rel]);
      break;
    }
  }

  // 2) Staff tools directory under public (warn)
  if (strpos($relLower, '/staff/tools') === 0) {
    mk_add_finding($report, 'warn', 'PUBLIC_STAFF_TOOLS', 'Staff tools directory exists under public. Prefer: /private/tools + staff-only runner.', $file, ['rel' => $rel]);
  }

  // 3) Wrong init path offenders in public endpoints (real offenders only)
  foreach ($offender_strings as $needle) {
    if ($needle !== '' && strpos($scan, $needle) !== false) {
      mk_add_finding($report, 'fail', 'PUBLIC_INIT_PATH_OFFENDER', 'Public endpoint references incorrect initialize path: ' . $needle, $file, ['rel' => $rel]);
      break;
    }
  }

  // 4) Heuristic: public endpoints may include private/shared + private/functions + private/assets.
  //    Warn ONLY if they appear to include dangerous private areas (tools/ops/staff_tools),
  //    or if they reference PRIVATE_PATH in a way that suggests privileged tooling.
  if (strpos($scan, '/private/') !== false || strpos($scan, 'PRIVATE_PATH') !== false) {

    $public_safe_private_paths = [
      '/private/shared/',
      '/private/functions/',
      '/private/assets/',
    ];

    $danger_private_paths = [
      '/private/tools/',
      '/private/tools/ops/',
      '/private/tools/staff_tools/',
    ];

    $touchesDanger = false;
    foreach ($danger_private_paths as $dp) {
      if (strpos($scan, $dp) !== false) { $touchesDanger = true; break; }
    }

    if (!$touchesDanger) {
      $onlySafe = false;
      foreach ($public_safe_private_paths as $sp) {
        if (strpos($scan, $sp) !== false) { $onlySafe = true; break; }
      }

      if (!$onlySafe && strpos($relLower, '/staff/') === false) {
        mk_add_finding(
          $report,
          'warn',
          'PUBLIC_PRIVATE_INCLUDE_NO_GUARD',
          'Public endpoint references private paths outside the known-safe allowlist. Review.',
          $file,
          ['rel' => $rel]
        );
      }
    } else {
      $hasGuard = (strpos($scan, 'require_staff_login') !== false)
               || (strpos($scan, 'require_staff') !== false)
               || (strpos($scan, 'mk_require_staff_login') !== false)
               || (strpos($scan, 'require_staff_role') !== false)
               || (strpos($scan, 'require_staff_admin') !== false);

      if (!$hasGuard && strpos($relLower, '/staff/') === false) {
        mk_add_finding(
          $report,
          'warn',
          'PUBLIC_PRIVATE_INCLUDE_NO_GUARD',
          'Public endpoint appears to touch sensitive private tooling without an obvious staff/auth guard. Review.',
          $file,
          ['rel' => $rel]
        );
      }
    }
  }

  // Optional lint
  $lint = mk_php_lint($file);
  if ($lint['output'] !== '(lint skipped: proc_open disabled)' && $lint['output'] !== '(lint skipped: proc_open failed)') {
    $report['scan']['public']['linted']++;
    if (!$lint['ok']) {
      mk_add_finding($report, 'fail', 'PHP_SYNTAX_ERROR', 'PHP syntax error detected.', $file, ['rel' => $rel, 'details' => $lint['output']]);
    }
  }
}

mk_out('[ OK ] PUBLIC scan complete');
mk_hr();

/* ---------------------------------------------------------
   DB schema checks (best effort)
--------------------------------------------------------- */
if ($pdo instanceof PDO) {
  mk_out('DB schema checks');
  mk_hr();

  $must_tables = ['subjects', 'pages', 'contributors', 'page_files'];

  foreach ($must_tables as $t) {
    try {
      $st = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1");
      $st->execute([$t]);
      $ok = (bool)$st->fetchColumn();

      if (!$ok) {
        $report['db']['missing_tables'][] = $t;
        mk_add_finding($report, 'warn', 'MISSING_TABLE', 'Expected table missing: ' . $t, 'db:' . $t);
        mk_out('[WARN] Missing table: ' . $t);
      } else {
        $report['db']['tables'][] = $t;
        mk_out('[ OK ] Table: ' . $t);
      }
    } catch (Throwable $e) {
      mk_add_finding($report, 'warn', 'DB_SCHEMA_QUERY_FAIL', 'Schema query failed for table: ' . $t, 'db:' . $t, ['details' => $e->getMessage()]);
      mk_out('[WARN] Could not verify table: ' . $t);
    }
  }

  $must_cols = [
    'pages' => ['id', 'slug'],
    'contributors' => ['id', 'status'],
    'page_files' => ['id', 'page_id', 'stored_path'],
  ];

  foreach ($must_cols as $table => $cols) {
    foreach ($cols as $col) {
      try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
        $st->execute([$table, $col]);
        $ok = ((int)$st->fetchColumn() > 0);

        if (!$ok) {
          $report['db']['missing_columns'][] = ['table' => $table, 'column' => $col];
          mk_add_finding($report, 'warn', 'MISSING_COLUMN', 'Expected column missing: ' . $table . '.' . $col, 'db:' . $table . '.' . $col);
          mk_out('[WARN] Missing column: ' . $table . '.' . $col);
        } else {
          mk_out('[ OK ] Column: ' . $table . '.' . $col);
        }
      } catch (Throwable $e) {
        mk_add_finding($report, 'warn', 'DB_SCHEMA_QUERY_FAIL', 'Schema query failed for column: ' . $table . '.' . $col, 'db:' . $table . '.' . $col, ['details' => $e->getMessage()]);
        mk_out('[WARN] Could not verify column: ' . $table . '.' . $col);
      }
    }
  }

  mk_hr();
}

/* ---------------------------------------------------------
   Permission sanity checks (accurate)
--------------------------------------------------------- */
mk_out('Permissions (info)');
mk_hr();

$perm_init = mk_perm_octal($init);
if ($perm_init !== '') {
  mk_out('initialize.php perms: ' . $perm_init);

  // Only warn on group/world writable, not on 0644/0640.
  if (mk_is_world_writable($init) || mk_is_group_writable($init)) {
    mk_add_finding($report, 'warn', 'PERMS_TOO_OPEN', 'initialize.php is writable by group/world. Prefer 0644 or 0640.', $init, ['perms' => $perm_init]);
  }
}

$tool_file = __FILE__;
$perm_tool = mk_perm_octal($tool_file);
if ($perm_tool !== '') {
  mk_out('project_audit.php perms: ' . $perm_tool);

  if (mk_is_world_writable($tool_file) || mk_is_group_writable($tool_file)) {
    mk_add_finding($report, 'warn', 'PERMS_TOO_OPEN', 'project_audit.php is writable by group/world. Prefer 0640.', $tool_file, ['perms' => $perm_tool]);
  }
}

mk_hr();

/* ---------------------------------------------------------
   Final summary + JSON report write
--------------------------------------------------------- */
$failCount = count($report['findings']['fail']);
$warnCount = count($report['findings']['warn']);
$infoCount = count($report['findings']['info']);

mk_out('Summary');
mk_hr();
mk_out('FAIL: ' . $failCount);
mk_out('WARN: ' . $warnCount);
mk_out('INFO: ' . $infoCount);

$exitCode = ($failCount > 0) ? 2 : 0;

$logs_dir = $app_root . '/logs';
$ts = gmdate('Ymd_His');
$report_path = $logs_dir . '/project_audit_' . $ts . '.json';
$report['output']['json_report_path'] = $report_path;

$json = json_encode($report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if (!is_string($json)) $json = '{"error":"json_encode failed"}';

$wrote = mk_safe_json_write($report_path, $json);
mk_out('');
if ($wrote) {
  mk_out('[ OK ] JSON report written: ' . $report_path);
} else {
  mk_out('[WARN] Could not write JSON report to: ' . $report_path);
  mk_add_finding($report, 'warn', 'REPORT_WRITE_FAIL', 'Could not write JSON report. Check logs dir permissions.', $report_path);
}

mk_out('');
mk_out('[ OK ] Audit completed');

exit($exitCode);
