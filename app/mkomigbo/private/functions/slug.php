<?php
declare(strict_types=1);

/**
 * /private/functions/slug.php
 *
 * LEGACY WRAPPER (DO NOT DEFINE FUNCTIONS HERE)
 * --------------------------------------------
 * This file historically contained mk_slugify(), but it must not anymore because:
 * - helpers.php is the canonical source of helpers (including mk_slugify()).
 * - duplicate declarations cause fatal errors.
 *
 * This wrapper ensures older code that "requires slug.php" still works.
 */

if (defined('MK_SLUG_LOADED') && MK_SLUG_LOADED === true) {
  return;
}
define('MK_SLUG_LOADED', true);

$helpers = __DIR__ . '/helpers.php';
if (is_file($helpers)) {
  require_once $helpers;
}
