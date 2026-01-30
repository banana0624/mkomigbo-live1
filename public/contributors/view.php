<?php
declare(strict_types=1);

/**
 * /public/contributors/view.php
 * Public contributor profile (premium, schema-tolerant, canonical).
 *
 * Attachments:
 * - Unified engine (local /contributors-media/{slug}/ + optional contributor_files + allowlisted remote URLs)
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

// Optional module shim (safe)
$__mod_init = __DIR__ . '/_init.php';
if (is_file($__mod_init)) { require_once $__mod_init; }

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('pf__u')) {
  function pf__u(string $path): string { return function_exists('url_for') ? (string)url_for($path) : $path; }
}
if (!function_exists('pf__redirect_301')) {
  function pf__redirect_301(string $to): void {
    if ($to === '') return;
    $to = str_replace(["\r","\n"], '', $to);
    header('Location: ' . $to, true, 301);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Moved: " . $to;
    exit;
  }
}

/* Schema helpers */
if (!function_exists('pf__table_exists')) {
  function pf__table_exists(PDO $pdo, string $table): bool {
    try {
      $st = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1");
      $st->execute([$table]);
      return (bool)$st->fetchColumn();
    } catch (Throwable $e) { return false; }
  }
}
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];
    try {
      $st = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
      $st->execute([$table, $column]);
      $cache[$key] = (bool)$st->fetchColumn();
      return (bool)$cache[$key];
    } catch (Throwable $e) {
      $cache[$key] = false;
      return false;
    }
  }
}

/* Inputs */
$raw_slug = isset($_GET['slug']) && is_scalar($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$raw_id   = isset($_GET['id'])   && is_scalar($_GET['id'])   ? trim((string)$_GET['id'])   : '';

$slug = strtolower($raw_slug);
$id   = (int)$raw_id;

if ($slug !== '' && !preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $slug)) $slug = '';
if ($id < 0) $id = 0;

/* Canonical redirect (only for controller hits) */
$request_uri = (string)($_SERVER['REQUEST_URI'] ?? '');
$path_only   = strtolower((string)(parse_url($request_uri, PHP_URL_PATH) ?: ''));
if (strpos($path_only, '/contributors/view.php') !== false) {
  if ($slug !== '') pf__redirect_301(pf__u('/contributors/' . rawurlencode($slug) . '/'));
  if ($id > 0)      pf__redirect_301(pf__u('/contributors/id/' . rawurlencode((string)$id) . '/'));
}

/* Attachments engine */
$attachments_engine = null;
$attachments_items  = [];
$engine_file = (defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, "/\\") : '') . '/functions/attachments_engine.php';
if ($engine_file !== '' && is_file($engine_file)) {
  require_once $engine_file;
  if (function_exists('mk_attachments_engine')) $attachments_engine = mk_attachments_engine();
}

/* DB fetch (schema-tolerant) */
$contrib = null;
$db_note = '';

try {
  $pdo = function_exists('db') ? db() : null;
  if (!$pdo instanceof PDO) throw new RuntimeException('DB not available.');

  if (!pf__table_exists($pdo, 'contributors')) {
    $contrib = null;
  } else {
    $has_slug   = pf__column_exists($pdo, 'contributors', 'slug');
    $has_status = pf__column_exists($pdo, 'contributors', 'status');
    $has_public = pf__column_exists($pdo, 'contributors', 'is_public');

    $nameCol = null;
    foreach (['display_name','name','username','email'] as $c) {
      if (pf__column_exists($pdo, 'contributors', $c)) { $nameCol = $c; break; }
    }

    $bioCol = null;
    foreach (['bio_html','bio','about','description','content'] as $c) {
      if (pf__column_exists($pdo, 'contributors', $c)) { $bioCol = $c; break; }
    }

    $avatarCol = null;
    foreach (['avatar_url','avatar','photo','image_url'] as $c) {
      if (pf__column_exists($pdo, 'contributors', $c)) { $avatarCol = $c; break; }
    }

    $cols = [];
    $cols[] = "id";
    $cols[] = $has_slug ? "slug" : "NULL AS slug";
    $cols[] = $nameCol ? ("`{$nameCol}` AS display_name") : "CAST(id AS CHAR) AS display_name";
    $cols[] = $bioCol ? ("`{$bioCol}` AS bio") : "NULL AS bio";
    $cols[] = $avatarCol ? ("`{$avatarCol}` AS avatar") : "NULL AS avatar";

    $where  = [];
    $params = [];

    if ($slug !== '' && $has_slug) {
      $where[] = "slug = :slug";
      $params[':slug'] = $slug;
    } elseif ($id > 0) {
      $where[] = "id = :id";
      $params[':id'] = $id;
    } else {
      $where[] = "1=0";
    }

    if ($has_status)     $where[] = "status = 'active'";
    elseif ($has_public) $where[] = "is_public = 1";

    $sql = "SELECT " . implode(", ", $cols) . " FROM contributors WHERE " . implode(" AND ", $where) . " LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $contrib = $st->fetch(PDO::FETCH_ASSOC) ?: null;
  }

} catch (Throwable $e) {
  $db_note = $e->getMessage();
  $contrib = null;
}

/* Page vars */
$nav_active = 'contributors';
$active_nav = 'contributors';

$display = $contrib ? trim((string)($contrib['display_name'] ?? '')) : '';
if ($display === '') $display = ($slug !== '') ? $slug : (($id > 0) ? ('Contributor #' . $id) : 'Contributor');

$page_title = $contrib ? ($display . ' — Contributors — Mkomi Igbo') : 'Contributor not found — Mkomi Igbo';
$page_desc  = $contrib ? ('Profile of ' . $display . ' on Mkomi Igbo.') : 'The requested contributor profile could not be found.';

$extra_css = [ pf__u('/lib/css/public.css'), pf__u('/lib/css/article.css'), pf__u('/lib/css/contributors.css') ];

/* Cache (GET only) */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && function_exists('mk_public_cache_headers')) {
  mk_public_cache_headers(300);
}

/* Layout contract */
$GLOBALS['page_title'] = $page_title;
$GLOBALS['page_desc']  = $page_desc;
$GLOBALS['nav_active'] = $nav_active;
$GLOBALS['active_nav'] = $active_nav;
$GLOBALS['extra_css']  = $extra_css;

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => $nav_active,
      'active_nav' => $active_nav,
      'extra_css'  => $extra_css,
    ]);
  } catch (Throwable $e) { /* ignore */ }
}

/* Header */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}
if (!$header_ok) {
  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>" . h($page_title) . "</title></head><body>";
}

if (!$contrib) http_response_code(404);

$contributors_url = pf__u('/contributors/');
$home_url         = pf__u('/');
$subjects_url      = pf__u('/subjects/');
$platforms_url     = pf__u('/platforms/');

?>
<main class="container mk-page">

  <div class="mk-page-actions">
    <a class="mk-btn mk-btn--ghost" href="<?= h($contributors_url); ?>">← Back to Contributors</a>
    <a class="mk-btn mk-btn--ghost" href="<?= h($home_url); ?>">Home</a>
  </div>

  <?php if ($db_note !== ''): ?>
    <div class="mk-alert mk-alert--danger">
      <strong>DB Error:</strong> <?= h($db_note); ?>
    </div>
  <?php endif; ?>

  <?php if (!$contrib): ?>

    <header class="mk-hero mk-hero--compact">
      <div class="mk-hero__bar" aria-hidden="true"></div>
      <div class="mk-hero__inner">
        <h1 class="mk-hero__title">Contributor not found</h1>
        <p class="mk-muted mk-lede">
          No public contributor matched your request.
        </p>
        <div class="mk-hero__actions">
          <a class="mk-btn" href="<?= h($contributors_url); ?>">← Back to Contributors</a>
          <a class="mk-btn mk-btn--ghost" href="<?= h($subjects_url); ?>">Explore Subjects</a>
        </div>
      </div>
    </header>

  <?php else: ?>
    <?php
      $bio      = trim((string)($contrib['bio'] ?? ''));
      $avatar   = trim((string)($contrib['avatar'] ?? ''));
      $slug_out = trim((string)($contrib['slug'] ?? ''));
      $id_out   = (int)($contrib['id'] ?? 0);

      // Avatar safety: allow only http(s) or site-absolute paths
      if ($avatar !== '') {
        $ok = false;
        if (preg_match('~^https?://~i', $avatar)) $ok = true;
        if (strpos($avatar, '/') === 0) $ok = true;
        if (!$ok) $avatar = '';
      }

      // Bio sanitization (best-effort)
      if ($bio !== '') {
        if (function_exists('mk_sanitize_allowlist_html')) {
          try { $bio = (string)mk_sanitize_allowlist_html($bio); } catch (Throwable $e) {}
        } else {
          $tmp = preg_replace('~<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $bio);
          if (is_string($tmp)) $bio = $tmp;
        }
      }

      // Attachments
      if ($attachments_engine) {
        $use_slug = $slug_out !== '' ? $slug_out : $slug;
        $attachments_items = $attachments_engine->load_for_contributor(
          $use_slug,
          $id_out,
          (isset($pdo) && $pdo instanceof PDO) ? $pdo : null,
          (string)$bio
        );
      }

      $initial = strtoupper(substr($display, 0, 1));
    ?>

    <header class="mk-hero mk-hero--compact">
      <div class="mk-hero__bar" aria-hidden="true"></div>
      <div class="mk-hero__inner">
        <h1 class="mk-hero__title"><?= h($display); ?></h1>
        <p class="mk-muted mk-lede">
          <a href="<?= h($contributors_url); ?>">Contributors</a>
          <span aria-hidden="true">›</span>
          <span><?= h($display); ?></span>
          <?php if ($slug_out !== ''): ?><span class="mk-pill"><?= h($slug_out); ?></span><?php endif; ?>
        </p>
      </div>
    </header>

    <section class="mk-profile">

      <div class="mk-card">
        <div class="mk-card__body">
          <div class="mk-profile__grid">

            <div class="mk-profile__avatar">
              <?php if ($avatar !== ''): ?>
                <img class="mk-avatar" src="<?= h($avatar); ?>" alt="<?= h($display); ?>">
              <?php else: ?>
                <div class="mk-avatar mk-avatar--fallback" aria-hidden="true"><?= h($initial); ?></div>
              <?php endif; ?>
            </div>

            <div class="mk-profile__content">
              <h2 class="mk-section-title">Bio</h2>

              <?php if ($bio === ''): ?>
                <p class="mk-muted"><em>(Bio coming soon)</em></p>
              <?php else: ?>
                <div class="mk-prose"><?= $bio; ?></div>
              <?php endif; ?>

              <div class="mk-profile__actions">
                <a class="mk-btn" href="<?= h($subjects_url); ?>">Subjects</a>
                <a class="mk-btn" href="<?= h($platforms_url); ?>">Platforms</a>
                <a class="mk-btn mk-btn--ghost" href="<?= h($home_url); ?>">Home</a>
              </div>
            </div>

          </div>
        </div>
      </div>

      <?php if ($attachments_engine && !empty($attachments_items)): ?>
        <?= $attachments_engine->render($attachments_items) ?>
        <div class="mk-muted mk-attach-hint">
          Folder convention: <code>/contributors-media/<?= h($slug_out !== '' ? $slug_out : $slug) ?>/</code>
        </div>
      <?php endif; ?>

    </section>

  <?php endif; ?>

</main>

<?php
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_footer.php'); } catch (Throwable $e) {}
} else {
  echo "</body></html>";
}
