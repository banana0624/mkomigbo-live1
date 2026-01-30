<?php
declare(strict_types=1);

/**
 * /public/subjects/page.php
 * Canonical: /subjects/{subject}/{page}/
 *
 * Responsibilities:
 * - Validate slugs
 * - Canonical redirect from controller URL
 * - Fetch subject + page (schema-tolerant)
 * - Render article + sidebar
 * - Render attachments via attachments_engine.php (if present)
 *
 * HARD RULE: never blank-500. If something fatal happens, emit a safe error page.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

/* ---------------------------------------------------------
   Safe helpers
--------------------------------------------------------- */
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('mk_u')) {
  function mk_u(string $path): string { return function_exists('url_for') ? (string)url_for($path) : $path; }
}
if (!function_exists('mk_safe_redirect')) {
  function mk_safe_redirect(string $location, int $code = 302): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, $code);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Redirecting to: " . $location;
    exit;
  }
}
if (!function_exists('mk_excerpt_text')) {
  function mk_excerpt_text(string $html, int $limit = 220): string {
    $txt = trim(strip_tags($html));
    $txt = preg_replace('/\s+/u', ' ', $txt) ?? $txt;
    if ($txt === '') return '';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
      if (mb_strlen($txt, 'UTF-8') <= $limit) return $txt;
      return rtrim(mb_substr($txt, 0, $limit, 'UTF-8')) . '…';
    }
    if (strlen($txt) <= $limit) return $txt;
    return rtrim(substr($txt, 0, $limit)) . '…';
  }
}

/* ---------------------------------------------------------
   Crash-proofing (safe 500 page + log)
--------------------------------------------------------- */
$req_id = bin2hex(random_bytes(6));
$log_dir = (defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '') . '/logs';
$log_file = ($log_dir !== '') ? ($log_dir . '/public_errors.log') : '';

$mk_log = static function(string $msg) use ($log_dir, $log_file, $req_id): void {
  if ($log_file === '') return;
  if (!is_dir($log_dir)) { @mkdir($log_dir, 0755, true); }
  $line = '[' . gmdate('c') . '] req=' . $req_id . ' ' . $msg . "\n";
  @file_put_contents($log_file, $line, FILE_APPEND);
};

register_shutdown_function(static function() use ($mk_log, $req_id) {
  $e = error_get_last();
  if (!$e) return;
  $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
  if (!in_array((int)$e['type'], $fatal, true)) return;

  http_response_code(500);
  header('Content-Type: text/html; charset=utf-8');

  $mk_log('FATAL type=' . $e['type'] . ' file=' . ($e['file'] ?? '') . ':' . ($e['line'] ?? '') . ' msg=' . ($e['message'] ?? ''));

  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>Server error</title></head><body>";
  echo "<main style='max-width:920px;margin:24px auto;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;line-height:1.4'>";
  echo "<h1>Server error</h1>";
  echo "<p>Something went wrong while rendering this page.</p>";
  echo "<p style='opacity:.75'>Request id: <code>" . htmlspecialchars($req_id, ENT_QUOTES, 'UTF-8') . "</code></p>";
  echo "</main></body></html>";
});

/* ---------------------------------------------------------
   404 helper (never 500)
--------------------------------------------------------- */
if (!function_exists('mk_subjects_404')) {
  function mk_subjects_404(string $title = 'Page not found', string $message = 'The page you requested does not exist.'): void
  {
    http_response_code(404);

    $brand = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomi Igbo';
    $page_title = $title . ' • ' . $brand;

    $GLOBALS['page_title'] = $page_title;
    $GLOBALS['page_desc']  = $message;
    $GLOBALS['active_nav'] = 'subjects';
    $GLOBALS['nav_active'] = 'subjects';
    $GLOBALS['extra_css']  = [ mk_u('/lib/css/public.css'), mk_u('/lib/css/article.css'), mk_u('/lib/css/subjects.css') ];

    if (function_exists('mk_view_set')) {
      try {
        mk_view_set([
          'page_title' => $page_title,
          'page_desc'  => $message,
          'active_nav' => 'subjects',
          'nav_active' => 'subjects',
          'extra_css'  => $GLOBALS['extra_css'],
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
      echo "<title>" . h($page_title) . "</title></head><body>";
    }

    $subjects_url = mk_u('/subjects/');
    $home_url     = mk_u('/');

    echo '<main class="container mk-page">';
    echo '  <div class="mk-page-actions">';
    echo '    <a class="mk-btn mk-btn--ghost" href="' . h($subjects_url) . '">← Back to Subjects</a>';
    echo '    <a class="mk-btn mk-btn--ghost" href="' . h($home_url) . '">Home</a>';
    echo '  </div>';
    echo '  <header class="mk-hero mk-hero--compact">';
    echo '    <div class="mk-hero__bar" aria-hidden="true"></div>';
    echo '    <div class="mk-hero__inner">';
    echo '      <h1 class="mk-hero__title">' . h($title) . '</h1>';
    echo '      <p class="mk-muted mk-lede">' . h($message) . '</p>';
    echo '    </div>';
    echo '  </header>';
    echo '</main>';

    if (function_exists('mk_require_shared')) {
      try { mk_require_shared('public_footer.php'); } catch (Throwable $e) {}
    } else {
      echo "</body></html>";
    }
    exit;
  }
}

/* ---------------------------------------------------------
   Inputs
--------------------------------------------------------- */
$subject_slug = isset($_GET['subject']) && is_scalar($_GET['subject']) ? trim((string)$_GET['subject']) : '';
$page_slug = '';
if (isset($_GET['slug']) && is_scalar($_GET['slug'])) $page_slug = trim((string)$_GET['slug']);
elseif (isset($_GET['page']) && is_scalar($_GET['page'])) $page_slug = trim((string)$_GET['page']);

$subject_slug = strtolower($subject_slug);
$page_slug    = strtolower($page_slug);

$valid_slug = static function (string $s): bool {
  return ($s !== '') && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
};
if (!$valid_slug($subject_slug) || !$valid_slug($page_slug)) {
  mk_subjects_404('Page not found', 'Invalid subject/page slug.');
}

/* Canonical redirect from controller path */
$req_path = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
if (stripos($req_path, '/subjects/page.php') !== false) {
  $pretty = '/subjects/' . rawurlencode($subject_slug) . '/' . rawurlencode($page_slug) . '/';
  mk_safe_redirect(mk_u($pretty), 301);
}

/* Schema helper */
if (!function_exists('mk_has_col')) {
  function mk_has_col(PDO $pdo, string $table, string $column): bool
  {
    if (function_exists('mk_has_column')) {
      try { return (bool)mk_has_column($pdo, $table, $column); } catch (Throwable $e) {}
    }
    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];

    try {
      $st = $pdo->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
      ");
      $st->execute([$table, $column]);
      $cache[$key] = (bool)$st->fetchColumn();
      return (bool)$cache[$key];
    } catch (Throwable $e) {
      $cache[$key] = false;
      return false;
    }
  }
}

/* HTML sanitizer */
if (!function_exists('mk_page_sanitize_html')) {
  function mk_page_sanitize_html(string $html): string
  {
    if ($html === '') return '';
    if (function_exists('mk_sanitize_allowlist_html')) {
      try { return (string)mk_sanitize_allowlist_html($html); } catch (Throwable $e) {}
    }
    $tmp = preg_replace('~<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $html);
    return is_string($tmp) ? $tmp : $html;
  }
}

/* ---------------------------------------------------------
   Attachments engine (optional)
--------------------------------------------------------- */
$attachments_engine = null;
$attachments_items  = [];

try {
  $engine_file = (defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, "/\\") : '') . '/functions/attachments_engine.php';
  if ($engine_file !== '' && is_file($engine_file)) {
    require_once $engine_file;
    if (function_exists('mk_attachments_engine')) $attachments_engine = mk_attachments_engine();
  }
} catch (Throwable $e) {
  $mk_log('attachments_engine load failed: ' . $e->getMessage());
}

/* ---------------------------------------------------------
   Load subject + page + sidebar
--------------------------------------------------------- */
$subject = null;
$page = null;
$sidebar_pages = [];
$db_error = '';

try {
  if (!function_exists('db')) throw new RuntimeException('db() not available.');
  $pdo = db();
  if (!$pdo instanceof PDO) throw new RuntimeException('DB connection not available.');

  /* SUBJECT cols */
  $subNameCol = 'name';
  if (!mk_has_col($pdo, 'subjects', 'name')) {
    if (mk_has_col($pdo, 'subjects', 'menu_name')) $subNameCol = 'menu_name';
    elseif (mk_has_col($pdo, 'subjects', 'subject_name')) $subNameCol = 'subject_name';
    else $subNameCol = 'slug';
  }

  $subDescCol = null;
  if (mk_has_col($pdo, 'subjects', 'meta_description')) $subDescCol = 'meta_description';
  elseif (mk_has_col($pdo, 'subjects', 'short_desc')) $subDescCol = 'short_desc';
  elseif (mk_has_col($pdo, 'subjects', 'description')) $subDescCol = 'description';
  elseif (mk_has_col($pdo, 'subjects', 'content')) $subDescCol = 'content';

  $subCols = ['id', 'slug', "{$subNameCol} AS name"];
  if ($subDescCol) $subCols[] = "{$subDescCol} AS description";

  $st = $pdo->prepare("SELECT " . implode(', ', $subCols) . " FROM subjects WHERE slug = ? LIMIT 1");
  $st->execute([$subject_slug]);
  $subject = $st->fetch(PDO::FETCH_ASSOC) ?: null;

  if ($subject) {
    $sid = (int)($subject['id'] ?? 0);

    $hasTitle    = mk_has_col($pdo, 'pages', 'title');
    $hasMenuName = mk_has_col($pdo, 'pages', 'menu_name');
    $hasBodyHtml = mk_has_col($pdo, 'pages', 'body_html');
    $hasBody     = mk_has_col($pdo, 'pages', 'body');
    $hasContent  = mk_has_col($pdo, 'pages', 'content');

    $hasStatus   = mk_has_col($pdo, 'pages', 'status');
    $hasIsPublic = mk_has_col($pdo, 'pages', 'is_public');
    $hasVisible  = mk_has_col($pdo, 'pages', 'visible');

    $hasNavOrder = mk_has_col($pdo, 'pages', 'nav_order');
    $hasPosition = mk_has_col($pdo, 'pages', 'position');

    /* PAGE */
    $pCols = ['id', 'subject_id', 'slug'];
    if ($hasTitle)    $pCols[] = 'title';
    if ($hasMenuName) $pCols[] = 'menu_name';
    if ($hasBodyHtml) $pCols[] = 'body_html';
    if ($hasBody)     $pCols[] = 'body';
    if ($hasContent)  $pCols[] = 'content';

    $where = "subject_id = ? AND slug = ?";
    if ($hasStatus)       $where .= " AND status IN ('active','published','public')";
    elseif ($hasIsPublic) $where .= " AND is_public = 1";
    elseif ($hasVisible)  $where .= " AND visible = 1";

    $st2 = $pdo->prepare("SELECT " . implode(', ', array_unique($pCols)) . " FROM pages WHERE {$where} LIMIT 1");
    $st2->execute([$sid, $page_slug]);
    $page = $st2->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($page) {
      $t = '';
      if (isset($page['title']) && is_string($page['title'])) $t = trim($page['title']);
      if ($t === '' && isset($page['menu_name']) && is_string($page['menu_name'])) $t = trim($page['menu_name']);
      if ($t === '') $t = (string)($page['slug'] ?? '');
      $page['title'] = $t;

      $body_html = '';
      if (!empty($page['body_html']) && is_string($page['body_html'])) $body_html = (string)$page['body_html'];
      elseif (!empty($page['body']) && is_string($page['body'])) $body_html = (string)$page['body'];
      elseif (!empty($page['content']) && is_string($page['content'])) $body_html = (string)$page['content'];
      $page['body_html'] = $body_html;

      if ($attachments_engine && method_exists($attachments_engine, 'load_for_subject_page')) {
      try {
        $attachments_items = $attachments_engine->load_for_subject_page(
          $subject_slug,
          $page_slug,
          $pdo,
          (int)($page['id'] ?? 0),
          (string)($page['body_html'] ?? '')
        );
    
        // DEBUG TAP (safe): log count + first few URLs/titles
        $sample = [];
        foreach (array_slice($attachments_items, 0, 8) as $it) {
          $sample[] = [
            'kind'  => (string)($it['kind'] ?? ''),
            'title' => (string)($it['title'] ?? ''),
            'url'   => (string)($it['url'] ?? ''),
            'src'   => (string)($it['source'] ?? ''),
          ];
        }
        $mk_log('attachments_engine items=' . count($attachments_items) . ' sample=' . json_encode($sample, JSON_UNESCAPED_SLASHES));
      } catch (Throwable $e) {
        $mk_log('attachments_engine load_for_subject_page failed: ' . $e->getMessage());
      }
    }

    }

    /* SIDEBAR */
    $sCols = ['id','slug'];
    if ($hasTitle)    $sCols[] = 'title';
    if ($hasMenuName) $sCols[] = 'menu_name';

    $whereS = "subject_id = ?";
    if ($hasStatus)       $whereS .= " AND status IN ('active','published','public')";
    elseif ($hasIsPublic) $whereS .= " AND is_public = 1";
    elseif ($hasVisible)  $whereS .= " AND visible = 1";

    $orderCol = $hasNavOrder ? 'nav_order' : ($hasPosition ? 'position' : 'id');

    $st3 = $pdo->prepare("
      SELECT " . implode(', ', array_unique($sCols)) . "
      FROM pages
      WHERE {$whereS}
      ORDER BY {$orderCol} IS NULL, {$orderCol} ASC, id ASC
    ");
    $st3->execute([$sid]);
    $sidebar_pages = $st3->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($sidebar_pages as &$sp) {
      $lbl = '';
      if (isset($sp['title']) && is_string($sp['title'])) $lbl = trim($sp['title']);
      if ($lbl === '' && isset($sp['menu_name']) && is_string($sp['menu_name'])) $lbl = trim($sp['menu_name']);
      if ($lbl === '') $lbl = (string)($sp['slug'] ?? '');
      $sp['title'] = $lbl;
    }
    unset($sp);
  }

} catch (Throwable $e) {
  $db_error = $e->getMessage();
  $mk_log('DB error: ' . $db_error);
}

/* ---------------------------------------------------------
   Titles + meta
--------------------------------------------------------- */
$brand = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomi Igbo';

$subject_title = $subject ? trim((string)($subject['name'] ?? $subject_slug)) : $subject_slug;
if ($subject_title === '') $subject_title = $subject_slug;

$page_title_txt = $page ? trim((string)($page['title'] ?? $page_slug)) : 'Page not found';
if ($page_title_txt === '') $page_title_txt = $page_slug;

$body_html_raw = ($page && is_string($page['body_html'] ?? null)) ? (string)$page['body_html'] : '';
$lede = ($body_html_raw !== '') ? mk_excerpt_text($body_html_raw, 220) : '';

$page_title_full = ($subject && $page)
  ? ($page_title_txt . ' • ' . $subject_title . ' • ' . $brand)
  : ('Page not found • ' . $brand);

$page_desc_full = ($subject && $page)
  ? (trim($lede) !== '' ? $lede : ('Read “' . $page_title_txt . '” under ' . $subject_title . ' on ' . $brand . '.'))
  : ('Page not found on ' . $brand . '.');

/* Layout contract */
$GLOBALS['page_title'] = $page_title_full;
$GLOBALS['page_desc']  = $page_desc_full;
$GLOBALS['active_nav'] = 'subjects';
$GLOBALS['nav_active'] = 'subjects';
$GLOBALS['extra_css']  = [ mk_u('/lib/css/public.css'), mk_u('/lib/css/article.css'), mk_u('/lib/css/subjects.css') ];

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title_full,
      'page_desc'  => $page_desc_full,
      'active_nav' => 'subjects',
      'nav_active' => 'subjects',
      'extra_css'  => $GLOBALS['extra_css'],
    ]);
  } catch (Throwable $e) {}
}

/* Header */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}
if (!$header_ok) {
  header('Content-Type: text/html; charset=utf-8');
  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($page_title_full) . "</title></head><body>";
}

/* Canonical URLs */
$home_url     = mk_u('/');
$subjects_url = mk_u('/subjects/');
$subject_url  = mk_u('/subjects/' . rawurlencode($subject_slug) . '/');
$page_url     = mk_u('/subjects/' . rawurlencode($subject_slug) . '/' . rawurlencode($page_slug) . '/');

if (!$subject || !$page) http_response_code(404);

$body_html = mk_page_sanitize_html($body_html_raw);
?>
<main class="container mk-page">

  <?php if ($db_error !== ''): ?>
    <div class="mk-alert mk-alert--danger">
      <strong>DB Error:</strong> <?= h($db_error) ?>
      <div class="mk-muted" style="margin-top:6px;">Request id: <code><?= h($req_id) ?></code></div>
    </div>
  <?php endif; ?>

  <div class="mk-page-actions">
    <a class="mk-btn mk-btn--ghost" href="<?= h($subjects_url) ?>">← Back to Subjects</a>
    <a class="mk-btn mk-btn--ghost" href="<?= h($subject_url) ?>">← Back to <?= h($subject_title) ?></a>
    <a class="mk-btn mk-btn--ghost" href="<?= h($home_url) ?>">Home</a>
  </div>

  <nav class="mk-crumbs" aria-label="Breadcrumb">
    <a href="<?= h($home_url) ?>">Home</a>
    <span class="mk-crumbs__sep">›</span>
    <a href="<?= h($subjects_url) ?>">Subjects</a>
    <span class="mk-crumbs__sep">›</span>
    <a href="<?= h($subject_url) ?>"><?= h($subject_title) ?></a>
    <span class="mk-crumbs__sep">›</span>
    <span class="mk-crumbs__current"><?= h($page_title_txt) ?></span>
  </nav>

  <?php if (!$subject): ?>

    <?php mk_subjects_404('Subject not found', 'Slug: ' . $subject_slug); ?>

  <?php elseif (!$page): ?>

    <?php mk_subjects_404('Page not found', 'Subject: ' . $subject_slug . ' · Page: ' . $page_slug); ?>

  <?php else: ?>

    <header class="mk-article-hero mk-article-hero--compact">
      <div class="mk-article-hero__bar" aria-hidden="true"></div>
      <div class="mk-article-hero__inner">
        <h1 class="mk-article-hero__title"><?= h($page_title_txt) ?></h1>
        <?php if (trim($lede) !== ''): ?>
          <p class="mk-article-hero__lede mk-muted"><?= h($lede) ?></p>
        <?php endif; ?>
      </div>
    </header>

    <section class="mk-article-shell mk-article-shell--with-aside">

      <article class="mk-article">
        <div class="mk-card">
          <div class="mk-card__body">
            <?php if (trim($body_html) === ''): ?>
              <p class="mk-muted" style="margin:0;">This page has no content yet.</p>
            <?php else: ?>
              <div class="mk-prose"><?= $body_html ?></div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($attachments_engine && method_exists($attachments_engine, 'render') && !empty($attachments_items)): ?>

          <?php if (defined('MK_ATTACH_DEBUG') && MK_ATTACH_DEBUG === true && isset($_GET['attach_debug']) && (string)$_GET['attach_debug'] === '1'): ?>
            <div class="mk-card" style="margin-top:12px;">
              <div class="mk-card__body">
                <div style="font-weight:800; margin-bottom:8px;">Attachments debug</div>
                <pre style="white-space:pre-wrap; overflow:auto; margin:0;"><?= h(json_encode($attachments_items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
              </div>
            </div>
          <?php endif; ?>
        
          <?= $attachments_engine->render($attachments_items) ?>
          <div class="mk-muted mk-attach-hint">
            Folder convention: <code>/subjects-media/<?= h($subject_slug) ?>/<?= h($page_slug) ?>/</code>
          </div>
        <?php endif; ?>

        
      </article>

      <aside class="mk-aside">
        <div class="mk-card">
          <div class="mk-card__body">
            <div class="mk-aside__title">More in <?= h($subject_title) ?></div>

            <?php if (empty($sidebar_pages)): ?>
              <div class="mk-muted">No other pages listed yet.</div>
            <?php else: ?>
              <ul class="mk-aside__list">
                <?php foreach ($sidebar_pages as $sp): ?>
                  <?php
                    $sp_slug = strtolower(trim((string)($sp['slug'] ?? '')));
                    if ($sp_slug === '' || !preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $sp_slug)) continue;

                    $sp_title = trim((string)($sp['title'] ?? ''));
                    if ($sp_title === '') $sp_title = $sp_slug;

                    $sp_url = mk_u('/subjects/' . rawurlencode($subject_slug) . '/' . rawurlencode($sp_slug) . '/');
                    $is_current = ($sp_slug === $page_slug);
                  ?>
                  <li>
                    <a class="<?= $is_current ? 'is-current' : '' ?>" href="<?= h($sp_url) ?>">
                      <?= h($sp_title) ?>
                      <?php if ($is_current): ?><span class="mk-muted">(current)</span><?php endif; ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <div class="mk-aside__actions">
              <a class="mk-btn mk-btn--ghost" href="<?= h($subject_url) ?>">Subject landing</a>
              <a class="mk-btn mk-btn--ghost" href="<?= h($subjects_url) ?>">All subjects</a>
            </div>
          </div>
        </div>
      </aside>

    </section>

  <?php endif; ?>

</main>

<?php
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_footer.php'); } catch (Throwable $e) {}
} else {
  echo "</body></html>";
}
