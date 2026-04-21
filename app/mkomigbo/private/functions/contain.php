<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/functions/contain.php
 * Dependency container / include hub.
 *
 * Contract:
 * - No output
 * - No redirects
 * - No DB queries directly
 * - Never fatal: every include is guarded
 */

if (defined('MK_CONTAIN_LOADED')) {
  return;
}
define('MK_CONTAIN_LOADED', true);

/* ---------------------------------------------------------
 * Path constants (derive safely from this file location)
 * --------------------------------------------------------- */
$__private = realpath(dirname(__DIR__)); // .../app/mkomigbo/private
if (!defined('PRIVATE_PATH')) {
  define('PRIVATE_PATH', $__private !== false ? $__private : dirname(__DIR__));
}
if (!defined('APP_ROOT')) {
  define('APP_ROOT', dirname((string)PRIVATE_PATH)); // .../app/mkomigbo
}
if (!defined('FUNCTIONS_PATH')) define('FUNCTIONS_PATH', rtrim((string)PRIVATE_PATH, "/\\") . '/functions');
if (!defined('ASSETS_PATH'))    define('ASSETS_PATH',    rtrim((string)PRIVATE_PATH, "/\\") . '/assets');
if (!defined('SHARED_PATH'))    define('SHARED_PATH',    rtrim((string)PRIVATE_PATH, "/\\") . '/shared');

/* ---------------------------------------------------------
 * Safe require helper (never fatal)
 * --------------------------------------------------------- */
$req = static function(string $abs): void {
  try {
    if ($abs !== '' && is_file($abs)) require_once $abs;
  } catch (Throwable $e) {
    // Swallow to keep contain "never fatal".
    // Your bootstrap/logger may record this elsewhere.
  }
};

/* ---------------------------------------------------------
 * Public bootstrap (optional)
 * --------------------------------------------------------- */
$req(FUNCTIONS_PATH . '/public_bootstrap.php');

/* ---------------------------------------------------------
 * Core helpers (load early)
 * --------------------------------------------------------- */
$req(FUNCTIONS_PATH . '/helpers.php');                    // your project helpers
$req((string)PRIVATE_PATH . '/common/helper_functions.php'); // h(), url_for(), etc (legacy)

/* ---------------------------------------------------------
 * Validation / utilities (optional)
 * --------------------------------------------------------- */
$req(ASSETS_PATH . '/validation_functions.php');
$req(ASSETS_PATH . '/other_utilities.php');

/* ---------------------------------------------------------
 * Database (only define db() if not already defined)
 * --------------------------------------------------------- */
if (!function_exists('db')) {
  $req(ASSETS_PATH . '/database.php');
}

/* ---------------------------------------------------------
 * Schema helpers (optional)
 * --------------------------------------------------------- */
if (!function_exists('mk_has_column') || !function_exists('mk_has_table') || !function_exists('mk_table_columns')) {
  $req(ASSETS_PATH . '/config.php');
}

/* ---------------------------------------------------------
 * AuthN / AuthZ (optional)
 * --------------------------------------------------------- */
$req(ASSETS_PATH . '/auth_functions.php');
$req(FUNCTIONS_PATH . '/authz.php');

/* ---------------------------------------------------------
 * Domain functions (optional)
 * --------------------------------------------------------- */
$req(ASSETS_PATH . '/subject_functions.php');
$req(ASSETS_PATH . '/page_functions.php');
$req(ASSETS_PATH . '/admin_functions.php');
$req(ASSETS_PATH . '/image_functions.php');

/* ---------------------------------------------------------
 * Theme + registry + SEO (optional)
 * --------------------------------------------------------- */
$req(FUNCTIONS_PATH . '/theme_functions.php');
$req(FUNCTIONS_PATH . '/subjects_registry_helpers.php');
$req(FUNCTIONS_PATH . '/seo_helpers.php');

/* ---------------------------------------------------------
 * Optional external utilities
 * --------------------------------------------------------- */
$req(ASSETS_PATH . '/send_email.php');
