<?php
declare(strict_types=1);

/**
 * /public/contributors/view.php
 * Public contributor profile (premium, schema-tolerant, canonical).
 *
 * Goals:
 * - Must never hard-500 (catch all Throwable)
 * - Works with both controller query (?slug=) AND pretty URLs (/contributors/{slug}/)
 * - Schema tolerant contributors table
 * - Attachments engine is optional and must never crash the page
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

// Optional module shim (safe)
$__mod_init = __DIR__ . '/_init.php';
if (is_file($__mod_init)) { require_once $__mod_init; }

/* ---------------------------
   Minimal helpers (fallbacks)
--------------------------- */
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

/* ---------------------------
   Schema helpers (safe)
--------------------------- */
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

/* =========================================================
   Main handler (catch-all): never allow initialize.php to take over
========================================================= */
try {

  /* ---------------------------
     Inputs: query + pretty URL fallback
  --------------------------- */
  $raw_slug = isset($_GET['slug']) && is_scalar($_GET['slug']) ? trim((string)$_GET['slug']) : '';
  $raw_id   = isset($_GET['id'])   && is_scalar($_GET['id'])   ? trim((string)$_GET['id'])   : '';

  $request_uri = (string)($_SERVER['REQUEST_URI'] ?? '');
  $path_only   = (string)(parse_url($request_uri, PHP_URL_PATH) ?: '');

  // If rewrite didn't pass ?slug=, derive from /contributors/{slug}/ or /contributors/id/{id}/
  if ($raw_slug === '' && $raw_id === '') {
    $m = [];
    if (preg_match('~^/contributors/([a-z0-9][a-z0-9_-]{0,190})/?$~i', $path_only, $m)) {
      $raw_slug = (string)$m[1];
    } elseif (preg_match('~^/contributors/id/([0-9]{1,10})/?$~', $path_only, $m)) {
      $raw_id = (string)$m[1];
    }
  }

  $slug = strtolower($raw_slug);
  $id   = (int)$raw_id;

  if ($slug !== '' && !preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $slug)) $slug = '';
  if ($id < 0) $id = 0;

  /* Canonical redirect (only for controller hits) */
  $path_lower = strtolower($path_only);
  if (strpos($path_lower, '/contributors/view.php') !== false) {
    if ($slug !== '') pf__redirect_301(pf__u('/contributors/' . rawurlencode($slug) . '/'));
    if ($id > 0)      pf__redirect_301(pf__u('/contributors/id/' . rawurlencode((string)$id) . '/'));
  }

  /* ---------------------------
     Attachments engine (optional, must never crash)
  --------------------------- */
  $attachments_engine = null;
  $attachments_items  = [];

  $engine_file = (defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, "/\\") : '') . '/functions/attachments_engine.php';
  if ($engine_file !== '' && is_file($engine_file)) {
    try {
      require_once $engine_file;
      if (function_exists('mk_attachments_engine')) {
        $tmp = mk_attachments_engine();
        // Accept object only; anything else is ignored to prevent fatal calls
        if (is_object($tmp)) $attachments_engine = $tmp;
      }
    } catch (Throwable $e) {
      $attachments_engine = null;
      $attachments_items = [];
    }
  }

  /* ---------------------------
     DB fetch (schema-tolerant)
  --------------------------- */
  $contrib = null;
  $db_note = '';

  $pdo = null;
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
        // If neither slug nor id is present, treat as not found (but do NOT crash)
        $where[] = "1=0";
      }

      // Public gating (only if those columns exist)
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

  /* ---------------------------
     Page vars
  --------------------------- */
  $nav_active = 'contributors';
  $active_nav = 'contributors';

  $display = $contrib ? trim((string)($contrib['display_name'] ?? '')) : '';
  if ($display === '') $display = ($slug !== '') ? $slug : (($id > 0) ? ('Contributor #' . $id) : 'Contributor');

  $page_title = $contrib ? ($display . ' — Contributors — Mkomi Igbo') : 'Contributor not found — Mkomi Igbo';
  $page_desc  = $contrib ? ('Profile of ' . $display . ' on Mkomi Igbo.') : 'The requested contributor profile could not be found.';

  $extra_css = [
    pf__u('/lib/css/public.css'),
    pf__u('/lib/css/article.css'),
    pf__u('/lib/css/contributors.css'),
  ];

  /* Cache (GET only) */
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && function_exists('mk_public_cache_headers')) {
    try { mk_public_cache_headers(300); } catch (Throwable $e) {}
  }

  /* Layout contract (globals are required due to mk_require_shared scope) */
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

  /* IMPORTANT: status code before header output */
  if (!$contrib) {
    http_response_code(404);
  } else {
    http_response_code(200);
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

  $contributors_url = pf__u('/contributors/');
  $home_url         = pf__u('/');
  $subjects_url     = pf__u('/subjects/');
  $platforms_url    = pf__u('/platforms/');

  ?>
  <main class="container mk-page">

    <div class="mk-page-actions">
      <a class="mk-btn mk-btn--ghost" href="<?= h($contributors_url); ?>">← Back to Contributors</a>
      <a class="mk-btn mk-btn--ghost" href="<?= h($home_url); ?>">Home</a>
    </div>

    <?php if ($db_note !== '' && (defined('APP_DEBUG') && APP_DEBUG)): ?>
      <div class="mk-alert mk-alert--danger">
        <strong>DB Error:</strong> <?= h($db_note); ?>
      </div>
    <?php endif; ?>

    <?php if (!$contrib): ?>

      <header class="mk-hero mk-hero--compact">
        <div class="mk-hero__bar" aria-hidden="true"></div>
        <div class="mk-hero__inner">
          <h1 class="mk-hero__title">Contributor not found</h1>
          <p class="mk-muted mk-lede">No public contributor matched your request.</p>
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

        // Attachments: only call if the engine has the required methods
        if (
          $attachments_engine &&
          is_object($attachments_engine) &&
          method_exists($attachments_engine, 'load_for_contributor') &&
          method_exists($attachments_engine, 'render')
        ) {
          try {
            $use_slug = $slug_out !== '' ? $slug_out : $slug;
            $attachments_items = $attachments_engine->load_for_contributor(
              $use_slug,
              $id_out,
              ($pdo instanceof PDO) ? $pdo : null,
              (string)$bio
            );
            if (!is_array($attachments_items)) $attachments_items = [];
          } catch (Throwable $e) {
            $attachments_items = [];
          }
        } else {
          $attachments_items = [];
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

        <?php if (!empty($attachments_items) && $attachments_engine && method_exists($attachments_engine, 'render')): ?>
          <?php
            try {
              echo $attachments_engine->render($attachments_items);
            } catch (Throwable $e) {
              // Never crash page over attachments
            }
          ?>
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

} catch (Throwable $e) {
  // Absolute last-resort: never let initialize.php print text/plain internal error
  $ref = '';
  try { $ref = bin2hex(random_bytes(8)) . '-' . bin2hex(random_bytes(4)); } catch (Throwable $x) { $ref = 'n/a'; }

  try {
    if (function_exists('app_log')) {
      app_log('error', 'contributors view hard-failed', [
        'ref' => $ref,
        'uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => substr($e->getTraceAsString(), 0, 4000),
      ]);
    } else {
      error_log('[contributors][fatal] ref=' . $ref . ' ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }
  } catch (Throwable $ignore) {}

  http_response_code(500);
  if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');

  echo "<!doctype html><html lang='en'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>";
  echo "<title>Internal error — Mkomigbo</title></head><body>";
  echo "<main style='max-width:900px;margin:40px auto;padding:0 16px;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial'>";
  echo "<h1>Internal error</h1>";
  echo "<p>We hit an internal error while rendering this contributor profile.</p>";
  echo "<p><strong>Reference:</strong> " . h($ref) . "</p>";
  echo "<p><a href='/contributors/'>← Back to Contributors</a></p>";
  echo "</main></body></html>";
}
