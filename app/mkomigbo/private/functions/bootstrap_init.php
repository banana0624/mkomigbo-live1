<?php
declare(strict_types=1);

/**
 * /private/functions/bootstrap_init.php
 *
 * PURPOSE:
 * - Provide mk_initialize() as a stable, idempotent initializer that can be invoked
 *   by initialize.php (or legacy code) without duplicating responsibilities.
 *
 * CONTRACT:
 * - initialize.php owns: APP_ROOT/SITE_ROOT/PUBLIC_PATH/WWW_ROOT, logging, handlers, env parsing.
 * - bootstrap_init.php owns: loading feature modules once (contain.php + optional route modules),
 *   plus compatibility shims and schema helpers that depend on db().
 *
 * HARD RULES:
 * - Must not send headers.
 * - Must not change error_reporting aggressively.
 * - Must not re-implement db() here.
 */

if (defined('MK_BOOTSTRAP_INIT_LOADED') && MK_BOOTSTRAP_INIT_LOADED === true) {
  return;
}
define('MK_BOOTSTRAP_INIT_LOADED', true);

/* ---------------------------------------------------------
 * Resolve core paths from the constants you actually use now
 * --------------------------------------------------------- */
if (!defined('APP_ROOT')) {
  // .../app/mkomigbo
  define('APP_ROOT', dirname(__DIR__, 2));
}
if (!defined('SITE_ROOT')) {
  // .../public_html
  define('SITE_ROOT', dirname(APP_ROOT, 2));
}
if (!defined('PUBLIC_PATH')) {
  // For legacy compatibility in your codebase; PUBLIC_SUBDIR is the actual /public folder.
  define('PUBLIC_PATH', SITE_ROOT);
}
if (!defined('PUBLIC_SUBDIR')) {
  define('PUBLIC_SUBDIR', SITE_ROOT . '/public');
}

if (!defined('FUNCTIONS_PATH')) define('FUNCTIONS_PATH', APP_ROOT . '/private/functions');
if (!defined('ASSETS_PATH'))    define('ASSETS_PATH', APP_ROOT . '/private/assets');
if (!defined('SHARED_PATH'))    define('SHARED_PATH', APP_ROOT . '/private/shared');

/* ---------------------------------------------------------
 * Minimal require helper (use initialize.php helper if present)
 * --------------------------------------------------------- */
if (!function_exists('mk__require_or_fail')) {
  function mk__require_or_fail(string $file, string $label): void
  {
    if (function_exists('mk_require_or_fail')) {
      mk_require_or_fail($file, $label);
      return;
    }
    if (!is_file($file)) {
      throw new RuntimeException("Bootstrap missing {$label}: {$file}");
    }
    require_once $file;
  }
}

/* ---------------------------------------------------------
 * Minimal log helper (safe even if logger not loaded)
 * Prefers: app_log() -> mk_log()/mk_app_log() -> error_log()
 * --------------------------------------------------------- */
if (!function_exists('mk__log_bootstrap_notice')) {
  function mk__log_bootstrap_notice(string $message, array $context = []): void
  {
    try {
      if (function_exists('app_log')) {
        app_log('notice', $message, $context);
        return;
      }
      if (function_exists('mk_log')) {
        mk_log('NOTICE', $message, $context);
        return;
      }
      if (function_exists('mk_app_log')) {
        mk_app_log('NOTICE', $message, $context);
        return;
      }
    } catch (Throwable $e) {
      // Fall through to error_log below
    }

    error_log('[bootstrap_init][NOTICE] ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES));
  }
}

if (!function_exists('mk__log_bootstrap_error')) {
  function mk__log_bootstrap_error(string $message, array $context = []): void
  {
    try {
      if (function_exists('app_log')) {
        app_log('error', $message, $context);
        return;
      }
      if (function_exists('mk_log')) {
        mk_log('ERROR', $message, $context);
        return;
      }
      if (function_exists('mk_app_log')) {
        mk_app_log('ERROR', $message, $context);
        return;
      }
    } catch (Throwable $e) {
      // Fall through to error_log below
    }

    error_log('[bootstrap_init][ERROR] ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES));
  }
}

/* ---------------------------------------------------------
 * Public API: mk_initialize() (idempotent)
 * --------------------------------------------------------- */
if (!function_exists('mk_initialize')) {
  function mk_initialize(): void
  {
    static $ran = false;
    if ($ran) return;
    $ran = true;

    // 0) Core helpers must exist early (h(), url_for(), request helpers, etc.)
    $shim = ASSETS_PATH . '/core_shim.php';
    if (is_file($shim)) {
      try {
        require_once $shim;
      } catch (Throwable $e) {
        mk__log_bootstrap_error('core_shim.php load failed', [
          'file' => $shim,
          'error' => $e->getMessage(),
        ]);
        // Do not throw here; initialize.php will fail-fast if truly required.
      }
    } else {
      mk__log_bootstrap_error('core_shim.php missing', ['expected' => $shim]);
      // Do not throw; initialize.php owns fail-fast policy.
    }

    // 1) Public bootstrap helpers (routing/theme glue)
    if (!function_exists('mk_public_bootstrap')) {
      mk__require_or_fail(FUNCTIONS_PATH . '/public_bootstrap.php', 'public_bootstrap');
    }

    // 2) Theme helpers (pf__accent_for etc.)
    if (!function_exists('pf__accent_for')) {
      $theme = FUNCTIONS_PATH . '/theme_functions.php';
      if (is_file($theme)) {
        require_once $theme;
      }
    }

    // 3) DB layer (PDO db()) – do NOT implement db() here
    if (!function_exists('db')) {
      mk__require_or_fail(ASSETS_PATH . '/database.php', 'database');
    }

    // 4) Shared include helper
    mk_init_shared_include_helpers();

    // 5) Schema helpers (depends on db())
    mk_init_schema_helpers();

    // 6) Legacy compat shims (optional, safe)
    mk_init_legacy_compat();

    // 7) Feature hub (contain.php) – safe load (no public hard-crash)
    mk_init_contain_safe();

    // 8) Route-scoped feature requirements (safe load)
    mk_init_required_features_safe();
  }
}

/* ---------------------------------------------------------
 * Shared include helper
 * --------------------------------------------------------- */
if (!function_exists('mk_init_shared_include_helpers')) {
  function mk_init_shared_include_helpers(): void
  {
    if (!function_exists('mk_require_shared')) {
      function mk_require_shared(string $file): void
      {
        $path = rtrim((string)SHARED_PATH, '/') . '/' . ltrim($file, '/');
        if (!is_file($path)) {
          throw new RuntimeException('Shared include not found: ' . $path);
        }
        require_once $path;
      }
    }
  }
}

/* ---------------------------------------------------------
 * Schema helpers (DB-safe, cached)
 * --------------------------------------------------------- */
if (!function_exists('mk_init_schema_helpers')) {
  function mk_init_schema_helpers(): void
  {
    if (!function_exists('mk_table_columns')) {
      function mk_table_columns(PDO $pdo, string $table): array
      {
        static $cache = [];
        $key = strtolower($table);
        if (isset($cache[$key])) return $cache[$key];

        try {
          $st = $pdo->query("DESCRIBE `{$table}`");
          $cols = [];
          foreach (($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
            if (!empty($row['Field'])) {
              $cols[strtolower((string)$row['Field'])] = true;
            }
          }
          return $cache[$key] = $cols;
        } catch (Throwable $e) {
          return $cache[$key] = [];
        }
      }
    }

    if (!function_exists('mk_has_column')) {
      function mk_has_column(PDO $pdo, string $table, string $column): bool
      {
        $cols = mk_table_columns($pdo, $table);
        return isset($cols[strtolower($column)]);
      }
    }

    if (!function_exists('mk_has_table')) {
      function mk_has_table(PDO $pdo, string $table): bool
      {
        try {
          $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
          return true;
        } catch (Throwable $e) {
          return false;
        }
      }
    }

    // Keep your existing pf__* aliases (your code relies on them)
    if (!function_exists('pf__table_exists_hard')) {
      function pf__table_exists_hard(PDO $pdo, string $table): bool { return mk_has_table($pdo, $table); }
    }

    if (!function_exists('pf__column_exists_hard')) {
      function pf__column_exists_hard(PDO $pdo, string $table, string $column): bool
      {
        try {
          $pdo->query("SELECT `{$column}` FROM `{$table}` LIMIT 1");
          return true;
        } catch (Throwable $e) {
          return false;
        }
      }
    }

    if (!function_exists('pf__column_exists')) {
      function pf__column_exists(PDO $pdo, string $table, string $column): bool { return mk_has_column($pdo, $table, $column); }
    }
  }
}

/* ---------------------------------------------------------
 * Legacy compatibility (never hard-fail)
 * - Unifies legacy globals: $db, $pdo, $GLOBALS['db'], $GLOBALS['pdo']
 * --------------------------------------------------------- */
if (!function_exists('mk_init_legacy_compat')) {
  function mk_init_legacy_compat(): void
  {
    global $db;

    try {
      // Prefer already-set globals
      if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        $GLOBALS['db'] = $GLOBALS['pdo'];
        $db = $GLOBALS['pdo'];
        return;
      }
      if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
        $GLOBALS['pdo'] = $GLOBALS['db'];
        $db = $GLOBALS['db'];
        return;
      }

      // If a local/global $db exists
      if ($db instanceof PDO) {
        $GLOBALS['pdo'] = $db;
        $GLOBALS['db']  = $db;
        return;
      }

      // Otherwise attempt db()
      if (function_exists('db')) {
        $pdo = db();
        if ($pdo instanceof PDO) {
          $GLOBALS['pdo'] = $pdo;
          $GLOBALS['db']  = $pdo;
          $db = $pdo;
          return;
        }
      }
    } catch (Throwable $e) {
      // Never hard-fail.
    }
  }
}

/* ---------------------------------------------------------
 * Request path helper
 * --------------------------------------------------------- */
if (!function_exists('mk_request_path')) {
  function mk_request_path(): string
  {
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($uri, PHP_URL_PATH);
    $path = is_string($path) ? $path : '/';
    return ($path !== '') ? $path : '/';
  }
}

/* ---------------------------------------------------------
 * Contain (dependency hub) – safe load (no public hard-crash)
 * --------------------------------------------------------- */
if (!function_exists('mk_init_contain_safe')) {
  function mk_init_contain_safe(): void
  {
    if (defined('MK_CONTAIN_LOADED') && MK_CONTAIN_LOADED === true) return;

    $contain = FUNCTIONS_PATH . '/contain.php';
    if (!is_file($contain)) {
      mk__log_bootstrap_error('contain.php missing; continuing without contain', ['expected' => $contain]);
      return;
    }

    try {
      require_once $contain;
      if (!defined('MK_CONTAIN_LOADED')) define('MK_CONTAIN_LOADED', true);
    } catch (Throwable $e) {
      mk__log_bootstrap_error('contain.php load failed; continuing', [
        'expected' => $contain,
        'error' => $e->getMessage(),
      ]);
    }
  }
}

/* ---------------------------------------------------------
 * Route-scoped requirements – safe (no hard-crash)
 * --------------------------------------------------------- */
if (!function_exists('mk_init_required_features_safe')) {
  function mk_init_required_features_safe(): void
  {
    $path = mk_request_path();

    // Igbo calendar routes
    $needs_calendar = (bool)preg_match('~^/igbo-calendar(?:/|$)~', $path);
    if ($needs_calendar) {
      mk__bootstrap_calendar_safe($path);
    }

    // Future: add other route-scoped modules here similarly (subjects, contributors, platforms, etc.)
  }
}

/* ---------------------------------------------------------
 * Calendar module safe loader
 * --------------------------------------------------------- */
if (!function_exists('mk__bootstrap_calendar_safe')) {
  function mk__bootstrap_calendar_safe(string $path): void
  {
    // If already available, do nothing.
    if (
      function_exists('igbo_calendar_render_page') ||
      function_exists('igbo_calendar_render') ||
      function_exists('igbo_calendar_render_app')
    ) {
      return;
    }

    // Load candidates (your codebase has used both styles historically).
    $candidates = [
      FUNCTIONS_PATH . '/igbo_calendar_functions.php',
      FUNCTIONS_PATH . '/igbo_calendar_render.php',
    ];

    foreach ($candidates as $file) {
      if (!is_file($file)) continue;

      try {
        require_once $file;
      } catch (Throwable $e) {
        mk__log_bootstrap_error('Igbo calendar module load failed', [
          'file' => $file,
          'error' => $e->getMessage(),
        ]);
      }
    }

    // Still missing? Do NOT throw. Log and let the route handle its own rendering error state.
    if (
      !function_exists('igbo_calendar_render_page') &&
      !function_exists('igbo_calendar_render') &&
      !function_exists('igbo_calendar_render_app')
    ) {
      mk__log_bootstrap_error('Igbo calendar renderer not found after loading candidates', [
        'path' => $path,
        'candidates' => $candidates,
      ]);
    }
  }
}
