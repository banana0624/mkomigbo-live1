<?php
declare(strict_types=1);

/**
 * /public/platforms/_platform_template.php
 * Shared template for individual platform landing pages.
 *
 * Supports TWO calling styles:
 *
 * A) Array contract (recommended):
 *   $pf = [
 *     'title'  => 'Blog',
 *     'desc'   => 'Long-form articles...',
 *     'slug'   => 'blog',
 *     'status' => 'preview'|'live',
 *     'robots' => 'noindex, nofollow'  // optional
 *   ];
 *   $platform_body_html = '<section>...</section>'; // optional pre-rendered HTML
 *   require __DIR__ . '/../_platform_template.php';
 *
 * B) Legacy vars contract (accepted):
 *   $platform_label, $platform_desc, $platform_key (or slug)
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
   Normalize input (pf array + legacy vars)
--------------------------------------------------------- */
$pf = (isset($pf) && is_array($pf)) ? $pf : [];

$rawTitle =
  (isset($pf['title']) && is_string($pf['title']) && trim($pf['title']) !== '') ? trim($pf['title'])
  : ((isset($platform_label) && is_string($platform_label) && trim($platform_label) !== '') ? trim($platform_label) : 'Platform');

$rawDesc =
  (isset($pf['desc']) && is_string($pf['desc']) && trim($pf['desc']) !== '') ? trim($pf['desc'])
  : ((isset($platform_desc) && is_string($platform_desc) && trim($platform_desc) !== '') ? trim($platform_desc) : 'This platform is being prepared.');

$rawSlug =
  (isset($pf['slug']) && is_string($pf['slug'])) ? $pf['slug']
  : ((isset($platform_key) && is_string($platform_key)) ? $platform_key : '');

$slug = strtolower(trim((string)$rawSlug));
$slug = preg_replace('/[^a-z0-9\-]/', '', $slug) ?? '';
$slug = (string)$slug;

$status = (isset($pf['status']) && is_string($pf['status'])) ? strtolower(trim($pf['status'])) : 'preview';
$status = ($status === 'live') ? 'live' : 'preview';

$robots = (isset($pf['robots']) && is_string($pf['robots']) && trim($pf['robots']) !== '')
  ? trim($pf['robots'])
  : 'noindex, nofollow';

/* ---------------------------------------------------------
   Globals for header (IMPORTANT: mk_require_shared() is a function)
   => locals won’t cross scope; globals will.
--------------------------------------------------------- */
$canonicalPath = ($slug !== '') ? ('/platforms/' . $slug . '/') : '/platforms/';

$extra_css = [
  pf__u('/lib/css/public.css'),
  pf__u('/lib/css/platforms.css'),
];

$extra_head = '<meta name="robots" content="' . h($robots) . '">';

$GLOBALS['nav_active'] = 'platforms';
$GLOBALS['extra_css']  = $extra_css;
$GLOBALS['extra_head'] = $extra_head;

/* SEO: this is what your public_header.php actually consumes reliably */
$GLOBALS['seo'] = array_merge(
  (isset($GLOBALS['seo']) && is_array($GLOBALS['seo'])) ? $GLOBALS['seo'] : [],
  [
    'title'       => $rawTitle . ' — Platforms',
    'description' => $rawDesc,
    'canonical'   => $canonicalPath,
    'og_type'     => 'website',
  ]
);

/* Optional view layer */
if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'nav_active' => 'platforms',
      'extra_css'  => $extra_css,
      'extra_head' => $extra_head,
      'seo'        => $GLOBALS['seo'],
    ]);
  } catch (Throwable $e) {}
}

/* ---------------------------------------------------------
   Header
--------------------------------------------------------- */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}
if (!$header_ok) {
  if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html lang='en'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($rawTitle) . " — Platforms</title></head><body>";
}

/* ---------------------------------------------------------
   Page shell
--------------------------------------------------------- */
$platforms_url = pf__u('/platforms/');
?>
<main class="container mk-page">

  <nav class="mk-crumbs" aria-label="Breadcrumb">
    <a href="<?= h(pf__u('/')) ?>">Home</a>
    <span class="mk-crumbs__sep">›</span>
    <a href="<?= h($platforms_url) ?>">Platforms</a>
    <span class="mk-crumbs__sep">›</span>
    <span class="mk-crumbs__current"><?= h($rawTitle) ?></span>
  </nav>

  <header class="mk-hero mk-hero--compact">
    <div class="mk-hero__bar" aria-hidden="true"></div>
    <div class="mk-hero__inner">
      <div class="mk-page-actions">
        <a class="mk-btn mk-btn--ghost" href="<?= h($platforms_url) ?>">← Back to Platforms</a>
      </div>

      <h1 class="mk-hero__title"><?= h($rawTitle) ?></h1>
      <p class="mk-hero__subtitle"><?= h($rawDesc) ?></p>

      <div class="pf-card__meta">
        <?php if ($status === 'live'): ?>
          <span class="pf-pill">Live</span>
          <span class="pf-pill">Available</span>
        <?php else: ?>
          <span class="pf-pill">Preview</span>
          <span class="pf-pill">Coming soon</span>
        <?php endif; ?>
      </div>

      <?php if ($slug !== ''): ?>
        <p class="mk-muted">Slug: <code><?= h($slug) ?></code></p>
      <?php endif; ?>
    </div>
  </header>

  <?php
  /* Optional controller-provided body */
  if (isset($platform_body_html) && is_string($platform_body_html) && trim($platform_body_html) !== '') {
    echo $platform_body_html;
  } else {
  ?>
    <section class="mk-card" style="margin-top:14px;">
      <div class="mk-card__body mk-prose">
        <p>This platform page is active. Replace this content with the real module UI when you’re ready.</p>
      </div>
    </section>
  <?php } ?>

</main>

<?php
/* ---------------------------------------------------------
   Footer
--------------------------------------------------------- */
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_footer.php'); } catch (Throwable $e) {}
} else {
  echo "</body></html>";
}
