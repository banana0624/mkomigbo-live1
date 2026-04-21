<?php
declare(strict_types=1);

/**
 * /private/registry/staff_register.php
 * Compatibility stub (old project). Safe no-op registry.
 */

if (defined('MK_STAFF_REGISTER_LOADED')) return;
define('MK_STAFF_REGISTER_LOADED', true);

if (!function_exists('staff_registry_all')) {
  function staff_registry_all(): array
  {
    return [];
  }
}
