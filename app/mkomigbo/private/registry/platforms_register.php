<?php
declare(strict_types=1);

/**
 * /private/registry/platforms_register.php
 * Compatibility stub (old project). Safe no-op registry.
 */

if (defined('MK_PLATFORMS_REGISTER_LOADED')) return;
define('MK_PLATFORMS_REGISTER_LOADED', true);

if (!function_exists('platform_by_slug_registry')) {
  function platform_by_slug_registry(string $slug): ?array
  {
    return null; // registry not used; DB/pages drive platforms
  }
}

if (!function_exists('platforms_registry_all')) {
  function platforms_registry_all(): array
  {
    return [];
  }
}
