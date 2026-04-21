<?php
declare(strict_types=1);

/**
 * /private/registry/pages_register.php
 * Compatibility stub (old project). Safe no-op registry.
 */

if (defined('MK_PAGES_REGISTER_LOADED')) return;
define('MK_PAGES_REGISTER_LOADED', true);

if (!function_exists('page_by_slug_registry')) {
  function page_by_slug_registry(string $scope, string $slug): ?array
  {
    return null;
  }
}
