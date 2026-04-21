<?php
declare(strict_types=1);

/**
 * /public/subjects/_scripts/scripts.php
 * Subjects: Scripts page (public)
 *
 * Best-practice:
 * - Use only shared public_header.php / public_footer.php
 * - Never use subjects_header.php (legacy, causes conflicts)
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../../_init.php';

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$page_title = 'Scripts — Subjects';
$page_desc  = 'Writing systems and symbols relevant to Igbo knowledge: Nsibidi, Ndebe, and related concepts.';
$nav_active = 'subjects';

$GLOBALS['seo'] = array_merge((isset($GLOBALS['seo']) && is_array($GLOBALS['seo'])) ? $GLOBALS['seo'] : [], [
  'title'       => $page_title,
  'description' => $page_desc,
]);

$header_ok = false;
try {
  if (function_exists('mk_require_shared')) { mk_require_shared('public_header.php'); $header_ok = true; }
} catch (Throwable $e) {}

if (!$header_ok) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Header include failed.\n";
  exit;
}
?>

<main class="container mk-page">
  <nav class="mk-crumbs" aria-label="Breadcrumb">
    <a href="/">Home</a>
    <span class="mk-crumbs__sep">›</span>
    <a href="/subjects/">Subjects</a>
    <span class="mk-crumbs__sep">›</span>
    <span class="mk-crumbs__current">Scripts</span>
  </nav>

  <header class="mk-hero mk-hero--compact">
    <div class="mk-hero__bar" aria-hidden="true"></div>
    <div class="mk-hero__inner">
      <h1 class="mk-hero__title">Scripts</h1>
      <p class="mk-hero__subtitle"><?= h($page_desc) ?></p>
    </div>
  </header>

  <section class="mk-card" style="margin-top:14px;">
    <div class="mk-card__body mk-prose">
      <p>
        This section documents writing systems and symbolic communication relevant to Igbo knowledge.
        You can expand this into structured pages (concepts, sources, timelines) as your module grows.
      </p>

      <ul>
        <li><strong>Nsibidi</strong> — symbolic system used historically across parts of the region.</li>
        <li><strong>Ndebe</strong> — modern script proposals and usage notes.</li>
      </ul>
    </div>
  </section>
</main>

<?php
try {
  if (function_exists('mk_require_shared')) mk_require_shared('public_footer.php');
} catch (Throwable $e) {}
