<?php
declare(strict_types=1);

// /public/igbo-calendar/index.php
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

if (!function_exists('h')) {
    function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

$year = 0;
if (isset($_GET['year'])) {
    $year = (int) $_GET['year'];
} elseif (isset($_GET['gy'])) {
    $year = (int) $_GET['gy'];
}
if ($year < 1) {
    $year = (int) gmdate('Y');
}
if ($year < 1) {
    $year = 1;
}
if ($year > 9999) {
    $year = 9999;
}

$m = isset($_GET['m']) ? (int) $_GET['m'] : 1;
if ($m < 1 || $m > 13) {
    $m = 1;
}

$todayIso = gmdate('Y-m-d');
$currentGregorianYear = (int) gmdate('Y');

$monthNames = [
    1  => 'Ọnwa Mbụ',
    2  => 'Ọnwa Abụọ',
    3  => 'Ọnwa Ife Eke',
    4  => 'Ọnwa Anọ',
    5  => 'Ọnwa Agwụ',
    6  => 'Ọnwa Ifejiọkụ',
    7  => 'Ọnwa Alọm Chi / Asaa',
    8  => 'Ọnwa Ilo Mmụọ / Asatọ',
    9  => 'Ọnwa Ala / Itolu',
    10 => 'Ọnwa Okike / Iri',
    11 => 'Ọnwa Ajala / Iri na otu',
    12 => 'Ọnwa Ede Ajala / Iri na abụọ',
    13 => 'Ọnwa Ụzọ Arụsị / Iri na atọ',
];

$monthDetail = [
    1  => 'Ọnwa Mbụ (Feb–Mar): New Year (Mbido Afọ)',
    2  => 'Ọnwa Abụọ (Mar–Apr): Farm Clearing & Cleansing',
    3  => 'Ọnwa Ife Eke (Apr–May): Fasting & Offering',
    4  => 'Ọnwa Anọ (May–Jun): Planting Season',
    5  => 'Ọnwa Agwụ (Jun–Jul): Masquerade Rites / Knowledge',
    6  => 'Ọnwa Ifejiọkụ (Jul–Aug): Yam Festival / Rituals',
    7  => 'Ọnwa Alọm Chi / Asaa (Aug–Sep): New Yam Festival',
    8  => 'Ọnwa Ilo Mmụọ / Asatọ (Sep–Oct): Ancestral Veneration',
    9  => 'Ọnwa Ala / Itolu (Oct–Nov): Ofala Festival',
    10 => 'Ọnwa Okike / Iri (Nov): Creation Myths / Offerings',
    11 => 'Ọnwa Ajala / Iri na otu (Nov–Dec): Spiritual Cleansing',
    12 => 'Ọnwa Ede Ajala / Iri na abụọ (Dec–Jan): End-Year Offerings',
    13 => 'Ọnwa Ụzọ Arụsị / Iri na atọ (Jan–Feb): Intercalary Rituals',
];

$canonical = 'https://mkomigbo.com/igbo-calendar/';

$base = '/igbo-calendar';
$css = $base . '/igbo-calendar.css';
$js = $base . '/igbo-calendar.js';
$ux = $base . '/calendar-ux.js';
$pwa = $base . '/pwa-hook.js';
$yearStartEndpoint = $base . '/year-start.php';

$title = 'Amujzi Igbo Calendar App';
$desc = 'Amujzi Igbo Calendar App (13-month system with market-week logic and lunar context).';

$pubBase = dirname(__DIR__) . '/igbo-calendar';

$css_v = is_file($pubBase . '/igbo-calendar.css') ? (string) @filemtime($pubBase . '/igbo-calendar.css') : (string) time();
$js_v  = is_file($pubBase . '/igbo-calendar.js') ? (string) @filemtime($pubBase . '/igbo-calendar.js') : (string) time();
$ux_v  = is_file($pubBase . '/calendar-ux.js') ? (string) @filemtime($pubBase . '/calendar-ux.js') : (string) time();
$pwa_v = is_file($pubBase . '/pwa-hook.js') ? (string) @filemtime($pubBase . '/pwa-hook.js') : (string) time();

$hasManifestJson = is_file($pubBase . '/manifest.json');
$manifestHref = $base . '/manifest.json';
$manifest_v = $hasManifestJson ? (string) @filemtime($pubBase . '/manifest.json') : (string) time();

$hasCowrieSvg = is_file($pubBase . '/icons/cowrie-icon.svg');
$hasIcon16 = is_file($pubBase . '/icons/icon-16.png');
$hasIcon32 = is_file($pubBase . '/icons/icon-32.png');
$hasIcon48 = is_file($pubBase . '/icons/icon-48.png');
$hasIcon72 = is_file($pubBase . '/icons/icon-72.png');
$hasIcon192 = is_file($pubBase . '/icons/icon-192.png');
$hasIcon512 = is_file($pubBase . '/icons/icon-512.png');
$hasFavicon = is_file($pubBase . '/icons/favicon.ico');

$cowrie_v = $hasCowrieSvg ? (string) @filemtime($pubBase . '/icons/cowrie-icon.svg') : (string) time();
$icon16_v = $hasIcon16 ? (string) @filemtime($pubBase . '/icons/icon-16.png') : (string) time();
$icon32_v = $hasIcon32 ? (string) @filemtime($pubBase . '/icons/icon-32.png') : (string) time();
$icon48_v = $hasIcon48 ? (string) @filemtime($pubBase . '/icons/icon-48.png') : (string) time();
$icon72_v = $hasIcon72 ? (string) @filemtime($pubBase . '/icons/icon-72.png') : (string) time();
$icon192_v = $hasIcon192 ? (string) @filemtime($pubBase . '/icons/icon-192.png') : (string) time();
$icon512_v = $hasIcon512 ? (string) @filemtime($pubBase . '/icons/icon-512.png') : (string) time();
$favicon_v = $hasFavicon ? (string) @filemtime($pubBase . '/icons/favicon.ico') : (string) time();

$y_from = max(1, $year - 200);
$y_to = min(9999, $year + 200);

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="referrer" content="strict-origin-when-cross-origin">
  <meta name="theme-color" content="#0b0b0b">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

  <title><?php echo h($title); ?></title>
  <meta name="description" content="<?php echo h($desc); ?>">
  <link rel="canonical" href="<?php echo h($canonical); ?>">

  <meta property="og:type" content="website">
  <meta property="og:title" content="<?php echo h($title); ?>">
  <meta property="og:description" content="<?php echo h($desc); ?>">
  <meta property="og:url" content="<?php echo h($canonical); ?>">

  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="<?php echo h($title); ?>">
  <meta name="twitter:description" content="<?php echo h($desc); ?>">

<?php if ($hasManifestJson): ?>
  <link rel="manifest" href="<?php echo h($manifestHref . '?v=' . $manifest_v); ?>">
<?php endif; ?>

<?php if ($hasCowrieSvg): ?>
  <link rel="icon" type="image/svg+xml" href="<?php echo h($base . '/icons/cowrie-icon.svg?v=' . $cowrie_v); ?>">
<?php endif; ?>
<?php if ($hasIcon16): ?>
  <link rel="icon" type="image/png" sizes="16x16" href="<?php echo h($base . '/icons/icon-16.png?v=' . $icon16_v); ?>">
<?php endif; ?>
<?php if ($hasIcon32): ?>
  <link rel="icon" type="image/png" sizes="32x32" href="<?php echo h($base . '/icons/icon-32.png?v=' . $icon32_v); ?>">
<?php endif; ?>
<?php if ($hasIcon48): ?>
  <link rel="icon" type="image/png" sizes="48x48" href="<?php echo h($base . '/icons/icon-48.png?v=' . $icon48_v); ?>">
  <link rel="apple-touch-icon" sizes="48x48" href="<?php echo h($base . '/icons/icon-48.png?v=' . $icon48_v); ?>">
<?php endif; ?>
<?php if ($hasIcon72): ?>
  <link rel="apple-touch-icon" sizes="72x72" href="<?php echo h($base . '/icons/icon-72.png?v=' . $icon72_v); ?>">
<?php endif; ?>
<?php if ($hasIcon192): ?>
  <link rel="icon" type="image/png" sizes="192x192" href="<?php echo h($base . '/icons/icon-192.png?v=' . $icon192_v); ?>">
  <link rel="apple-touch-icon" sizes="192x192" href="<?php echo h($base . '/icons/icon-192.png?v=' . $icon192_v); ?>">
<?php endif; ?>
<?php if ($hasIcon512): ?>
  <link rel="icon" type="image/png" sizes="512x512" href="<?php echo h($base . '/icons/icon-512.png?v=' . $icon512_v); ?>">
  <link rel="apple-touch-icon" sizes="512x512" href="<?php echo h($base . '/icons/icon-512.png?v=' . $icon512_v); ?>">
<?php endif; ?>
<?php if ($hasFavicon): ?>
  <link rel="shortcut icon" href="<?php echo h($base . '/icons/favicon.ico?v=' . $favicon_v); ?>">
<?php elseif ($hasIcon32): ?>
  <link rel="shortcut icon" href="<?php echo h($base . '/icons/icon-32.png?v=' . $icon32_v); ?>">
<?php elseif ($hasIcon16): ?>
  <link rel="shortcut icon" href="<?php echo h($base . '/icons/icon-16.png?v=' . $icon16_v); ?>">
<?php endif; ?>

  <link rel="stylesheet" href="<?php echo h($css); ?>?v=<?php echo h($css_v); ?>">
</head>

<body class="igcal-body">
<a class="igcal-sr-only" href="#igcal-app">Skip to calendar</a>

<div class="igbo-calendar-app">
  <div class="igcal-shell">
    <main
      id="igcal-app"
      class="igcal-app"
      data-app="igbo-calendar"
      data-selected-year="<?php echo h((string) $year); ?>"
      data-selected-month="<?php echo h((string) $m); ?>"
      data-yearstart-endpoint="<?php echo h($yearStartEndpoint); ?>"
      data-today-iso="<?php echo h($todayIso); ?>"
    >
      <div class="igcal-layout">

        <aside class="igcal-sidebar">
          <div class="igcal-sidebar__inner">

            <header class="igcal-hero">
              <div class="igcal-hero__top">
                <div class="igcal-hero__brand">
                  <h1 class="igcal-hero__title"><?php echo h($title); ?></h1>

                  <div class="igcal-hero__subtitle-stack">
                    <div class="igcal-hero__subtitle-line">
                      <span class="muted">Current Igbo Year</span>
                      <strong id="igcal-current-year-label"><?php echo (int) $currentGregorianYear; ?></strong>
                    </div>
                    <div class="igcal-hero__subtitle-line">
                      <span class="muted">Current Igbo Month</span>
                      <strong id="igcal-current-month-label">Loading…</strong>
                    </div>
                    <div class="igcal-hero__subtitle-line">
                      <span class="muted">Current Day</span>
                      <strong id="igcal-current-day-label">Loading…</strong>
                    </div>
                  </div>
                </div>

                <nav class="igcal-hero__links" aria-label="Calendar links">
                  <a class="igcal-link" href="/igbo-calendar/install/">Install</a>
                </nav>
              </div>

              <div class="mk-cowrie-emblem mk-cowrie-grid" aria-hidden="true">
                <span class="mk-cowrie" aria-hidden="true"></span>
                <span class="mk-cowrie" aria-hidden="true"></span>
                <span class="mk-cowrie" aria-hidden="true"></span>
                <span class="mk-cowrie" aria-hidden="true"></span>
              </div>
            </header>

            <section class="igcal-panel-group" aria-label="Current calendar summary">
              <section class="igcal-card igcal-card--feature igcal-today-context" aria-labelledby="igcal-current-summary-title">
                <div class="igcal-card__body">
                  <div class="igcal-panel-head">
                    <h2 id="igcal-current-summary-title" class="igcal-panel-title">Current Day</h2>
                    <span class="igcal-panel-kicker">Static</span>
                  </div>
                  <div id="igcal-current-summary" class="igcal-summary-box" aria-live="polite">
                    Loading current day…
                  </div>
                </div>
              </section>
            </section>

            <section class="igcal-panel-group" aria-label="Viewed calendar controls">
              <section class="igcal-card igcal-day-panel" aria-labelledby="igcal-viewed-day-title" data-ig-viewed-day="1">
                <div class="igcal-card__body">
                  <div class="igcal-panel-head">
                    <h2 id="igcal-viewed-day-title" class="igcal-panel-title">Viewed Day</h2>
                    <span class="igcal-panel-kicker">Responsive</span>
                  </div>

                  <div id="igcal-viewed-day-summary" class="igcal-summary-box" aria-live="polite">
                    Select a day from the calendar.
                  </div>

                  <div class="igcal-controls__row igcal-controls__row--nav igcal-day-nav">
                    <button class="igcal-btn" type="button" id="igcal-prev-day">◀ Previous Day</button>
                    <button class="igcal-btn igcal-btn--primary" type="button" id="igcal-today-day" data-ig-today-btn="1">Today</button>
                    <button class="igcal-btn" type="button" id="igcal-next-day">Next Day ▶</button>
                  </div>
                </div>
              </section>

              <section class="igcal-card igcal-month-panel" aria-labelledby="igcal-viewed-month-title">
                <div class="igcal-card__body">
                  <div class="igcal-panel-head">
                    <h2 id="igcal-viewed-month-title" class="igcal-panel-title">Viewed Month</h2>
                    <span class="igcal-panel-kicker">Responsive</span>
                  </div>

                  <div id="igcal-viewed-month-summary" class="igcal-summary-box" aria-live="polite">
                    Loading month…
                  </div>

                  <div class="igcal-controls__row igcal-controls__row--nav igcal-month-nav">
                    <button class="igcal-btn" type="button" id="igcal-prev-month">◀ Previous Month</button>
                    <button class="igcal-btn igcal-btn--primary" type="button" id="igcal-this-month">This Month</button>
                    <button class="igcal-btn" type="button" id="igcal-next-month">Next Month ▶</button>
                  </div>

                  <label class="igcal-field igcal-field--wide">
                    <span class="igcal-field__label">Month</span>
                    <select id="igcal-month-select" class="igcal-select">
<?php for ($i = 1; $i <= 13; $i++): ?>
                      <option
                        value="<?php echo (int) $i; ?>"
                        data-month-name="<?php echo h($monthNames[$i] ?? ('Month ' . $i)); ?>"
                        data-month-detail="<?php echo h($monthDetail[$i] ?? ''); ?>"
<?php echo ($i === $m) ? ' selected' : ''; ?>
                      >
                        <?php echo (int) $i; ?> — <?php echo h($monthNames[$i] ?? ('Month ' . $i)); ?>
                      </option>
<?php endfor; ?>
                    </select>
                  </label>
                </div>
              </section>

              <section class="igcal-card" aria-labelledby="igcal-viewed-year-title" data-ig-viewed-year="1">
                  <div class="igcal-card__body">
                    <div class="igcal-panel-head">
                      <h2 id="igcal-viewed-year-title" class="igcal-panel-title">Viewed Year</h2>
                      <span class="igcal-panel-kicker">Responsive</span>
                    </div>
                
                    <div class="mk-view-actions" data-mk-key="viewed-year-inline">
                      <button type="button" class="mk-mini-btn" id="igcal-year-copy">Copy</button>
                      <button type="button" class="mk-mini-btn" id="igcal-year-share">Share</button>
                      <button type="button" class="mk-mini-btn" id="igcal-year-print">Print</button>
                    </div>
                    <div class="igcal-sharebar__status" data-ig-share-status="year" aria-live="polite"></div>
                
                    <div class="mk-view-nav" data-mk-key="viewed-year-nav-inline">
                      <button type="button" class="mk-mini-btn" id="igcal-year-prev">Previous Year</button>
                      <button type="button" class="mk-mini-btn" id="igcal-year-current">This Year</button>
                      <button type="button" class="mk-mini-btn" id="igcal-year-next">Next Year</button>
                    </div>
                
                    <div id="igcal-viewed-year-summary" class="igcal-summary-box" aria-live="polite">
                      Loading year…
                    </div>
                
                    <div class="igcal-year-control">
                      <label class="igcal-field igcal-field--wide">
                        <span class="igcal-field__label">Year</span>
                        <select id="igcal-year-select" class="igcal-select">
                <?php for ($y = $y_from; $y <= $y_to; $y++): ?>
                          <option value="<?php echo (int) $y; ?>"<?php echo ($y === $year) ? ' selected' : ''; ?>>
                            <?php echo (int) $y; ?>
                          </option>
                <?php endfor; ?>
                        </select>
                      </label>
                      <button class="igcal-btn igcal-btn--primary igcal-year-apply" type="button" id="igcal-apply-year">Go</button>
                    </div>
                  </div>
                </section>

              <section class="igcal-card" aria-labelledby="igcal-range-title" data-ig-year-range="1">
                <div class="igcal-card__body">
                  <div class="igcal-panel-head">
                    <h2 id="igcal-range-title" class="igcal-panel-title">Year Start / End</h2>
                    <span class="igcal-panel-kicker">Responsive</span>
                  </div>

                  <div class="mk-view-actions" data-mk-key="year-range-inline">
                    <button type="button" class="mk-mini-btn" id="igcal-range-copy">Copy</button>
                    <button type="button" class="mk-mini-btn" id="igcal-range-share">Share</button>
                    <button type="button" class="mk-mini-btn" id="igcal-range-print">Print</button>
                  </div>
                  <div class="igcal-sharebar__status" data-ig-share-status="range" aria-live="polite"></div>

                  <div id="igcal-year-range-summary" class="igcal-summary-box" aria-live="polite">
                    Loading year range…
                  </div>

                  <label class="igcal-field igcal-field--wide">
                    <span class="igcal-field__label">Available Year Ranges</span>
                    <select id="igcal-gregorian-range" class="igcal-select">
                      <option value="">Loading…</option>
                    </select>
                  </label>
                </div>
              </section>

              <footer class="igcal-footer">
                <p class="igcal-footer__meta">
                  <span>Igbo Calendar</span>
                  <span aria-hidden="true">·</span>
                  <a class="igcal-link" href="/igbo-calendar/">Home</a>
                </p>
              </footer>
            </section>
          </div>
        </aside>

        <section class="igcal-main">
          <section class="igcal-card" aria-label="Calendar">
            <div class="igcal-card__body">
              <div class="igcal-loading" role="status" aria-live="polite">Loading calendar…</div>
              <div id="igcal-mount" data-role="calendar-mount" aria-live="polite"></div>
            </div>
          </section>
        </section>

      </div>
<section id="awag-section" style="max-width:1200px;margin:32px auto;padding:0 16px 48px;"><div class="awag-header"><h2>AWAG &mdash; Activity Guide</h2><span id="awag-month-label"></span></div><div id="awag-modules-mount"><p style="color:rgba(232,245,224,.5);padding:20px 0;">Loading activity guide...</p></div></section>
    </main>
  </div>
</div>

<script src="<?php echo h($js); ?>?v=<?php echo h($js_v); ?>" defer></script>
<script src="<?php echo h($ux); ?>?v=<?php echo h($ux_v); ?>" defer></script>
<script src="/awag/igbo-calendar/awag-modules.js?v=3"></script>
<script src="<?php echo h($pwa); ?>?v=<?php echo h($pwa_v); ?>" defer></script>

</body>
</html>