<?php
declare(strict_types=1);
// public_html/public/igbo-calendar/_diag.php

@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

/**
 * Catch fatal errors that hosts often suppress.
 */
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    echo "\n\n[FATAL]\n";
    echo $e['message'] . "\n";
    echo $e['file'] . ':' . $e['line'] . "\n";
  }
});

/**
 * Catch uncaught exceptions.
 */
set_exception_handler(function (Throwable $e) {
  echo "\n\n[EXCEPTION]\n";
  echo get_class($e) . "\n";
  echo $e->getMessage() . "\n";
  echo $e->getFile() . ':' . $e->getLine() . "\n";
  echo $e->getTraceAsString() . "\n";
  exit;
});

echo "IGBO CALENDAR DIAG\n";
echo "------------------\n";
echo "PHP_VERSION: " . PHP_VERSION . "\n";
echo "SCRIPT: " . __FILE__ . "\n\n";

$init = __DIR__ . '/../_init.php';
echo "INIT: {$init}\n";
if (!is_file($init)) {
  echo "[FAIL] _init.php missing\n";
  exit;
}

require_once $init;

echo "APP_ROOT: " . (defined('APP_ROOT') ? APP_ROOT : '(not defined)') . "\n";
echo "PRIVATE_PATH: " . (defined('PRIVATE_PATH') ? PRIVATE_PATH : '(not defined)') . "\n";

$boot = (defined('PRIVATE_PATH') ? (PRIVATE_PATH . '/functions/igbo_calendar_bootstrap.php') : '');
echo "BOOT: {$boot}\n";
if (!is_file($boot)) {
  echo "[FAIL] bootstrap missing\n";
  exit;
}

/**
 * Force bootstrap debug mode even if host suppresses output.
 */
define('IGCAL_FORCE_DEBUG', true);

require_once $boot;

echo "[OK] bootstrap loaded\n";
echo "igbo_calendar_render_page: " . (function_exists('igbo_calendar_render_page') ? 'YES' : 'NO') . "\n";
echo "igbo_calendar_current_position: " . (function_exists('igbo_calendar_current_position') ? 'YES' : 'NO') . "\n";
echo "igbo_moon_metrics: " . (function_exists('igbo_moon_metrics') ? 'YES' : 'NO') . "\n";
echo "igbo_context_for_date: " . (function_exists('igbo_context_for_date') ? 'YES' : 'NO') . "\n";

if (function_exists('igbo_calendar_current_position')) {
  $pos = igbo_calendar_current_position();
  echo "\nCURRENT POSITION:\n";
  var_export($pos);
  echo "\n";
}

echo "\n[OK] diag complete\n";
