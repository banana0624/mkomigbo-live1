<?php
declare(strict_types=1);

/**
 * /igbo-calendar.php  (docroot entry)
 *
 * This must output ONE calendar shell only.
 * We delegate to /public/igbo-calendar/index.php and EXIT.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/public/_init.php';

/**
 * Guard: if something includes this file again, do nothing.
 */
if (defined('MK_IGBO_CALENDAR_ENTRY')) { return; }
define('MK_IGBO_CALENDAR_ENTRY', 1);

/**
 * Delegate to the public calendar shell.
 * (Do NOT call igbo_calendar_render.php here. That’s what caused the second header/app root.)
 */
$public = __DIR__ . '/public/igbo-calendar/index.php';
if (!is_file($public)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=UTF-8');
  echo "Missing calendar entry: {$public}";
  exit;
}

require $public;
exit;
