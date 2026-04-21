<?php
declare(strict_types=1);

/**
 * HARD GATE:
 * This renderer must not output a full calendar shell unless explicitly allowed.
 * Prevents duplicate shells on /igbo-calendar/.
 */
if (!defined('MK_ALLOW_IGBO_CALENDAR_RENDER')) {
  return;
}


/**
 * /private/igbo-calendar/igbo_calendar_render.php
 *
 * Server-rendered wrapper for the Igbo Calendar HTML.
 * - Uses igbo_calendar_render_page() from igbo_calendar_functions.php
 * - Produces HTML with:
 *   - [data-app="igbo-calendar"]
 *   - data-selected-year / data-selected-month
 *   - month sections .igcal-month[data-ig-month="N"]
 *
 * IMPORTANT:
 * /private is not web-served. Include this from /public/igbo-calendar/index.php.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

require_once __DIR__ . '/igbo_calendar_functions.php';

/* Inputs */
$gy = isset($_GET['gy']) ? (int)$_GET['gy'] : (int)gmdate('Y');
if ($gy < 1600) $gy = 1600;
if ($gy > 2600) $gy = 2600;

$m  = isset($_GET['m']) ? (int)$_GET['m'] : 1;
if ($m < 1 || $m > 13) $m = 1;

/* Server-rendered calendar */
$calendarHtml = igbo_calendar_render_page($gy, [
  // optional overrides:
  // 'preferred_new_year_marketday' => 'Orie',
]);

/* Public asset URLs (served from /public/igbo-calendar/) */
$base = '/igbo-calendar';
$css  = $base . '/igbo-calendar.css';
$js   = $base . '/igbo-calendar.js';

$todayIso = gmdate('Y-m-d');
$yearStartEndpoint = $base . '/year-start.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="referrer" content="strict-origin-when-cross-origin">
  <title>Igbo Calendar</title>
  <meta name="description" content="Igbo Calendar with 4-day market-week logic and lunar context.">
  <link rel="stylesheet" href="<?= h($css) ?>?v=<?= (int)time() ?>">
</head>
<body class="igcal-body">

<main
  class="igcal-app"
  data-app="igbo-calendar"
  data-selected-year="<?= h((string)$gy) ?>"
  data-selected-month="<?= h((string)$m) ?>"
  data-yearstart-endpoint="<?= h($yearStartEndpoint) ?>"
  data-today-iso="<?= h($todayIso) ?>"
>
  <header class="igcal-hero">
    <h1 class="igcal-hero__title">Igbo Calendar</h1>
    <p class="igcal-hero__subtitle">Gregorian year <strong><?= (int)$gy ?></strong> · Month <strong><?= (int)$m ?></strong></p>

    <section class="igcal-controls" aria-label="Calendar controls">
      <div class="igcal-controls__row">
        <button class="igcal-btn" type="button" data-action="prev-month" id="igcal-prev-month">◀ Prev</button>
        <button class="igcal-btn igcal-btn--primary" type="button" data-action="current-month" id="igcal-current-month">Today</button>
        <button class="igcal-btn" type="button" data-action="next-month" id="igcal-next-month">Next ▶</button>

        <label class="igcal-field" style="margin-left:auto;min-width:200px;">
          <span class="igcal-field__label">Month</span>
          <select id="igcal-month-select" class="igcal-select" aria-label="Select month">
            <?php for ($i=1; $i<=13; $i++): ?>
              <option value="<?= (int)$i ?>"<?= $i===$m ? ' selected' : '' ?>><?= (int)$i ?></option>
            <?php endfor; ?>
          </select>
        </label>
      </div>
    </section>
  </header>

  <section class="igcal-card" aria-label="Calendar">
    <div class="igcal-card__body">
      <?= $calendarHtml ?>
    </div>
  </section>
</main>

<script src="<?= h($js) ?>?v=<?= (int)time() ?>" defer></script>
</body>
</html>
