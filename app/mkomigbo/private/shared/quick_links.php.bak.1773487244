<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/shared/quick_links.php
 * Shared: CTA row + optional tip + "Quick links" cards
 *
 * CLEAN MODE (Option B):
 * - No inline <style> blocks
 * - No inline style="" attributes
 * - Styling lives in external CSS (public.css)
 *
 * Optional inputs (set BEFORE require/include):
 * - $ql_title (string) default "Quick links"
 * - $ql_include_staff (bool) default false
 * - $ql_tip (string|null) optional tip line shown under the CTA row
 */

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$ql_title = (isset($ql_title) && is_string($ql_title) && trim($ql_title) !== '')
  ? trim($ql_title)
  : 'Quick links';

$ql_include_staff = isset($ql_include_staff) ? (bool)$ql_include_staff : false;

$ql_tip = (isset($ql_tip) && is_string($ql_tip) && trim($ql_tip) !== '')
  ? trim($ql_tip)
  : null;

/* URLs */
$subjects_url      = function_exists('url_for') ? url_for('/subjects/')      : '/subjects/';
$platforms_url     = function_exists('url_for') ? url_for('/platforms/')     : '/platforms/';
$contributors_url  = function_exists('url_for') ? url_for('/contributors/')  : '/contributors/';
$igbo_calendar_url = function_exists('url_for') ? url_for('/igbo-calendar/') : '/igbo-calendar/';
$staff_url         = function_exists('url_for') ? url_for('/staff/')         : '/staff/';

/**
 * Map accents to CSS classes (so we don't use inline style="--accent: ...")
 * Your public.css can style these classes, e.g.:
 * .mk-ql-accent--blue { --accent:#2563eb; } etc.
 */
$accent_class = static function(string $hex): string {
  $hex = strtolower(trim($hex));
  return match ($hex) {
    '#2563eb' => 'mk-ql-accent--blue',
    '#7c3aed' => 'mk-ql-accent--purple',
    '#059669' => 'mk-ql-accent--green',
    '#0ea5e9' => 'mk-ql-accent--sky',
    '#f59e0b' => 'mk-ql-accent--amber',
    default   => 'mk-ql-accent--blue',
  };
};

$items = [
  [
    'href'   => $subjects_url,
    'badge'  => 'S',
    'title'  => 'Subjects',
    'desc'   => 'Browse all topics and dive into pages.',
    'accent' => '#2563eb',
  ],
  [
    'href'   => $platforms_url,
    'badge'  => 'P',
    'title'  => 'Platforms',
    'desc'   => 'Explore tools and sections like calendars, posts, and more.',
    'accent' => '#7c3aed',
  ],
  [
    'href'   => $contributors_url,
    'badge'  => 'C',
    'title'  => 'Contributors',
    'desc'   => 'Meet authors, editors, and collaborators.',
    'accent' => '#059669',
  ],
];

if ($ql_include_staff) {
  $items[] = [
    'href'   => $staff_url,
    'badge'  => 'A',
    'title'  => 'Staff',
    'desc'   => 'Admin dashboard for managing subjects and pages.',
    'accent' => '#0ea5e9',
  ];
}

$items[] = [
  'href'   => $igbo_calendar_url,
  'badge'  => 'I',
  'title'  => 'Igbo Calendar',
  'desc'   => 'Download the Igbo Calendar for reference.',
  'accent' => '#f59e0b',
];

?>
<div class="mk-quicklinks">

  <div class="cta-row mk-cta-row">
    <a class="mk-btn mk-btn--primary" href="<?= h($subjects_url) ?>">Explore Subjects</a>
    <a class="mk-btn" href="<?= h($platforms_url) ?>">Browse Platforms</a>
    <a class="mk-btn" href="<?= h($contributors_url) ?>">Meet Contributors</a>
    <a class="mk-btn" href="<?= h($igbo_calendar_url) ?>">Download Igbo Calendar</a>
  </div>

  <?php if ($ql_tip !== null): ?>
    <p class="muted mk-tip"><?= h($ql_tip) ?></p>
  <?php endif; ?>

  <h2 class="mk-ql-heading"><?= h($ql_title) ?></h2>

  <div class="mk-ql-grid" aria-label="<?= h($ql_title) ?>">
    <?php foreach ($items as $it): ?>
      <?php
        $cls = 'card mk-card mk-ql-card ' . $accent_class((string)$it['accent']);
      ?>
      <article class="<?= h($cls) ?>">
        <a class="stretch mk-card__link mk-ql-link" href="<?= h((string)$it['href']) ?>">
          <div class="mk-ql-top">
            <div class="logo mk-ql-badge" aria-hidden="true"><?= h((string)$it['badge']) ?></div>
            <div class="mk-ql-text">
              <h3 class="mk-ql-title"><?= h((string)$it['title']) ?></h3>
              <p class="muted mk-muted mk-ql-desc"><?= h((string)$it['desc']) ?></p>
            </div>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>

</div>
