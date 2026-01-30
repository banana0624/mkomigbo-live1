<?php
declare(strict_types=1);

/**
 * /home/mkomigbo/public_html/app/mkomigbo/private/assets/initialize.php
 * Central bootstrap (production-safe):
 * - env + debug toggles
 * - structured JSONL logging with request correlation
 * - controlled PHP error -> exception conversion
 * - global exception + fatal shutdown handlers
 * - stable path constants (APP_ROOT / PRIVATE_PATH / SITE_ROOT / PUBLIC_ROOT / PUBLIC_PATH / PUBLIC_SUBDIR / WWW_ROOT)
 * - loads core helpers early (core_shim.php)
 * - loads db layer (database.php owns db())
 * - loads feature bootstrap via /private/functions/bootstrap_init.php (preferred)
 * - optional legacy compat: /private/functions/public_bootstrap.php
 */

/* -------------------------------------------------------------------------
 * 0) Idempotency: prevent double-init
 * ------------------------------------------------------------------------- */
if (defined('MK_APP_INITIALIZED') && MK_APP_INITIALIZED === true) {
  return;
}
define('MK_APP_INITIALIZED', true);

define('MK_ATTACH_DEBUG', true);

/* -------------------------------------------------------------------------
 * 1) Define APP_ROOT early
 * ------------------------------------------------------------------------- */
if (!defined('APP_ROOT')) {
  $appRoot = dirname(__DIR__, 2); // .../app/mkomigbo
  $rp = @realpath($appRoot);
  define('APP_ROOT', (is_string($rp) && $rp !== '') ? $rp : $appRoot);
}

/* -------------------------------------------------------------------------
 * 1b) Private path
 * ------------------------------------------------------------------------- */
if (!defined('PRIVATE_PATH')) {
  define('PRIVATE_PATH', APP_ROOT . '/private');
}

/* -------------------------------------------------------------------------
 * 1c) SITE_ROOT (public_html)
 * ------------------------------------------------------------------------- */
if (!defined('SITE_ROOT')) {
  $siteRoot = dirname(APP_ROOT, 2); // .../public_html
  $rp = @realpath($siteRoot);
  define('SITE_ROOT', (is_string($rp) && $rp !== '') ? $rp : $siteRoot);
}

/* -------------------------------------------------------------------------
 * 1d) Public filesystem roots (must match your real layout)
 * Web folder on disk: public_html/public
 * ------------------------------------------------------------------------- */
if (!defined('PUBLIC_ROOT')) {
  define('PUBLIC_ROOT', SITE_ROOT . '/public');
}
if (!defined('PUBLIC_PATH')) {
  define('PUBLIC_PATH', PUBLIC_ROOT);
}
if (!defined('PUBLIC_SUBDIR')) {
  define('PUBLIC_SUBDIR', PUBLIC_ROOT);
}

/* Legacy-safe aliases (prevent fatals in older code) */
if (!defined('SITE_PUBLIC'))  define('SITE_PUBLIC', PUBLIC_ROOT);
if (!defined('DOC_ROOT'))     define('DOC_ROOT', SITE_ROOT);
if (!defined('DOCUMENT_ROOT')) define('DOCUMENT_ROOT', SITE_ROOT);

/* -------------------------------------------------------------------------
 * 1e) URL base path (path prefix only; default '')
 * ------------------------------------------------------------------------- */
if (!defined('WWW_ROOT')) {
  $wr = (string)(getenv('WWW_ROOT') ?: '');
  $wr = trim($wr);

  if ($wr !== '' && $wr !== '/') {
    if ($wr[0] !== '/') { $wr = '/' . $wr; }
    $wr = rtrim($wr, '/');
  } else {
    $wr = '';
  }
  define('WWW_ROOT', $wr);
}

/* -------------------------------------------------------------------------
 * 2) REQUIRED: core_shim.php early (h(), url_for(), request helpers)
 * ------------------------------------------------------------------------- */
$__core_shim = __DIR__ . '/core_shim.php';
if (is_file($__core_shim)) {
  require_once $__core_shim;
} else {
  if (!headers_sent()) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
  }
  echo "Bootstrap failed: core_shim.php missing\nMissing: {$__core_shim}\n";
  exit;
}

/* -------------------------------------------------------------------------
 * 3) Environment toggles
 * ------------------------------------------------------------------------- */
if (!defined('APP_ENV')) {
  define('APP_ENV', (string)(getenv('APP_ENV') ?: 'prod'));
}
if (!defined('APP_DEBUG')) {
  define('APP_DEBUG', APP_ENV !== 'prod');
}
if (!defined('APP_DEBUG_OVERRIDE')) {
  $dbg = isset($_GET['__debug']) && (string)($_GET['__debug'] ?? '') === '1';
  define('APP_DEBUG_OVERRIDE', $dbg);
}

/* -------------------------------------------------------------------------
 * 4) Basic PHP error settings
 * ------------------------------------------------------------------------- */
error_reporting(E_ALL);
ini_set('display_errors', (APP_DEBUG || APP_DEBUG_OVERRIDE) ? '1' : '0');
ini_set('display_startup_errors', (APP_DEBUG || APP_DEBUG_OVERRIDE) ? '1' : '0');
ini_set('log_errors', '1');

if (!ini_get('date.timezone')) {
  @date_default_timezone_set('UTC');
}

/* -------------------------------------------------------------------------
 * 5) Logging paths (robust fallback)
 * ------------------------------------------------------------------------- */
if (!defined('LOG_DIR')) {
  define('LOG_DIR', APP_ROOT . '/logs');
}

$__logDir = LOG_DIR;
if (!is_dir($__logDir)) { @mkdir($__logDir, 0750, true); }
if (!is_dir($__logDir) || !is_writable($__logDir)) {
  $__logDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mkomigbo-logs';
  @mkdir($__logDir, 0750, true);
}

if (!defined('APP_LOG_FILE')) {
  define('APP_LOG_FILE', rtrim($__logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app.log.jsonl');
}

/* -------------------------------------------------------------------------
 * 6) Request correlation
 * ------------------------------------------------------------------------- */
if (!defined('REQUEST_ID')) {
  $rid = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
  if (!$rid) {
    try {
      $rid = bin2hex(random_bytes(8)) . '-' . bin2hex(random_bytes(4));
    } catch (Throwable $e) {
      $rid = uniqid('rid_', true);
    }
  }
  define('REQUEST_ID', (string)$rid);
}

/* -------------------------------------------------------------------------
 * 7) Mask sensitive keys
 * ------------------------------------------------------------------------- */
if (!function_exists('mask_sensitive')) {
  function mask_sensitive($value) {
    $sensitiveKeys = [
      'password','pass','pwd',
      'token','access_token','refresh_token',
      'authorization','api_key','apikey',
      'secret','session','cookie',
      'db_pass','db_password','csrf','csrf_token',
    ];

    if (is_array($value)) {
      $out = [];
      foreach ($value as $k => $v) {
        $lk = is_string($k) ? strtolower($k) : $k;
        if (is_string($lk) && in_array($lk, $sensitiveKeys, true)) $out[$k] = '[REDACTED]';
        else $out[$k] = mask_sensitive($v);
      }
      return $out;
    }

    if (is_string($value)) {
      if (strlen($value) > 5000) return substr($value, 0, 5000) . '...[TRUNCATED]';
      return $value;
    }

    return $value;
  }
}

/* -------------------------------------------------------------------------
 * 8) Structured JSONL logger
 * ------------------------------------------------------------------------- */
if (!function_exists('app_log')) {
  function app_log(string $level, string $message, array $context = []): void
  {
    $event = [
      'ts'         => gmdate('c'),
      'level'      => strtoupper($level),
      'message'    => $message,
      'request_id' => defined('REQUEST_ID') ? REQUEST_ID : null,
      'context'    => function_exists('mask_sensitive') ? mask_sensitive($context) : $context,
      'http'       => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'uri'    => $_SERVER['REQUEST_URI'] ?? null,
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua'     => $_SERVER['HTTP_USER_AGENT'] ?? null,
      ],
      'sapi' => PHP_SAPI,
    ];

    try {
      $line = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      if ($line === false) {
        $line = '{"ts":"' . gmdate('c') . '","level":"ERROR","message":"json_encode_failed","request_id":"' .
          (defined('REQUEST_ID') ? REQUEST_ID : '') . '"}';
      }
      $ok = @file_put_contents(APP_LOG_FILE, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
      if ($ok === false) error_log('[APP_LOG_FALLBACK] ' . $line);
    } catch (Throwable $e) {
      error_log('[APP_LOG_EXCEPTION] ' . $message . ' rid=' . (defined('REQUEST_ID') ? REQUEST_ID : 'n/a'));
    }
  }
}

/* -------------------------------------------------------------------------
 * 9) Safe require helper
 * ------------------------------------------------------------------------- */
if (!function_exists('mk_require_or_fail')) {
  function mk_require_or_fail(string $file, string $label = 'required file'): void
  {
    if (is_file($file)) { require_once $file; return; }

    if (function_exists('app_log')) {
      app_log('critical', 'Bootstrap require failed', [
        'label'   => $label,
        'missing' => $file,
        'app_root'=> defined('APP_ROOT') ? APP_ROOT : null,
      ]);
    }

    if (!headers_sent()) {
      http_response_code(500);
      header('Content-Type: text/plain; charset=utf-8');
    }

    $isDebug = (defined('APP_DEBUG') && APP_DEBUG) || (defined('APP_DEBUG_OVERRIDE') && APP_DEBUG_OVERRIDE);
    echo $isDebug
      ? ("Bootstrap failed: {$label}\nMissing: {$file}\nRID: " . (defined('REQUEST_ID') ? REQUEST_ID : 'n/a'))
      : ("Bootstrap failed. Reference: " . (defined('REQUEST_ID') ? REQUEST_ID : 'n/a'));
    exit;
  }
}

/* -------------------------------------------------------------------------
 * 10) Load .env (best-effort)
 * ------------------------------------------------------------------------- */
if (!function_exists('mk_load_env_file')) {
  function mk_load_env_file(string $path): void
  {
    if (!is_file($path) || !is_readable($path)) return;

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return;

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || $line[0] === '#') continue;

      $pos = strpos($line, '=');
      if ($pos === false) continue;

      $key = trim(substr($line, 0, $pos));
      $val = trim(substr($line, $pos + 1));
      if ($key === '') continue;

      if (strlen($val) >= 2) {
        $first = $val[0];
        $last  = $val[strlen($val) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
          $val = substr($val, 1, -1);
        }
      }

      if (getenv($key) === false) {
        @putenv($key . '=' . $val);
        $_ENV[$key] = $val;
      }
    }
  }
}
mk_load_env_file(APP_ROOT . '/.env');

/* -------------------------------------------------------------------------
 * 11) DB constants from env
 * ------------------------------------------------------------------------- */
if (!defined('DB_DSN')) {
  $dsn = (string)(getenv('DB_DSN') ?: '');

  $host    = (string)(getenv('DB_HOST') ?: '');
  $port    = (string)(getenv('DB_PORT') ?: '3306');
  $name    = (string)(getenv('DB_NAME') ?: '');
  $charset = (string)(getenv('DB_CHARSET') ?: 'utf8mb4');

  if ($dsn === '' && $host !== '' && $name !== '') {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
  }

  define('DB_DSN', $dsn);
}
if (!defined('DB_USER')) define('DB_USER', (string)(getenv('DB_USER') ?: ''));
if (!defined('DB_PASS')) define('DB_PASS', (string)(getenv('DB_PASS') ?: ''));

/* -------------------------------------------------------------------------
 * 12) Convert PHP errors to exceptions (controlled)
 * ------------------------------------------------------------------------- */
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
  if (!(error_reporting() & $severity)) return true;

  if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
    if (function_exists('app_log')) app_log('warning', 'PHP deprecated', ['message'=>$message,'file'=>$file,'line'=>$line]);
    return true;
  }

  if (defined('APP_DEBUG') && !APP_DEBUG && ($severity === E_NOTICE || $severity === E_USER_NOTICE)) {
    if (function_exists('app_log')) app_log('notice', 'PHP notice', ['message'=>$message,'file'=>$file,'line'=>$line]);
    return true;
  }

  throw new ErrorException($message, 0, $severity, $file, $line);
});

/* -------------------------------------------------------------------------
 * 13) Global exception handler
 * ------------------------------------------------------------------------- */
set_exception_handler(function (Throwable $e): void {
  if (function_exists('app_log')) {
    app_log('error', 'Unhandled exception', [
      'type'=>get_class($e),'code'=>$e->getCode(),'message'=>$e->getMessage(),
      'file'=>$e->getFile(),'line'=>$e->getLine(),
      'trace'=>explode("\n",$e->getTraceAsString()),
    ]);
  }

  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
  $wantJson = (stripos($accept, 'application/json') !== false);

  if (!headers_sent()) {
    http_response_code(500);
    header($wantJson ? 'Content-Type: application/json; charset=UTF-8'
                     : 'Content-Type: text/plain; charset=UTF-8');
  }

  $isDebug = (defined('APP_DEBUG') && APP_DEBUG) || (defined('APP_DEBUG_OVERRIDE') && APP_DEBUG_OVERRIDE);

  if ($isDebug) {
    echo "Server error (debug)\n";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo $e->getTraceAsString();
    return;
  }

  if ($wantJson) {
    echo json_encode(['ok'=>false,'error'=>'internal_error','reference'=>defined('REQUEST_ID')?REQUEST_ID:null],
      JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  } else {
    echo "An internal error occurred. Reference: " . (defined('REQUEST_ID') ? REQUEST_ID : 'n/a');
  }
});

/* -------------------------------------------------------------------------
 * 14) Fatal shutdown handler
 * ------------------------------------------------------------------------- */
register_shutdown_function(function (): void {
  $err = error_get_last();
  if (!$err) return;

  $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
  if (!in_array((int)$err['type'], $fatalTypes, true)) return;

  if (function_exists('app_log')) {
    app_log('critical', 'Fatal shutdown error', [
      'type'=>$err['type'],'message'=>$err['message'],'file'=>$err['file'],'line'=>$err['line'],
    ]);
  }

  if (!headers_sent()) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
  }

  $isDebug = (defined('APP_DEBUG') && APP_DEBUG) || (defined('APP_DEBUG_OVERRIDE') && APP_DEBUG_OVERRIDE);
  echo $isDebug
    ? ("Fatal error (debug). Reference: " . (defined('REQUEST_ID') ? REQUEST_ID : 'n/a'))
    : ("A fatal error occurred. Reference: " . (defined('REQUEST_ID') ? REQUEST_ID : 'n/a'));
});

/* -------------------------------------------------------------------------
 * 15) Core bootstraps (order matters)
 * ------------------------------------------------------------------------- */
mk_require_or_fail(APP_ROOT . '/private/assets/database.php', 'database');
mk_require_or_fail(APP_ROOT . '/private/functions/bootstrap_init.php', 'bootstrap_init');

/* Optional theme functions */
$__theme = APP_ROOT . '/private/functions/theme_functions.php';
if (is_file($__theme)) require_once $__theme;

/* Optional legacy compat */
$__legacy = APP_ROOT . '/private/functions/public_bootstrap.php';
if (is_file($__legacy)) require_once $__legacy;

/* -------------------------------------------------------------------------
 * 15b) Igbo Calendar export helper (HARD REQUIRED by request)
 *
 * IMPORTANT: do NOT load IgboCalendarYear here to avoid class collisions.
 * The export helper will load the engine on-demand when /igbo-calendar/download/ runs.
 * ------------------------------------------------------------------------- */
mk_require_or_fail(PRIVATE_PATH . '/functions/igbo_calendar_export.php', 'igbo_calendar_export');

if (!class_exists('IgboCalendarYear')) {
  $__cal_candidates = [
    PRIVATE_PATH . '/calendar/IgboCalendarYear.php',
    PRIVATE_PATH . '/functions/IgboCalendarYear.php',
  ];
  foreach ($__cal_candidates as $__f) {
    if (is_file($__f)) { require_once $__f; break; }
  }
  if (!class_exists('IgboCalendarYear') && function_exists('app_log')) {
    app_log('warning', 'IgboCalendarYear still not loaded after attempts', ['candidates'=>$__cal_candidates]);
  }
}

/* REQUIRED by your request: add export helper (best-effort) */
$__export = PRIVATE_PATH . '/functions/igbo_calendar_export.php';
if (is_file($__export)) {
  require_once $__export; // <-- inserted as requested (safe)
} else {
  if (function_exists('app_log')) app_log('warning', 'igbo_calendar_export.php missing', ['missing'=>$__export]);
}

/* Warm DB (non-fatal) */
try {
  if (function_exists('db')) {
    $pdo = db();
    if ($pdo instanceof PDO) $GLOBALS['pdo'] = $pdo;
  }
} catch (Throwable $e) {
  if (function_exists('app_log')) app_log('warning', 'DB warm-up failed (continuing)', ['message'=>$e->getMessage()]);
}

/* Initialize modules */
$plain = isset($_GET['__plain']) && (string)($_GET['__plain'] ?? '') === '1';
$needTheme = !$plain;

if (function_exists('mk_initialize')) {
  mk_initialize();
} elseif (function_exists('mk_public_bootstrap')) {
  mk_public_bootstrap(['cache'=>0,'need_theme'=>$needTheme]);
}

/* Validate core functions */
$__missing = [];
foreach (['h','url_for','db'] as $__fn) {
  if (!function_exists($__fn)) $__missing[] = $__fn;
}
if ($__missing) {
  if (function_exists('app_log')) app_log('critical', 'Core functions missing after initialize', ['missing'=>$__missing,'app_root'=>APP_ROOT]);
  throw new RuntimeException('Application bootstrap incomplete: missing ' . implode(', ', $__missing));
}
