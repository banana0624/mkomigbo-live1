<?php
declare(strict_types=1);

/**
 * Hardened Subject Page Controller
 * Production-safe + contribution-aware
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* ---------------- SAFE HELPERS ---------------- */

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('mk_u')) {
  function mk_u(string $path): string {
    return function_exists('url_for') ? (string)url_for($path) : $path;
  }
}

if (!function_exists('mk_safe_redirect')) {
  function mk_safe_redirect(string $url): void {
    header('Location: ' . str_replace(["\r","\n"], '', $url));
    exit;
  }
}

/* ---------------- INPUT ---------------- */

$subject_slug = strtolower(trim($_GET['subject'] ?? ''));
$page_slug    = strtolower(trim($_GET['slug'] ?? ($_GET['page'] ?? '')));

if ($subject_slug === '' || $page_slug === '') {
  http_response_code(404);
  exit('Invalid request');
}

/* ---------------- BASIC PAGE ---------------- */

$subject_title = ucfirst(str_replace(['-','_'], ' ', $subject_slug));
$page_title_txt = ucfirst(str_replace(['-','_'], ' ', $page_slug));

/* ---------------- LOAD CONTENT ---------------- */

$body_html = '';
$page_file = __DIR__ . "/pages/{$subject_slug}/{$page_slug}.php";

if (is_file($page_file)) {
  ob_start();
  try {
    require $page_file;
  } catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    exit('Page error');
  }
  $body_html = ob_get_clean();
}

/* ---------------- GLOBAL SUCCESS FLAG ---------------- */

$show_success = (
  isset($_GET['contrib']) &&
  $_GET['contrib'] === 'success'
);

/* ---------------- HEADER ---------------- */

if (function_exists('mk_require_shared')) {
  mk_require_shared('public_header.php');
} else {
  echo "<!doctype html><html><head><meta charset='utf-8'><title>".h($page_title_txt)."</title></head><body>";
}
?>

<main class="container mk-page">

  <?php if ($show_success): ?>
    <div class="mk-card" style="margin-bottom:12px;border-left:4px solid #16a34a;">
      <div class="mk-card__body">
        <strong>Success.</strong> Your contribution has been submitted and is awaiting review.
      </div>
    </div>
  <?php endif; ?>

  <header class="mk-article-hero mk-article-hero--compact">
    <h1><?= h($page_title_txt) ?></h1>
  </header>

  <section class="mk-article-shell">

    <article class="mk-article">
      <div class="mk-card">
        <div class="mk-card__body">

          <?php if (trim($body_html) === ''): ?>
            <p class="mk-muted">This page has no content yet.</p>
          <?php else: ?>
            <div class="mk-prose">
              <?= $body_html ?>
            </div>
          <?php endif; ?>

        </div>
      </div>

      <?php
      /* ---------------- GLOBAL CONTRIBUTION WIDGET ---------------- */
      try {
        $contrib = __DIR__ . '/_partials/contribute.php';
        if (is_file($contrib)) {
          require $contrib;
        }
      } catch (Throwable $e) {
        // never break page
      }
      ?>

    </article>

  </section>

</main>

<?php
if (function_exists('mk_require_shared')) {
  mk_require_shared('public_footer.php');
} else {
  echo "</body></html>";
}