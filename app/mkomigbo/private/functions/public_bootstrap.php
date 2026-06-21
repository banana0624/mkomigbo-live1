<?php
declare(strict_types=1);

/**
 * /private/functions/public_bootstrap.php
 *
 * COMPATIBILITY SHIM (do not delete):
 * - Some parts of the codebase (and older deployments) may still require:
 *     /private/functions/public_bootstrap.php
 * - In THIS codebase, the “feature bootstrap” lives in:
 *     /private/functions/bootstrap_init.php
 *
 * This file:
 * - loads bootstrap_init.php (idempotent)
 * - provides mk_public_bootstrap() if missing, as a compatibility wrapper
 * - NEVER sends headers and does not change error_reporting
 */

if (defined('MK_PUBLIC_BOOTSTRAP_LOADED') && MK_PUBLIC_BOOTSTRAP_LOADED === true) {
  return;
}
define('MK_PUBLIC_BOOTSTRAP_LOADED', true);

if (!defined('APP_ROOT')) {
  // Best-effort: initialize.php should define APP_ROOT first.
  // // DISABLED_APP_ROOT (DISABLED_AUTO_FIX), dirname(__DIR__, 2));
}

$bootstrapInit = APP_ROOT . '/private/functions/bootstrap_init.php';
if (is_file($bootstrapInit)) {
  require_once $bootstrapInit;
}

require_once __DIR__ . '/seo_defaults.php';

/**
 * Compatibility wrapper:
 * - initialize.php historically calls mk_public_bootstrap([...])
 * - In your current design, mk_initialize() is the stable initializer
 */
if (!function_exists('mk_public_bootstrap')) {
  function mk_public_bootstrap(array $options = []): void
  {
    if (function_exists('mk_initialize')) {
      mk_initialize();
    }
    // Intentionally ignore $options here: public_bootstrap options are handled
    // by your core init chain (theme/public helpers) elsewhere.
  }
}
