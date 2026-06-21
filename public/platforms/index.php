<?php
declare(strict_types=1);

/**
 * /public/platforms/index.php
 * Public Platforms landing (premium + robust).
 *
 * Behavior:
 * - Available now: only platforms that physically exist AND have /index.php (clickable)
 * - Coming soon: shows the same platforms again as disabled preview (grey/not clickable)
 *
 * Notes:
 * - Zero DB
 * - Uses shared header/footer via mk_require_shared()
 * - Loads /lib/css/public.css + /lib/css/platforms.css through extra_css
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/_init.php';

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('pf__u')) {
  function pf__u(string $path): string {
    return function_exists('url_for') ? (string)url_for($path) : $path;
  }
}

/* ---------------------------------------------------------
 * Locate PUBLIC root safely
 * --------------------------------------------------------- */
$public_root = defined('PUBLIC_PATH')
  ? rtrim((string)PUBLIC_PATH, '/')
  : rtrim(dirname(__DIR__), '/'); // fallback: /public_html/public

$platforms_dir = $public_root . '/platforms';

/* ---------------------------------------------------------
 * Known platform metadata (Option 1 slugs)
 * --------------------------------------------------------- */
$meta = [
  'blog' => [
    'title' => 'Blog',
    'desc'  => 'Long-form articles, editorials, and featured writings across subjects.',
    'icon'  => 'B',
  ],
  'communities' => [
    'title' => 'Communities',
    'desc'  => 'Interest-based spaces for readers and contributors to gather and collaborate.',
    'icon'  => 'C',
  ],
  'forum' => [
    'title' => 'Forum',
    'desc'  => 'Community discussions, Q&A, and collaborative learning.',
    'icon'  => 'F',
  ],
  'gallery' => [
    'title' => 'Gallery',
    'desc'  => 'Curated photos, artefacts, maps, and historical visuals.',
    'icon'  => 'G',
  ],
  'knowledge-index' => [
    'title' => 'Knowledge Index',
    'desc'  => 'Cross-subject discovery by tags, themes, timelines, and people.',
    'icon'  => 'KI',
  ],
  'media-library' => [
    'title' => 'Media Library',
    'desc'  => 'Curated videos, reels, photos, and documentary references.',
    'icon'  => 'M',
  ],
  'podcast' => [
    'title' => 'Podcast',
    'desc'  => 'Audio episodes: conversations, history, interviews, and cultural insights.',
    'icon'  => 'PD',
  ],
  'posts' => [
    'title' => 'Posts',
    'desc'  => 'Short-form updates and public notes connected to Subjects and pages.',
    'icon'  => 'P',
  ],
  'reel' => [
    'title' => 'Reel',
    'desc'  => 'Quick video moments, highlights, and cultural snippets.',
    'icon'  => 'R',
  ],
  'threads' => [
    'title' => 'Threads',
    'desc'  => 'Structured discussions designed for deep, topic-focused conversations.',
    'icon'  => 'T',
  ],
  'vlog' => [
    'title' => 'Vlog',
    'desc'  => 'Video stories, short explainers, interviews, and documentary-style content.',
    'icon'  => 'V',
  ],
];

/* Utility: humanize slug */
$humanize = static function(string $slug): string {
  $s = str_replace(['_', '-'], ' ', $slug);
  $s = preg_replace('/\s+/', ' ', $s);
  $s = trim((string)$s);
  return $s !== '' ? ucwords($s) : 'Platform';
};

/* Normalize key */
$norm_key = static function(string $name): string {
  $key = strtolower((string)$name);
  $key = preg_replace('/[^a-z0-9\-_]/', '', $key);
  $key = str_replace('_', '-', $key);
  return $key;
};

/* ---------------------------------------------------------
 * Build platforms map:
 * - Always include meta placeholders (planned)
 * - Mark "available" if folder exists and has index.php
 * --------------------------------------------------------- */
$platforms_map = [];

/* 1) Add meta placeholders first (so "Coming soon" always has content) */
foreach ($meta as $key => $m) {
  $title = $m['title'] ?? $humanize($key);
  $platforms_map[$key] = [
    'key'      => $key,
    'title'    => $title,
    'desc'     => $m['desc'] ?? 'This platform is being prepared.',
    'href'     => pf__u('/platforms/' . $key . '/'),
    'icon'     => $m['icon'] ?? strtoupper(substr($title, 0, 1)),
    'hasDir'   => false,
    'hasIndex' => false,
  ];
}

/* 2) Overlay discovered folders (may add unknown platforms too) */
$found = [];
if (is_dir($platforms_dir) && is_readable($platforms_dir)) {
  $items = @scandir($platforms_dir);
  if (is_array($items)) {
    foreach ($items as $name) {
      if ($name === '.' || $name === '..') continue;
      if ($name !== '' && $name[0] === '.') continue;

      // ignore known non-platform files
      if ($name === '_init.php' || $name === 'index.php' || $name === 'view.php') continue;
      if (substr($name, -4) === '.php') continue;

      $abs = $platforms_dir . '/' . $name;
      if (!is_dir($abs)) continue;

      $key = $norm_key($name);
      if ($key === '') continue;

      $found[$key] = $abs;
    }
  }
}

foreach ($found as $key => $absdir) {
  $index_file = rtrim($absdir, '/') . '/index.php';
  $has_index  = is_file($index_file);

  $m = $meta[$key] ?? [];
  $title = $m['title'] ?? $humanize($key);
  $desc  = $m['desc']  ?? 'This platform is being prepared.';
  $icon  = $m['icon']  ?? strtoupper(substr($title, 0, 1));

  $platforms_map[$key] = [
    'key'      => $key,
    'title'    => $title,
    'desc'     => $desc,
    'href'     => pf__u('/platforms/' . $key . '/'),
    'icon'     => $icon,
    'hasDir'   => true,
    'hasIndex' => $has_index,
  ];
}

/* Convert to list */
$platforms = array_values($platforms_map);

/* Stable ordering (meta keys first, then alphabetical) */
$known_order = array_keys($meta);
usort($platforms, static function(array $a, array $b) use ($known_order): int {
  $ai = array_search($a['key'], $known_order, true);
  $bi = array_search($b['key'], $known_order, true);
  $ai = ($ai === false) ? 9999 : (int)$ai;
  $bi = ($bi === false) ? 9999 : (int)$bi;
  if ($ai !== $bi) return $ai <=> $bi;
  return strcmp((string)$a['key'], (string)$b['key']);
});

/* Available now: only those with physical index.php */
$available = array_values(array_filter($platforms, static fn($p) => !empty($p['hasIndex'])));

/* Coming soon: show the same list again (disabled preview) */
$coming = array_values(array_filter($platforms, static fn($p) => empty($p['hasIndex'])));

/* ---------------------------------------------------------
 * Layout contract
 * --------------------------------------------------------- */
$page_title = 'Platforms — Mkomi Igbo';
$page_desc  = 'Platforms are interactive sections — tools, indexes, and features that complement the Subjects library.';
$extra_css  = [ pf__u('/lib/css/public.css'), pf__u('/lib/css/platforms.css') ];

$GLOBALS['page_title']  = $page_title;
$GLOBALS['page_desc']   = $page_desc;
$GLOBALS['nav_active']  = 'platforms';
$GLOBALS['active_nav']  = 'platforms';
$GLOBALS['extra_css']   = $extra_css;

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => 'platforms',
      'active_nav' => 'platforms',
      'extra_css'  => $extra_css,
    ]);
  } catch (Throwable $e) {}
}

/* Header */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}
if (!$header_ok) {
  if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html lang='en'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($page_title) . "</title><link rel='stylesheet' href='/assets/css/ui.css'><link rel='stylesheet' href='/assets/css/public.css'><link rel='stylesheet' href='/assets/css/platforms.css'></head><body>";
  echo '<header style="background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:20;"><div style="max-width:1200px;margin:0 auto;padding:0 16px;display:flex;align-items:center;justify-content:space-between;min-height:60px;gap:14px;flex-wrap:wrap;"><a href="/" style="display:inline-flex;align-items:center;gap:9px;text-decoration:none;color:inherit;"><img src="/assets/images/logos/mk-logo.png" width="30" height="30" style="border-radius:8px;" alt="Mkomigbo"><strong style="font-size:1rem;">Mkomigbo</strong></a><nav style="display:flex;gap:4px;flex-wrap:wrap;"><a href="/subjects/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Subjects</a><a href="/platforms/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:800;font-size:.9rem;color:#0d6efd;border:1px solid rgba(13,110,253,.30);background:rgba(13,110,253,.08);">Platforms</a><a href="/contributors/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Contributors</a><a href="/awag/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">AWAG</a><a href="/igbo-calendar/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Calendar</a></nav></div></header>';
}

?>
<div class="container mk-page">

  <header class="pf-hero">
    <div class="pf-hero__bar" aria-hidden="true"></div>
    <div class="pf-hero__inner">
      <h1 class="pf-hero__title">Platforms</h1>
      <p class="pf-hero__desc">
        Platforms are interactive sections of Mkomi Igbo — tools, indexes, and features that complement the Subjects library.
        This page shows what is available now and what is planned next.
      </p>

      <?php
        // Quick links (clean mode: no inline <style>; styling is in public.css)
        $ql_title = 'Quick links';
        $ql_tip = null;
        $ql_include_staff = false;

        if (defined('PRIVATE_PATH')) {
          $ql = rtrim((string)PRIVATE_PATH, '/') . '/shared/quick_links.php';
          if (is_file($ql)) { require $ql; }
        }
      ?>
    </div>
  </header>

  <!-- Available now -->
  <div class="pf-section">
    <div class="pf-section__title">
      <h2>Available now</h2>
    </div>

    <?php if (count($available) === 0): ?>
      <p class="mk-muted">No platforms are enabled yet.</p>
    <?php else: ?>
      <section class="pf-grid" aria-label="Available platforms">
        <?php foreach ($available as $p): ?>
          <?php
            $k = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)$p['key']));
            $card_class = 'pf-card pf-card--' . $k;
          ?>
          <article class="<?= h($card_class) ?>" data-platform="<?= h($k) ?>">
            <div class="pf-card__bar" aria-hidden="true"></div>
            <a class="pf-card__link" href="<?= h((string)$p['href']) ?>">
              <div class="pf-card__body">
                <div class="pf-card__top">
                  <div class="pf-icon" aria-hidden="true"><?= h((string)$p['icon']) ?></div>
                  <div class="pf-card__text">
                    <h3 class="pf-card__title"><?= h((string)$p['title']) ?></h3>
                    <p class="pf-card__desc"><?= h((string)$p['desc']) ?></p>
                  </div>
                </div>
                <div class="pf-card__meta">
                  <span class="pf-pill">Available</span>
                  <span class="pf-pill">Open</span>
                </div>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </div>

  <!-- Coming soon -->
  <div class="pf-section">
    <div class="pf-section__title">
      <h2>Coming soon</h2>
    </div>

    <?php if (count($coming) === 0): ?>
      <p class="mk-muted">Nothing planned yet.</p>
    <?php else: ?>
      <section class="pf-grid" aria-label="Planned platforms">
        <?php foreach ($coming as $p): ?>
          <?php
            $k = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)$p['key']));
            $card_class = 'pf-card pf-card--' . $k;
          ?>
          <article class="<?= h($card_class) ?>" data-platform="<?= h($k) ?>">
            <div class="pf-card__bar" aria-hidden="true"></div>

            <!-- Coming soon: NOT clickable -->
            <div class="pf-card__link is-disabled" aria-disabled="true" role="link" tabindex="-1">
              <div class="pf-card__body">
                <div class="pf-card__top">
                  <div class="pf-icon" aria-hidden="true"><?= h((string)$p['icon']) ?></div>
                  <div class="pf-card__text">
                    <h3 class="pf-card__title"><?= h((string)$p['title']) ?></h3>
                    <p class="pf-card__desc"><?= h((string)$p['desc']) ?></p>
                  </div>
                </div>
                <div class="pf-card__meta">
                  <span class="pf-pill">Coming soon</span>
                  <span class="pf-pill">Preview</span>
                </div>
              </div>
            </div>

          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </div>

</div>

<?php
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_footer.php'); } catch (Throwable $e) {}
} else {
  echo "</body></html>";
}
