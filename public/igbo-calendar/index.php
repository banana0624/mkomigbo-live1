<?php
declare(strict_types=1);

/**
 * /public/igbo-calendar/index.php
 * Public: Igbo Calendar (PWA-ready) — Igbo-year-first.
 *
 * Key behavior:
 * - Active (default) view is the Igbo year that contains TODAY (UTC), not Gregorian year.
 * - Navigation uses:
 *     ys=YYYY-MM-DD  (Igbo year start date)
 *     m=1..13        (month number within that Igbo year)
 * - Prev/Next month buttons sync with Igbo year boundaries via JS:
 *     Month 1 prev -> loads previous Igbo year (month 13)
 *     Month 13 next -> loads next Igbo year (month 1)
 * - "Current Month" always returns to active Igbo year (no params).
 *
 * Requires:
 * - PRIVATE_PATH/functions/igbo_calendar_bootstrap.php
 * - /public/igbo-calendar/igbo-calendar.js
 * - /public/igbo-calendar/igbo-calendar.css
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

/* Emergency debug (only when ?debug=1) */
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
  @ini_set('display_errors', '1');
  @ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);

  set_error_handler(function($sev, $msg, $file, $line){
    header('Content-Type: text/plain; charset=utf-8');
    echo "PHP ERROR: {$msg}\n{$file}:{$line}\n";
    exit;
  });

  set_exception_handler(function($e){
    header('Content-Type: text/plain; charset=utf-8');
    echo "PHP EXCEPTION: " . get_class($e) . "\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo $e->getTraceAsString();
    exit;
  });
}


if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

/* ---------------------------------------------------------
   Includes (single entry bootstrap)
--------------------------------------------------------- */
$private = defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, '/') : '';
$boot = ($private !== '' ? ($private . '/functions/igbo_calendar_bootstrap.php') : '');

if ($boot === '' || !is_file($boot)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Igbo calendar bootstrap missing\nExpected: {$boot}\n";
  exit;
}
require_once $boot;

if (!function_exists('igbo_calendar_render_page') || !function_exists('igbo_calendar_current_position')) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Calendar functions missing: igbo_calendar_render_page() / igbo_calendar_current_position()\n";
  exit;
}

/* ---------------------------------------------------------
   Cache-busting helper
--------------------------------------------------------- */
$asset = static function(string $path) use ($u): string {
  if (function_exists('mk_asset_ver')) {
    try { return (string)mk_asset_ver($path); } catch (Throwable $e) {}
  }
  return $u($path);
};

/* ---------------------------------------------------------
   PWA URLs + meta
--------------------------------------------------------- */
$manifest_url = $u('/igbo-calendar/manifest.json');

$brand_name = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomigbo';
$page_title = 'Igbo Calendar • ' . $brand_name;
$page_desc  = '4-day market week (Eke/Orie/Afo/Nkwo), 13-month structure, and daily moon phase.';
$nav_active = 'igbo-calendar';
$active_nav = 'igbo-calendar';

$extra_css = [
  $asset('/igbo-calendar/igbo-calendar.css'),
];

$extra_head = ''
  . '<link rel="manifest" href="' . h($manifest_url) . '">' . "\n"
  . '<meta name="theme-color" content="#ffffff">' . "\n"
  . '<meta name="mobile-web-app-capable" content="yes">' . "\n"
  . '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n"
  . '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";

$GLOBALS['page_title'] = $page_title;
$GLOBALS['page_desc']  = $page_desc;
$GLOBALS['nav_active'] = $nav_active;
$GLOBALS['active_nav'] = $active_nav;
$GLOBALS['extra_css']  = $extra_css;
$GLOBALS['extra_head'] = $extra_head;

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => $nav_active,
      'active_nav' => $active_nav,
      'extra_css'  => $extra_css,
      'extra_head' => $extra_head,
    ]);
  } catch (Throwable $e) {}
}

/* Optional short cache (HTML only) */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && function_exists('mk_public_cache_headers')) {
  mk_public_cache_headers(60);
}

/* Header */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try {
    mk_require_shared('public_header.php');
    $header_ok = true;
  } catch (Throwable $e) {}
}
if (!$header_ok) {
  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html lang='en'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo $extra_head;
  foreach ($extra_css as $css) {
    echo "<link rel='stylesheet' href='" . h($css) . "'>";
  }
  echo "<title>" . h($page_title) . "</title></head><body>";
}

/* ---------------------------------------------------------
   Calendar configuration (safe knobs)
--------------------------------------------------------- */
$GLOBALS['mk_moon_calibration_days'] = -0.45;
$GLOBALS['mk_moon_sprite_offset']    = 0;

$tz = new DateTimeZone('UTC');
$todayIso = (new DateTimeImmutable('now', $tz))->format('Y-m-d');
$GLOBALS['mk_igbo_today_iso'] = $todayIso;

/**
 * Query params:
 * - ys=YYYY-MM-DD  explicit Igbo year start
 * - m=1..13        month within that Igbo year
 */
$qsYearStart = (string)($_GET['ys'] ?? '');
$qsMonth     = (int)($_GET['m'] ?? 0);

/* Resolve active Igbo position (authoritative "current") */
$pos = igbo_calendar_current_position();
$activeYearStartIso = (string)($pos['year_start_iso'] ?? '');
$activeMonth = (int)($pos['igbo_month'] ?? 1);
$activeDay   = (int)($pos['igbo_day'] ?? 1);

if ($activeMonth < 1 || $activeMonth > 13) $activeMonth = 1;
if ($activeDay < 1) $activeDay = 1;

/* Resolve selected year start */
$yearStartIso = $activeYearStartIso;
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $qsYearStart)) {
  $yearStartIso = $qsYearStart;
}
try {
  $yearStart = new DateTimeImmutable($yearStartIso . ' 00:00:00', $tz);
} catch (Throwable $e) {
  $yearStart = new DateTimeImmutable($activeYearStartIso . ' 00:00:00', $tz);
  $yearStartIso = $yearStart->format('Y-m-d');
}

/* Resolve selected month */
$selectedMonth = ($qsMonth >= 1 && $qsMonth <= 13) ? $qsMonth : $activeMonth;

/* ---------------------------------------------------------
   Compute prev/next Igbo-year starts (true boundaries)
--------------------------------------------------------- */
if (!function_exists('igbo_calendar_year_start_for_gregorian_year')) {
  function igbo_calendar_year_start_for_gregorian_year(int $gregYear, array $opts = []): DateTimeImmutable {
    $tz = new DateTimeZone('UTC');
    $anchor = igbo_anchor_with_epoch(igbo_get_market_anchor($opts));
    $s = igbo_find_new_year_start($gregYear, $anchor, $opts['preferred_new_year_marketday'] ?? null);
    return $s->setTimezone($tz)->setTime(0, 0, 0);
  }
}

$gregOfStart   = (int)$yearStart->format('Y');
$prevYearStart = igbo_calendar_year_start_for_gregorian_year($gregOfStart - 1);
$nextYearStart = igbo_calendar_year_start_for_gregorian_year($gregOfStart + 1);

$baseUrl = $u('/igbo-calendar/');

$buildUrl = static function(string $ys, int $m) use ($baseUrl): string {
  $m = max(1, min(13, $m));
  $q = 'ys=' . rawurlencode($ys) . '&m=' . rawurlencode((string)$m);
  return $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . $q;
};

$prevYearUrl = $buildUrl($prevYearStart->format('Y-m-d'), $selectedMonth);
$nextYearUrl = $buildUrl($nextYearStart->format('Y-m-d'), $selectedMonth);

/* "Home" means: active Igbo year & current month (no params) */
$homeUrl = $baseUrl;

/* ---------------------------------------------------------
   Render calendar (one Igbo year)
   We pass the Gregorian year of the Igbo-year start to the engine,
   and force today_iso to keep highlight stable.
--------------------------------------------------------- */
$renderGregYear = (int)$yearStart->format('Y');

$render_error = null;
$render_html = '';

try {
  $opts = [
    'today_iso' => $todayIso,
    // Keep your checkpoint preferences; engine already defaults to 2026-01-07=Nkwo
    'market_anchor_date' => '2026-01-07',
    'market_anchor_day'  => 'Nkwo',
    'preferred_new_year_marketday' => ($renderGregYear === 2026 ? 'Orie' : null),
  ];
  $render_html = igbo_calendar_render_page($renderGregYear, $opts);
} catch (Throwable $e) {
  $render_error = $e;
}

/* Links */
$install_url  = $u('/igbo-calendar/install/');
$download_url = $u('/igbo-calendar/download/');

/* Today context (for hero summary) */
$todayCtx = function_exists('igbo_context_for_date')
  ? igbo_context_for_date(new DateTimeImmutable($todayIso . ' 00:00:00', $tz))
  : null;

?>
<main class="container igbo-calendar-app"
      data-app="igbo-calendar"
      data-today-iso="<?= h($todayIso) ?>"
      data-year-start="<?= h($yearStartIso) ?>"
      data-selected-month="<?= (int)$selectedMonth ?>"
      data-prev-year-url="<?= h($prevYearUrl) ?>"
      data-next-year-url="<?= h($nextYearUrl) ?>"
      data-home-url="<?= h($homeUrl) ?>"
      style="padding:24px 0;">

  <!-- Sticky wrapper: reduces “bulky” feel; calendar scrolls under -->
  <div class="igcal-sticky-header">

    <section class="hero mk-hero">
      <div class="hero-bar mk-hero__bar"></div>
      <div class="hero-inner mk-hero__inner">
        <h1 class="mk-hero__title">Igbo Calendar</h1>

        <?php if (is_array($todayCtx)): ?>
          <div class="mk-card mk-card--soft" style="margin-top:14px; max-width:680px;">
            <div class="muted" style="font-size:.9rem; margin-bottom:6px;">Today’s Context (UTC)</div>
            <div style="display:flex; gap:12px; flex-wrap:wrap; font-size:.95rem;">
              <div><strong>Market:</strong> <?= h((string)($todayCtx['market_day'] ?? '')) ?></div>
              <div><strong>Element:</strong> <?= h((string)($todayCtx['element'] ?? '')) ?> <?= h((string)($todayCtx['element_symbol'] ?? '')) ?></div>
              <div><strong>Moon:</strong> <?= (int)($todayCtx['moon_pct'] ?? 0) ?>%</div>
              <div><strong>Stage:</strong> <?= h((string)($todayCtx['moon_stage'] ?? '')) ?></div>
              <div><strong>Igbo Today:</strong> Month <?= (int)$activeMonth ?> Day <?= (int)$activeDay ?></div>
            </div>
          </div>
        <?php endif; ?>

        <p class="muted mk-hero__desc" style="max-width:78ch;">
          4-day market week (Eke / Orie / Afo / Nkwo), 13-month structure, and daily moon phase.
          Checkpoint enforced: <strong>2026-01-07 = Nkwo</strong>.
        </p>

        <div class="cta-row mk-install" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
          <a class="btn" href="<?= h($download_url) ?>" download>Download export</a>
          <a class="btn btn--ghost" href="<?= h($install_url) ?>">Install Help</a>
          <button class="btn" id="mkInstallBtn" type="button" style="display:none;">Install App</button>
          <span class="muted" id="mkInstallNote" style="font-size:.92rem; line-height:1.5;"></span>
        </div>
      </div>
    </section>

    <!-- Igbo-year-first control bar -->
    <section style="margin-top:14px;">
      <div class="mk-card mk-card--soft">
        <div class="igcal-toolbar" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-start; justify-content:space-between;">

          <!-- Year box (styled + prominent) -->
          <div class="igcal-yearbox" style="min-width:min(620px,100%);">
            <div class="igcal-yearbox__label">Go to Igbo Year</div>

            <div class="igcal-yearbox__row">
              <a class="btn" href="<?= h($prevYearUrl) ?>" aria-label="Previous Igbo year">‹ Year</a>

              <select id="igcalYearSelect" class="mk-input" aria-label="Select Igbo year">
                <?php
                  // Compact window around current start year
                  $years = [];
                  for ($y = $gregOfStart - 3; $y <= $gregOfStart + 3; $y++) {
                    $ys = igbo_calendar_year_start_for_gregorian_year($y)->format('Y-m-d');
                    $years[] = ['label' => 'Igbo Year starting ' . $ys, 'ys' => $ys];
                  }
                  foreach ($years as $row):
                    $sel = ($row['ys'] === $yearStartIso) ? ' selected' : '';
                ?>
                  <option value="<?= h($row['ys']) ?>"<?= $sel ?>><?= h($row['label']) ?></option>
                <?php endforeach; ?>
              </select>

              <a class="btn" href="<?= h($nextYearUrl) ?>" aria-label="Next Igbo year">Year ›</a>
            </div>

            <div class="igcal-yearbox__meta">
              <span><strong>Viewing year start:</strong> <?= h($yearStartIso) ?></span>
              <span class="igcal-yearbox__dot">•</span>
              <span><strong>Active year start:</strong> <?= h($activeYearStartIso) ?></span>
              <span class="igcal-yearbox__dot">•</span>
              <span><strong>Selected month:</strong> <?= (int)$selectedMonth ?>/13</span>
            </div>
          </div>

          <!-- Month controls -->
          <div class="igcal-actions" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <button type="button" class="btn" data-action="prev-month">‹ Previous Month</button>
            <button type="button" class="btn" data-action="current-month">Current Month</button>
            <button type="button" class="btn" data-action="next-month">Next Month ›</button>
            <button type="button" class="btn" data-action="toggle-4weeks" aria-pressed="false">Toggle 4 Weeks</button>
          </div>

        </div>
      </div>
    </section>

  </div><!-- /igcal-sticky-header -->

  <section style="margin-top:18px;">
    <?php if ($render_error instanceof Throwable): ?>
      <div class="mk-alert mk-alert--danger" style="margin-top:12px;">
        <strong>Calendar error:</strong>
        <pre style="white-space:pre-wrap; margin:8px 0 0;"><?= h('Calendar failed to render.') ?></pre>
      </div>
    <?php else: ?>
      <?= $render_html ?>
    <?php endif; ?>
  </section>

</main>

<script src="<?= h($u('/igbo-calendar/pwa-hook.js')) ?>" defer></script>
<script src="<?= h($u('/igbo-calendar/igbo-calendar.js')) ?>" defer></script>

<?php
if (function_exists('mk_require_shared')) {
  mk_require_shared('public_footer.php');
} else {
  echo "</body></html>";
}
