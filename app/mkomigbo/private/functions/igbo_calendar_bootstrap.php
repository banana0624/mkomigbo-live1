<?php
declare(strict_types=1);

/**
 * /private/functions/igbo_calendar_bootstrap.php
 *
 * Hardened single include point for Igbo Calendar runtime dependencies.
 *
 * Loads in correct order:
 *  1) igbo calendar core functions (renderer + moon engine + igcal_* helpers)
 *  2) igbo_context.php (depends on igbo_moon_metrics)
 *  3) IgboCalendarYear class (if present)
 *  4) optional alternate renderer
 *
 * Debug:
 * - prints plain text only when ?debug=1 or IGCAL_FORCE_DEBUG is true
 */

if (!function_exists('igcal_boot_debug_enabled')) {
  function igcal_boot_debug_enabled(): bool {
    if (defined('IGCAL_FORCE_DEBUG') && IGCAL_FORCE_DEBUG === true) return true;
    return isset($_GET['debug']) && (string)$_GET['debug'] === '1';
  }
}

if (!function_exists('igcal_boot_fail')) {
  function igcal_boot_fail(string $msg): void {
    if (igcal_boot_debug_enabled()) {
      if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code(500);
      }
      echo "IGBO CALENDAR BOOTSTRAP ERROR\n";
      echo $msg . "\n";
    }
    exit;
  }
}

/* ---------------------------------------------------------
   Ensure PRIVATE_PATH and APP_ROOT are defined
--------------------------------------------------------- */
// This file path: .../private/functions/igbo_calendar_bootstrap.php
$privateFromHere = realpath(dirname(__DIR__));           // => .../private
$appFromHere     = ($privateFromHere !== false) ? realpath(dirname($privateFromHere)) : false;

if (!defined('PRIVATE_PATH') && $privateFromHere !== false) {
  define('PRIVATE_PATH', $privateFromHere);
}
if (!defined('APP_ROOT') && $appFromHere !== false) {
  // // DISABLED_APP_ROOT (DISABLED_AUTO_FIX), $appFromHere);
}

$private = defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, "/\\") : '';
if ($private === '' || !is_dir($private)) {
  igcal_boot_fail('PRIVATE_PATH is not defined or not a directory.');
}

$base = $private . DIRECTORY_SEPARATOR;

/* ---------------------------------------------------------
   1) Core calendar functions (required)
   Accept both underscore and hyphen variants.
--------------------------------------------------------- */
$coreCandidates = [
  $base . 'functions/igbo_calendar_functions.php', // underscore (your current)
  $base . 'functions/igbo-calendar_functions.php', // hyphen (older reference)
];

$core = '';
foreach ($coreCandidates as $p) {
  if (is_file($p)) { $core = $p; break; }
}
if ($core === '') {
  igcal_boot_fail('Missing required file. Tried: ' . implode(' | ', $coreCandidates));
}
require_once $core;

/* ---------------------------------------------------------
   2) Context engine (optional)
--------------------------------------------------------- */
$ctx = $base . 'functions/igbo_context.php';
if (is_file($ctx)) {
  require_once $ctx;
}

/* ---------------------------------------------------------
   3) IgboCalendarYear class (optional)
--------------------------------------------------------- */
$yearCandidates = [
  $base . 'calendar/IgboCalendarYear.php',
  $base . 'functions/IgboCalendarYear.php',
  $base . 'functions/IgboCalendarYear.legacy.php',
];

foreach ($yearCandidates as $p) {
  if (is_file($p)) { require_once $p; break; }
}

/* ---------------------------------------------------------
   4) Optional alternate renderer
--------------------------------------------------------- */
$render = $base . 'functions/igbo-calendar_render.php';
if (is_file($render)) {
  require_once $render;
}

/* ---------------------------------------------------------
   5) Active Igbo position helpers (needed by Igbo-year-first UI)
   Define only if not already defined.
--------------------------------------------------------- */

if (!function_exists('igbo_calendar_resolve_active_year_start_for_today')) {
  function igbo_calendar_resolve_active_year_start_for_today(DateTimeImmutable $todayUtc, array $opts = []): DateTimeImmutable
  {
    $tz = new DateTimeZone('UTC');
    $todayUtc = $todayUtc->setTimezone($tz)->setTime(0, 0, 0);

    $y = (int)$todayUtc->format('Y');
    $anchor = function_exists('igbo_anchor_with_epoch')
      ? igbo_anchor_with_epoch(igbo_get_market_anchor($opts))
      : igbo_get_market_anchor($opts);

    $startThis = igbo_find_new_year_start($y, $anchor, $opts['preferred_new_year_marketday'] ?? null)
      ->setTimezone($tz)->setTime(0, 0, 0);

    if ($todayUtc < $startThis) {
      return igbo_find_new_year_start($y - 1, $anchor, $opts['preferred_new_year_marketday'] ?? null)
        ->setTimezone($tz)->setTime(0, 0, 0);
    }
    return $startThis;
  }
}

if (!function_exists('igbo_calendar_current_position')) {
  function igbo_calendar_current_position(array $opts = []): array
  {
    $tz = new DateTimeZone('UTC');
    $today = (new DateTimeImmutable('now', $tz))->setTime(0, 0, 0);

    $yearStart = igbo_calendar_resolve_active_year_start_for_today($today, $opts);

    $gregYearOfStart = (int)$yearStart->format('Y');
    $isLeap = function_exists('igbo_is_gregorian_leap_year')
      ? igbo_is_gregorian_leap_year($gregYearOfStart)
      : (($gregYearOfStart % 400 === 0) || (($gregYearOfStart % 4 === 0) && ($gregYearOfStart % 100 !== 0)));

    $monthDays = function_exists('igbo_resolve_month_days')
      ? igbo_resolve_month_days($gregYearOfStart, $opts, $isLeap)
      : (function() use ($isLeap) {
            $md = array_fill(1, 13, 28);
            $md[7] = 29;
            if ($isLeap) $md[1] = 29;
            return $md;
          })();

    $diffDays = (int)floor(($today->getTimestamp() - $yearStart->getTimestamp()) / 86400);
    if ($diffDays < 0) $diffDays = 0;

    $m = 1;
    $dInMonth = $diffDays + 1;

    for ($month = 1; $month <= 13; $month++) {
      $len = (int)($monthDays[$month] ?? 28);
      if ($dInMonth <= $len) { $m = $month; break; }
      $dInMonth -= $len;
    }

    $m = max(1, min(13, $m));
    $len = (int)($monthDays[$m] ?? 28);
    $dInMonth = max(1, min($len, $dInMonth));

    return [
      'today_iso'      => $today->format('Y-m-d'),
      'year_start_iso' => $yearStart->format('Y-m-d'),
      'igbo_month'     => $m,
      'igbo_day'       => $dInMonth,
      'month_days'     => $monthDays,
    ];
  }
}
