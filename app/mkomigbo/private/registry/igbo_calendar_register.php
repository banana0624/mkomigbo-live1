<?php
declare(strict_types=1);

/**
 * /private/registry/igbo_calendar_register.php
 * Compatibility stub (old project). Safe no-op registry.
 */

if (defined('MK_IGBOCAL_REGISTER_LOADED')) return;
define('MK_IGBOCAL_REGISTER_LOADED', true);

if (!function_exists('igbo_calendar_registry')) {
  function igbo_calendar_registry(): array
  {
    return [];
  }
}
