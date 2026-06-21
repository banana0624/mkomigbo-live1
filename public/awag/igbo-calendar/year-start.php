<?php
declare(strict_types=1);

/**
 * /public/igbo-calendar/year-start.php
 * Public JSON endpoint:
 *   ?year=2025  -> {"ok":true,"year":2025,"ys":"2025-02-27"}
 *
 * Uses igbo_calendar_bootstrap.php + fallback year-start resolver.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$year = (int)($_GET['year'] ?? 0);
if ($year < 1 || $year > 9999) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid year'], JSON_UNESCAPED_SLASHES);
  exit;
}

/* Bootstrap */
$private = defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, '/') : '';
$boot = ($private !== '' ? ($private . '/functions/igbo_calendar_bootstrap.php') : '');

if ($boot === '' || !is_file($boot)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Bootstrap missing'], JSON_UNESCAPED_SLASHES);
  exit;
}
require_once $boot;

/* Fallback: same as index.php (critical) */
if (!function_exists('igbo_calendar_year_start_for_gregorian_year')) {
  function igbo_calendar_year_start_for_gregorian_year(int $gregYear, array $opts = []): DateTimeImmutable {
    $tz = new DateTimeZone('UTC');
    if (!function_exists('igbo_get_market_anchor')
      || !function_exists('igbo_anchor_with_epoch')
      || !function_exists('igbo_find_new_year_start')) {
      throw new RuntimeException('Calendar primitives missing');
    }

    $anchor = igbo_anchor_with_epoch(igbo_get_market_anchor($opts));
    $s = igbo_find_new_year_start($gregYear, $anchor, $opts['preferred_new_year_marketday'] ?? null);
    return $s->setTimezone($tz)->setTime(0, 0, 0);
  }
}

try {
  $ys = igbo_calendar_year_start_for_gregorian_year($year)->format('Y-m-d');
  echo json_encode(['ok' => true, 'year' => $year, 'ys' => $ys], JSON_UNESCAPED_SLASHES);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Failed to compute year start'], JSON_UNESCAPED_SLASHES);
  exit;
}
