<?php
declare(strict_types=1);

/**
 * /private/registry/admins_register.php
 * Compatibility stub (old project). Safe no-op registry.
 */

if (defined('MK_ADMINS_REGISTER_LOADED')) return;
define('MK_ADMINS_REGISTER_LOADED', true);

if (!function_exists('admins_registry_all')) {
  function admins_registry_all(): array
  {
    return [];
  }
}
