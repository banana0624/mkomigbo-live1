<?php
declare(strict_types=1);

/**
 * /public/platforms/_platform_template.php
 * Shared template for individual platform landing pages.
 *
 * Usage inside /public/platforms/{slug}/index.php:
 *   $pf = [
 *     'title' => 'Blog',
 *     'desc'  => 'Long-form articles...',
 *     'slug'  => 'blog',
 *     'status'=> 'preview'|'live'
 *   ];
 *   require __DIR__ . '/../_platform_template.php';
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

$pf = (isset($pf) && is_array($pf)) ? $pf : [];

$title  = isset($pf['title']) && is_string($pf['title']) && trim($pf['title']) !== '' ? trim($pf['title']) : 'Platform';
$desc   = isset($pf['desc']) && is_string($pf['desc']) && trim($pf['desc']) !== '' ? trim($pf['desc']) : 'This platform is being prepared.';
$slug   = isset($pf['slug']) && is_string($pf['slug']) ? preg_replace('/[^a-z0-9\-]/', '', strtolower($pf['slug'])) : '';
$status = isset($pf['status']) && is_string($pf['status']) ? strtolower(trim($pf['status'])) : 'preview';
if ($status !== 'live') { $status = 'preview'; }

$page_title = $title . ' — Platforms — Mkomi Igbo';
$page_desc  = $desc;

$extra_css = [ pf__u('/lib/css/public.css'), pf__u('/lib/css/platforms.css') ];

$GLOBALS['page_title'] = $page_title;
$GLOBALS['page_desc']  = $page_desc;
$GLOBALS['nav_active'] = 'platforms';
$GLOBALS['extra_css']  = $extra_css;

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => 'platforms',
      'extra_css'  => $extra_css,
    ]);
  } catch (Throwable $e) {}
}

$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}
if (!$header_ok) {
  if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html lang='en'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($page_title) . "</title></head><body>";
}

?>
<div class="container mk-page">

  <header class="pf-hero">
    <div class="pf-hero__bar" aria-hidden="true"></div>
    <div class="pf-hero__inner">
      <p class="mk-muted" style="margin:0 0 8px 0;">
        <a href="<?= h(pf__u('/platforms/')) ?>">← Back to Platforms</a>
      </p>

      <h1 class="pf-hero__title"><?= h($title) ?></h1>
      <p class="pf-hero__desc"><?= h($desc) ?></p>

      <?php if ($status !== 'live'): ?>
        <div class="pf-card__meta" style="margin-top:12px;">
          <span class="pf-pill">Preview</span>
          <span class="pf-pill">Coming soon</span>
        </div>
      <?php else: ?>
        <div class="pf-card__meta" style="margin-top:12px;">
          <span class="pf-pill">Live</span>
          <span class="pf-pill">Available</span>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <div class="pf-section">
    <div class="pf-section__title">
      <h2>Overview</h2>
    </div>

    <div class="mk-prose">
      <p>This platform page is active. Replace this content with the real module UI when you’re ready.</p>
      <?php if ($slug !== ''): ?>
        <p class="mk-muted">Slug: <code><?= h($slug) ?></code></p>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_footer.php'); } catch (Throwable $e) {}
} else {
  echo "</body></html>";
}
