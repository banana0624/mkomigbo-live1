<?php
declare(strict_types=1);

/**
 * /private/assets/database.php
 *
 * Database layer (single responsibility):
 * - Provide a cached PDO handle via db(): PDO
 * - Optionally provide a cached mysqli handle via db_mysqli(): mysqli (legacy support)
 *
 * Inputs (preferred order):
 * - DB_DSN, DB_USER, DB_PASS constants (from initialize.php)
 * - env vars: DB_DSN, DB_USER, DB_PASS
 *
 * Optional env vars (for mysql host/db style, or mysqli):
 * - DB_HOST, DB_PORT, DB_NAME
 *
 * Logging:
 * - app_log($level, $message, $context) if available
 * - otherwise error_log() (redacted)
 *
 * HARD RULES:
 * - No output, no headers
 * - No session operations
 */

if (defined('MK_DATABASE_LOADED') && MK_DATABASE_LOADED === true) {
  return;
}
define('MK_DATABASE_LOADED', true);

/* -------------------------------------------------------------------------
 * Internal helpers (scoped)
 * ------------------------------------------------------------------------- */
if (!function_exists('mk_db_log')) {
  function mk_db_log(string $level, string $message, array $context = []): void
  {
    // Never leak secrets
    $context = mk_db_redact_context($context);

    if (function_exists('app_log')) {
      app_log($level, $message, $context);
      return;
    }

    // Minimal fallback logging
    $suffix = $context ? (' ' . json_encode($context, JSON_UNESCAPED_SLASHES)) : '';
    error_log('[DB][' . strtoupper($level) . '] ' . $message . $suffix);
  }
}

if (!function_exists('mk_db_env')) {
  function mk_db_env(string $key): string
  {
    $v = getenv($key);
    return ($v === false) ? '' : (string)$v;
  }
}

if (!function_exists('mk_db_redact_dsn')) {
  function mk_db_redact_dsn(string $dsn): string
  {
    // DSN should not normally contain a password, but redact if present.
    $dsn = preg_replace('/(password=)([^;]+)/i', '$1[REDACTED]', $dsn);
    $dsn = preg_replace('/(pwd=)([^;]+)/i', '$1[REDACTED]', $dsn);
    return (string)$dsn;
  }
}

if (!function_exists('mk_db_redact_context')) {
  function mk_db_redact_context(array $context): array
  {
    $redactKeys = ['pass', 'password', 'db_pass', 'DB_PASS', 'secret', 'token'];

    foreach ($context as $k => $v) {
      $lk = strtolower((string)$k);

      // redact by key name
      foreach ($redactKeys as $rk) {
        if ($lk === strtolower($rk) || str_contains($lk, strtolower($rk))) {
          $context[$k] = '[REDACTED]';
          continue 2;
        }
      }

      // redact DSN values
      if ($lk === 'dsn' && is_string($v)) {
        $context[$k] = mk_db_redact_dsn($v);
      }
    }

    return $context;
  }
}

if (!function_exists('mk_db_guess_dsn')) {
  function mk_db_guess_dsn(): string
  {
    // If DSN is explicitly provided, use it.
    $dsn = defined('DB_DSN') ? (string)DB_DSN : mk_db_env('DB_DSN');
    $dsn = trim($dsn);
    if ($dsn !== '') return $dsn;

    // Otherwise, attempt to build a MySQL DSN from host/db env vars.
    $host = trim(mk_db_env('DB_HOST'));
    $name = trim(mk_db_env('DB_NAME'));
    $port = trim(mk_db_env('DB_PORT'));

    if ($host === '' || $name === '') return '';

    // Default port if omitted/invalid
    $portNum = (int)$port;
    if ($portNum <= 0) $portNum = 3306;

    // Use utf8mb4 and prefer strict-ish connection behavior.
    return "mysql:host={$host};port={$portNum};dbname={$name};charset=utf8mb4";
  }
}

if (!function_exists('mk_db_credentials')) {
  function mk_db_credentials(): array
  {
    $user = defined('DB_USER') ? (string)DB_USER : mk_db_env('DB_USER');
    $pass = (string) (getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '');
    return [trim($user), (string)$pass];
  }
}

if (!function_exists('mk_db_is_transient_disconnect')) {
  function mk_db_is_transient_disconnect(Throwable $e): bool
  {
    // MySQL “server has gone away” / “lost connection”
    $msg = strtolower($e->getMessage());
    if (str_contains($msg, 'server has gone away')) return true;
    if (str_contains($msg, 'lost connection')) return true;

    // PDO driver-specific codes can vary; keep message-based checks as primary.
    return false;
  }
}

/* -------------------------------------------------------------------------
 * PDO (primary)
 * ------------------------------------------------------------------------- */
if (!function_exists('db')) {
  function db(): PDO
  {
    // If another layer already populated it, respect it.
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
      return $GLOBALS['pdo'];
    }

    $dsn = mk_db_guess_dsn();
    [$user, $pass] = mk_db_credentials();

    if ($dsn === '') {
      mk_db_log('critical', 'DB_DSN is empty and DB_HOST/DB_NAME could not form a DSN', [
        'has_DB_DSN_env' => mk_db_env('DB_DSN') !== '' ? 1 : 0,
        'has_DB_HOST'    => mk_db_env('DB_HOST') !== '' ? 1 : 0,
        'has_DB_NAME'    => mk_db_env('DB_NAME') !== '' ? 1 : 0,
      ]);
      throw new RuntimeException('Database DSN is not configured.');
    }

    // NOTE: user can legitimately be empty on some local dev setups; still attempt.
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,

      // Keep connects responsive (avoid hangs on broken localhost sockets).
      // MySQL supports ATTR_TIMEOUT (seconds) for connection attempts in many builds.
      PDO::ATTR_TIMEOUT            => 5,
    ];

    try {
      $pdo = new PDO($dsn, $user, $pass, $options);

      // Ensure utf8mb4 (DSN charset usually handles it; this is a safe reinforcement)
      try {
        $pdo->exec("SET NAMES utf8mb4");
      } catch (Throwable $e) {
        mk_db_log('warning', 'SET NAMES utf8mb4 failed (non-fatal)', [
          'message' => $e->getMessage(),
        ]);
      }

      $GLOBALS['pdo'] = $pdo;
      return $pdo;

    } catch (Throwable $e) {
      mk_db_log('critical', 'PDO connection failed', [
        'message' => $e->getMessage(),
        'dsn'     => $dsn,
        'user'    => $user !== '' ? $user : '(empty)',
      ]);
      // Preserve original exception for debugging upstream if you ever toggle APP_DEBUG.
      throw new RuntimeException('Database connection failed.');
    }
  }
}

/**
 * Optional helper: db_ping()
 * - Verifies connection is alive.
 * - If transient disconnect, clears cache so next db() reconnects.
 */
if (!function_exists('db_ping')) {
  function db_ping(): bool
  {
    try {
      $pdo = db();
      $pdo->query('SELECT 1');
      return true;
    } catch (Throwable $e) {
      if (mk_db_is_transient_disconnect($e)) {
        unset($GLOBALS['pdo']);
        return false;
      }
      return false;
    }
  }
}

/**
 * Optional helper: db_reconnect()
 * - Clears cache and forces a fresh PDO creation.
 */
if (!function_exists('db_reconnect')) {
  function db_reconnect(): PDO
  {
    unset($GLOBALS['pdo']);
    return db();
  }
}

/* -------------------------------------------------------------------------
 * mysqli (optional legacy support)
 * Only create if requested by code.
 * ------------------------------------------------------------------------- */
if (!function_exists('db_mysqli')) {
  function db_mysqli(): mysqli
  {
    if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) {
      return $GLOBALS['mysqli'];
    }

    // Prefer host/db style env vars for mysqli.
    $host = trim(mk_db_env('DB_HOST'));
    $name = trim(mk_db_env('DB_NAME'));
    $port = (int)trim(mk_db_env('DB_PORT'));
    if ($port <= 0) $port = 3306;

    [$user, $pass] = mk_db_credentials();

    if ($host === '' || $name === '' || $user === '') {
      mk_db_log('critical', 'mysqli config missing (DB_HOST/DB_NAME/DB_USER required)', [
        'has_DB_HOST' => $host !== '' ? 1 : 0,
        'has_DB_NAME' => $name !== '' ? 1 : 0,
        'has_DB_USER' => $user !== '' ? 1 : 0,
      ]);
      throw new RuntimeException('mysqli database config is not complete.');
    }

    mysqli_report(MYSQLI_REPORT_OFF);

    $mysqli = @new mysqli($host, $user, $pass, $name, $port);
    if ($mysqli->connect_error) {
      mk_db_log('critical', 'mysqli connection failed', [
        'error' => $mysqli->connect_error,
        'host'  => $host,
        'port'  => $port,
        'db'    => $name,
        'user'  => $user,
      ]);
      throw new RuntimeException('mysqli connection failed.');
    }

    // Ensure utf8mb4
    if (!$mysqli->set_charset('utf8mb4')) {
      mk_db_log('warning', 'mysqli set_charset utf8mb4 failed (non-fatal)', [
        'error' => $mysqli->error,
      ]);
    }

    $GLOBALS['mysqli'] = $mysqli;
    return $mysqli;
  }
}
