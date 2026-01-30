<?php
declare(strict_types=1);

/**
 * /public/platforms/media_library/index.php
 * Platform page (standard placeholder) — must never 500.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

if (!function_exists('h')) { function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('pf__u')) { function pf__u(string $p): string { return function_exists('url_for') ? (string)url_for($p) : $p; } }

$pretty     = 'Media Library';
$page_title = $pretty . ' — Platforms — Mkomi Igbo';
$page_desc  = 'Media Library is coming soon: curated videos, audio, images, and documentary references.';

$extra_css = [ pf__u('/lib/css/public.css'), pf__u('/lib/css/platforms.css') ];

$GLOBALS['page_title']=$page_title; $GLOBALS['page_desc']=$page_desc;
$GLOBALS['nav_active']='platforms'; $GLOBALS['active_nav']='platforms'; $GLOBALS['extra_css']=$extra_css;

if (function_exists('mk_view_set')) {
  try { mk_view_set([
    'page_title'=>$page_title,'page_desc'=>$page_desc,
    'nav_active'=>'platforms','active_nav'=>'platforms',
    'extra_css'=>$extra_css,'meta_robots'=>'noindex, nofollow',
  ]); } catch (Throwable $e) {}
}

$header_ok=false;
if (function_exists('mk_require_shared')) { try { mk_require_shared('public_header.php'); $header_ok=true; } catch (Throwable $e) {} }
if (!$header_ok) { header('Content-Type:text/html; charset=UTF-8'); echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>".h($page_title)."</title></head><body>"; }

http_response_code(200);

$platforms_url = pf__u('/platforms/');
$home_url = pf__u('/');
$subjects_url = pf__u('/subjects/');
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
      <p class="mk-muted mk-lede">
        A curated library of documentaries, audio, images, reels, and referenced materials aligned to Subjects.
      </p>
      <div class="mk-hero__actions">
        <a class="mk-btn" href="<?= h($subjects_url) ?>">Explore Subjects</a>
        <a class="mk-btn mk-btn--ghost" href="<?= h($platforms_url) ?>">Back to Platforms</a>
      </div>
    </div>
  </header>

  <section class="pf-section">
    <div class="pf-section__title"><h2>What to expect</h2></div>

    <section class="pf-grid">
      <article class="pf-card" style="--pf-accent:#59312F;">
        <div class="pf-card__bar"></div>
        <div class="pf-card__body">
          <div class="pf-card__top">
            <div class="pf-icon" aria-hidden="true">ML</div>
            <div class="pf-card__text">
              <h3 class="pf-card__title">Curated media</h3>
              <p class="pf-card__desc mk-muted">Videos, audio, images, and structured references.</p>
            </div>
          </div>
          <div class="pf-card__meta"><span class="pf-pill">Planned</span><span class="pf-pill">Curated</span></div>
        </div>
      </article>

      <article class="pf-card" style="--pf-accent:#2F4A43;">
        <div class="pf-card__bar"></div>
        <div class="pf-card__body">
          <div class="pf-card__top">
            <div class="pf-icon" aria-hidden="true">L</div>
            <div class="pf-card__text">
              <h3 class="pf-card__title">Linked to Subjects</h3>
              <p class="pf-card__desc mk-muted">Each entry can connect to Subjects and Pages.</p>
            </div>
          </div>
          <div class="pf-card__meta"><span class="pf-pill">Planned</span><span class="pf-pill">Structured</span></div>
        </div>
      </article>

      <article class="pf-card" style="--pf-accent:#2F3A4A;">
        <div class="pf-card__bar"></div>
        <div class="pf-card__body">
          <div class="pf-card__top">
            <div class="pf-icon" aria-hidden="true">SRC</div>
            <div class="pf-card__text">
              <h3 class="pf-card__title">Provenance</h3>
              <p class="pf-card__desc mk-muted">Source notes, credits, and citations per item.</p>
            </div>
          </div>
          <div class="pf-card__meta"><span class="pf-pill">Planned</span><span class="pf-pill">Sources</span></div>
        </div>
      </article>
    </section>
  </section>

</main>
<?php
if (function_exists('mk_require_shared')) { try { mk_require_shared('public_footer.php'); } catch (Throwable $e) {} }
else { echo "</body></html>"; }
