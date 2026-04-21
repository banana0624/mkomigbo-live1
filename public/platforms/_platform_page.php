<?php
declare(strict_types=1);

/**
 * /public/platforms/_platform_page.php
 * Shared renderer for /platforms/{slug}/ pages.
 *
 * Expects $pf array:
 *  - slug (string)
 *  - pretty (string)
 *  - title (string) optional
 *  - desc (string) optional
 *  - lede (string) optional
 *  - robots (string) optional (default: "index, follow")
 *  - og_image (string) optional (site-relative recommended)
 *  - twitter_image (string) optional (site-relative recommended)
 *  - ctas (array) optional: [ ['label'=>..., 'href'=>..., 'class'=>...], ... ]
 *  - cards (array) optional: [ ['icon'=>..., 'title'=>..., 'desc'=>..., 'pills'=>[...], 'accent'=>...], ... ]
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

if (!function_exists('h')) { function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('pf__u')) { function pf__u(string $p): string { return function_exists('url_for') ? (string)url_for($p) : $p; } }

$brand = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomigbo';

$pf = (isset($pf) && is_array($pf)) ? $pf : [];
$slug   = isset($pf['slug']) && is_string($pf['slug']) ? trim($pf['slug']) : '';
$pretty = isset($pf['pretty']) && is_string($pf['pretty']) ? trim($pf['pretty']) : 'Platform';

if ($slug === '' || !preg_match('/^[a-z0-9][a-z0-9_-]{0,80}$/', $slug)) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Platform not found.";
  exit;
}

$desc = (isset($pf['desc']) && is_string($pf['desc'])) ? trim($pf['desc']) : '';
if ($desc === '') $desc = $pretty . ' is coming soon on ' . $brand . '.';

$title = (isset($pf['title']) && is_string($pf['title'])) ? trim($pf['title']) : '';
if ($title === '') $title = $pretty . ' • Platforms • ' . $brand;

$canonical = '/platforms/' . rawurlencode($slug) . '/';

/**
 * Robots:
 * - Default: indexable => do NOT emit robots meta (leave empty)
 * - Placeholder: noindex,follow (crawler can still discover links)
 * - Explicit override: $pf['robots']
 */
$robots = '';

// Placeholder switch (set in controller)
if (!empty($pf['is_placeholder'])) {
  $robots = 'noindex,follow';
}

// Explicit override wins
if (isset($pf['robots']) && is_string($pf['robots']) && trim($pf['robots']) !== '') {
  $robots = trim($pf['robots']);
}

$GLOBALS['meta_robots'] = $robots;


/**
 * Default OG/Twitter image:
 * Choose an asset you KNOW exists in your project.
 * You already reference icon-192.png in public_header.php; use that (safe).
 */
$default_og = '/igbo-calendar/icons/icon-192.png';

$og_image = (isset($pf['og_image']) && is_string($pf['og_image'])) ? trim($pf['og_image']) : '';
if ($og_image === '') $og_image = $default_og;

$tw_image = (isset($pf['twitter_image']) && is_string($pf['twitter_image'])) ? trim($pf['twitter_image']) : '';
if ($tw_image === '') $tw_image = $og_image;

$extra_css = [
  pf__u('/lib/css/public.css'),
  pf__u('/lib/css/platforms.css'),
];

/**
 * SEO bundle for public_header.php (single source of truth)
 */
$GLOBALS['seo'] = [
  'title'         => $title,
  'description'   => $desc,
  'canonical'     => $canonical,
  'og_type'       => 'website',
  'og_image'      => $og_image,
  'twitter_image' => $tw_image,
];

$GLOBALS['page_title']  = $title;
$GLOBALS['page_desc']   = $desc;
$GLOBALS['nav_active']  = 'platforms';
$GLOBALS['active_nav']  = 'platforms';
$GLOBALS['extra_css']   = $extra_css;
$GLOBALS['meta_robots'] = $robots;

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title'  => $title,
      'page_desc'   => $desc,
      'nav_active'  => 'platforms',
      'active_nav'  => 'platforms',
      'extra_css'   => $extra_css,
      'meta_robots' => $robots,
    ]);
  } catch (Throwable $e) {}
}

$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}
if (!$header_ok) {
  header('Content-Type: text/html; charset=utf-8');
  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($title) . "</title></head><body>";
}

http_response_code(200);

$platforms_url    = pf__u('/platforms/');
$home_url         = pf__u('/');
$subjects_url     = pf__u('/subjects/');
$contributors_url = pf__u('/contributors/');

$lede = (isset($pf['lede']) && is_string($pf['lede'])) ? trim($pf['lede']) : '';
if ($lede === '') $lede = 'This platform is being built: structured content, clean navigation, and verified sources.';

$ctas = (isset($pf['ctas']) && is_array($pf['ctas'])) ? $pf['ctas'] : [
  ['label'=>'Explore Subjects', 'href'=>$subjects_url, 'class'=>'mk-btn'],
  ['label'=>'Meet Contributors', 'href'=>$contributors_url, 'class'=>'mk-btn'],
  ['label'=>'Back to Platforms', 'href'=>$platforms_url, 'class'=>'mk-btn mk-btn--ghost'],
];

$cards = (isset($pf['cards']) && is_array($pf['cards'])) ? $pf['cards'] : [];

?>
<main class="container mk-page">

  <div class="mk-page-actions">
    <a class="mk-btn mk-btn--ghost" href="<?= h($platforms_url) ?>">← Back to Platforms</a>
    <a class="mk-btn mk-btn--ghost" href="<?= h($home_url) ?>">Home</a>
  </div>

  <header class="mk-hero mk-hero--compact">
    <div class="mk-hero__bar" aria-hidden="true"></div>
    <div class="mk-hero__inner">
      <h1 class="mk-hero__title"><?= h($pretty) ?></h1>
      <p class="mk-muted mk-lede"><?= h($lede) ?></p>

      <?php if (!empty($ctas)): ?>
        <div class="mk-hero__actions">
          <?php foreach ($ctas as $a): ?>
            <?php
              if (!is_array($a)) continue;
              $lbl  = isset($a['label']) && is_string($a['label']) ? $a['label'] : '';
              $href = isset($a['href'])  && is_string($a['href'])  ? $a['href']  : '';
              $cls  = isset($a['class']) && is_string($a['class']) ? $a['class'] : 'mk-btn';
              if ($lbl === '' || $href === '') continue;
            ?>
            <a class="<?= h($cls) ?>" href="<?= h($href) ?>"><?= h($lbl) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <?php if (!empty($cards)): ?>
    <section class="pf-section">
      <div class="pf-section__title"><h2>What to expect</h2></div>

      <section class="pf-grid">
        <?php foreach ($cards as $c): ?>
          <?php
            if (!is_array($c)) continue;
            $accent = isset($c['accent']) && is_string($c['accent']) ? $c['accent'] : '#2F4A5A';
            $icon   = isset($c['icon'])   && is_string($c['icon'])   ? $c['icon']   : '';
            $ct     = isset($c['title'])  && is_string($c['title'])  ? $c['title']  : '';
            $cd     = isset($c['desc'])   && is_string($c['desc'])   ? $c['desc']   : '';
            $pills  = isset($c['pills'])  && is_array($c['pills'])   ? $c['pills']  : [];
            if ($ct === '') continue;
          ?>
          <article class="pf-card" style="--pf-accent:<?= h($accent) ?>;">
            <div class="pf-card__bar"></div>
            <div class="pf-card__body">
              <div class="pf-card__top">
                <div class="pf-icon" aria-hidden="true"><?= h($icon) ?></div>
                <div class="pf-card__text">
                  <h3 class="pf-card__title"><?= h($ct) ?></h3>
                  <?php if ($cd !== ''): ?><p class="pf-card__desc mk-muted"><?= h($cd) ?></p><?php endif; ?>
                </div>
              </div>
              <?php if (!empty($pills)): ?>
                <div class="pf-card__meta">
                  <?php foreach ($pills as $p): if (!is_string($p) || trim($p)==='') continue; ?>
                    <span class="pf-pill"><?= h($p) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    </section>
  <?php endif; ?>

</main>

<?php
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_footer.php'); } catch (Throwable $e) {}
} else {
  echo "</body></html>";
}
