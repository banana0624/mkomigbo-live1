<?php
declare(strict_types=1);

/**
 * /public/subjects/subject.php
 * Subject landing page:
 * - overview/intro
 * - grouped dropdown list of pages (CSS-first; light JS filter)
 *
 * Routes:
 *   /subjects/{slug}/ -> this file
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
if (!function_exists('redirect_to')) {
  function redirect_to(string $location, int $code = 302): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, $code);
    exit;
  }
}
if (!function_exists('mk_u')) {
  function mk_u(string $path): string {
    return function_exists('url_for') ? (string)url_for($path) : $path;
  }
}
if (!function_exists('mk_excerpt_text')) {
  function mk_excerpt_text(string $html, int $limit = 180): string {
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
   404 helper (never 500)
--------------------------------------------------------- */
if (!function_exists('mk_subjects_not_found')) {
  function mk_subjects_not_found(string $title = 'Not Found', string $message = 'The page you requested does not exist.'): void {
    http_response_code(404);

    $brand = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomi Igbo';
    $page_title = $title . ' • ' . $brand;

    try {
      if (function_exists('mk_view_set')) {
        mk_view_set([
          'page_title' => $page_title,
          'page_desc'  => $message,
          'active_nav' => 'subjects',
          'nav_active' => 'subjects',
          'extra_css'  => [ mk_u('/lib/css/public.css'), mk_u('/lib/css/subjects.css') ],
        ]);
      } else {
        $GLOBALS['page_title'] = $page_title;
        $GLOBALS['page_desc']  = $message;
        $GLOBALS['active_nav'] = 'subjects';
        $GLOBALS['nav_active'] = 'subjects';
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

      $back = mk_u('/subjects/');
      $home = mk_u('/');

      echo '<main class="container mk-page">';
      echo '  <div class="mk-page-actions">';
      echo '    <a class="mk-btn mk-btn--ghost" href="' . h($back) . '">← Back to Subjects</a>';
      echo '    <a class="mk-btn mk-btn--ghost" href="' . h($home) . '">Home</a>';
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
        mk_require_shared('public_footer.php');
      } else {
        echo "</body></html>";
      }
      exit;
    } catch (Throwable $e) {}

    header('Content-Type: text/plain; charset=utf-8');
    echo "404 - " . $title . "\n" . $message;
    exit;
  }
}

/* ---------------------------------------------------------
   Read and validate subject slug
--------------------------------------------------------- */
$subject_slug = '';
if (isset($_GET['slug']) && is_scalar($_GET['slug'])) $subject_slug = trim((string)$_GET['slug']);
if ($subject_slug === '' && isset($_GET['subject']) && is_scalar($_GET['subject'])) $subject_slug = trim((string)$_GET['subject']);
$subject_slug = strtolower($subject_slug);

if ($subject_slug === '') redirect_to(mk_u('/subjects/'), 302);
if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $subject_slug)) {
  mk_subjects_not_found('Subject not found', 'Invalid subject slug.');
}

/* Canonical URL enforcement */
$req_path = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
$req_path_lc = strtolower($req_path);
if (strpos($req_path_lc, '/subjects/subject.php') !== false || strpos($req_path_lc, '/public/subjects/subject.php') !== false) {
  $pretty = '/subjects/' . rawurlencode($subject_slug) . '/';
  redirect_to(mk_u($pretty), 301);
}

/* ---------------------------------------------------------
   Column helper
--------------------------------------------------------- */
if (!function_exists('mk_subjects_has_column')) {
  function mk_subjects_has_column(PDO $pdo, string $table, string $column): bool
  {
    if (function_exists('mk_has_column')) {
      try { return (bool)mk_has_column($pdo, $table, $column); } catch (Throwable $e) {}
    }

    static $cache = [];
    $k = strtolower($table . '.' . $column);
    if (array_key_exists($k, $cache)) return (bool)$cache[$k];

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
      $cache[$k] = (bool)$st->fetchColumn();
      return (bool)$cache[$k];
    } catch (Throwable $e) {
      $cache[$k] = false;
      return false;
    }
  }
}

/* ---------------------------------------------------------
   Registry fallback loader
--------------------------------------------------------- */
if (!function_exists('mk_subjects_load_registry')) {
  function mk_subjects_load_registry(): void
  {
    $file = (defined('APP_ROOT') ? rtrim((string)APP_ROOT, '/') : '') . '/private/registry/subjects_register.php';
    if ($file !== '' && is_file($file)) require_once $file;
  }
}

/* ---------------------------------------------------------
   Attachments count (folder scan only; zero-DB)
   Canonical base: PRIVATE_PATH/subjects-media/{subject}/{page}/
--------------------------------------------------------- */
function mk_subjects_media_base(): string {
  if (defined('PRIVATE_PATH')) {
    $p = rtrim((string)PRIVATE_PATH, "/\\") . '/subjects-media';
    if (is_dir($p)) return $p;
  }
  if (defined('APP_ROOT')) {
    $p = rtrim((string)APP_ROOT, "/\\") . '/private/subjects-media';
    if (is_dir($p)) return $p;
  }
  return '';
}

if (!function_exists('mk_subjects_safe_media_dir')) {
  function mk_subjects_safe_media_dir(string $subject_slug, string $page_slug): string {
    $base = mk_subjects_media_base();
    if ($base === '' || !is_dir($base)) return '';

    $subject_slug = strtolower($subject_slug);
    $page_slug    = strtolower($page_slug);

    if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $subject_slug)) return '';
    if (!preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $page_slug)) return '';

    $candidate = $base . '/' . $subject_slug . '/' . $page_slug;

    $base_r = realpath($base);
    $cand_r = realpath($candidate);

    if ($base_r === false || $cand_r === false) return '';
    $base_r = rtrim(str_replace('\\', '/', $base_r), '/');
    $cand_r = rtrim(str_replace('\\', '/', $cand_r), '/');

    if ($cand_r === $base_r) return '';
    if (strpos($cand_r . '/', $base_r . '/') !== 0) return '';

    return $cand_r;
  }
}

if (!function_exists('mk_subjects_attachment_count')) {
  function mk_subjects_attachment_count(string $subject_slug, string $page_slug): array {

    static $cache = [];
    $key = strtolower($subject_slug . '//' . $page_slug);
    if (isset($cache[$key]) && is_array($cache[$key])) return $cache[$key];

    $dir = mk_subjects_safe_media_dir($subject_slug, $page_slug);
    if ($dir === '' || !is_dir($dir) || !is_readable($dir)) {
      return $cache[$key] = ['local' => 0, 'external' => 0, 'total' => 0];
    }

    $allowed = [
      'jpg','jpeg','png','webp','gif','svg',
      'mp4','webm','mov',
      'mp3','wav','ogg','m4a',
      'pdf','txt','csv','doc','docx','ppt','pptx','xls','xlsx','zip'
    ];

    $local = 0;
    $external = 0;

    try {
      $it = new DirectoryIterator($dir);
      foreach ($it as $f) {
        if ($f->isDot()) continue;
        if ($f->isDir()) continue;

        $name = (string)$f->getFilename();
        if ($name === '' || $name[0] === '.') continue;

        if (strcasecmp($name, 'meta.json') === 0) continue;

        if (strcasecmp($name, 'links.json') === 0) {
          $raw = @file_get_contents($f->getPathname());
          if (is_string($raw) && trim($raw) !== '') {
            $j = json_decode($raw, true);
            if (is_array($j)) {
              $arr = $j['links'] ?? $j;
              if (is_array($arr)) {
                $n = 0;
                foreach ($arr as $row) {
                  if (is_array($row) && isset($row['url']) && is_string($row['url']) && trim($row['url']) !== '') $n++;
                  elseif (is_string($row) && trim($row) !== '') $n++;
                }
                $external += $n;
              }
            }
          }
          continue;
        }

        $ext = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $allowed, true)) continue;
        if (in_array($ext, ['php','phtml','phar','cgi','pl','py','js','html','htm','sh'], true)) continue;

        $local++;
      }
    } catch (Throwable $e) {
      return $cache[$key] = ['local' => 0, 'external' => 0, 'total' => 0];
    }

    $total = $local + $external;
    return $cache[$key] = ['local' => $local, 'external' => $external, 'total' => $total];
  }
}

/* ---------------------------------------------------------
   Grouping helpers (deterministic; improves when DB adds group columns)
--------------------------------------------------------- */
if (!function_exists('mk_subjects_pick_group_col')) {
  function mk_subjects_pick_group_col(PDO $pdo): ?string {
    $candidates = ['group_name','topic_group','section','category'];
    foreach ($candidates as $c) {
      if (mk_subjects_has_column($pdo, 'pages', $c)) return $c;
    }
    return null;
  }
}

if (!function_exists('mk_subjects_infer_group')) {
  function mk_subjects_infer_group(string $title): string {
    $t = trim($title);
    if ($t === '') return 'Pages';

    // Patterns: "Group: Title", "Group — Title", "Group - Title"
    if (preg_match('/^([^:—\-]{2,42})\s*[:—\-]\s+.+$/u', $t, $m)) {
      $g = trim((string)$m[1]);
      $g = preg_replace('/\s+/u', ' ', $g) ?? $g;
      if ($g !== '' && mb_strlen($g, 'UTF-8') <= 42) return $g;
    }
    return 'Pages';
  }
}

if (!function_exists('mk_subjects_sort_groups')) {
  function mk_subjects_sort_groups(array $groups): array {
    // Put "Pages" last; otherwise natural alpha
    uksort($groups, function($a, $b) {
      $a = (string)$a; $b = (string)$b;
      if ($a === 'Pages' && $b !== 'Pages') return 1;
      if ($b === 'Pages' && $a !== 'Pages') return -1;
      return strnatcasecmp($a, $b);
    });
    return $groups;
  }
}

/* ---------------------------------------------------------
   Load subject + pages (DB-first, registry fallback)
--------------------------------------------------------- */
$subject  = null;
$pages    = [];
$db_error = null;
$used_registry = false;

try {
  if (!function_exists('db')) throw new RuntimeException('db() not available.');
  $pdo = db();
  if (!$pdo instanceof PDO) throw new RuntimeException('DB connection not available.');

  $nameCol = 'name';
  if (!mk_subjects_has_column($pdo, 'subjects', 'name')) {
    if (mk_subjects_has_column($pdo, 'subjects', 'menu_name')) $nameCol = 'menu_name';
    elseif (mk_subjects_has_column($pdo, 'subjects', 'subject_name')) $nameCol = 'subject_name';
    else $nameCol = 'slug';
  }

  $descCol = null;
  if (mk_subjects_has_column($pdo, 'subjects', 'meta_description')) $descCol = 'meta_description';
  elseif (mk_subjects_has_column($pdo, 'subjects', 'short_desc')) $descCol = 'short_desc';
  elseif (mk_subjects_has_column($pdo, 'subjects', 'description')) $descCol = 'description';
  elseif (mk_subjects_has_column($pdo, 'subjects', 'content')) $descCol = 'content';

  $hasIcon = mk_subjects_has_column($pdo, 'subjects', 'icon_path');

  $cols = "id, slug, {$nameCol} AS name";
  if ($descCol) $cols .= ", {$descCol} AS description";
  if ($hasIcon) $cols .= ", icon_path";

  $st = $pdo->prepare("SELECT {$cols} FROM subjects WHERE slug = ? LIMIT 1");
  $st->execute([$subject_slug]);
  $subject = $st->fetch(PDO::FETCH_ASSOC) ?: null;

  if ($subject) {
    $sid = (int)($subject['id'] ?? 0);

    if ($sid > 0) {
      $titleCol = 'menu_name';
      if (!mk_subjects_has_column($pdo, 'pages', 'menu_name')) {
        $titleCol = mk_subjects_has_column($pdo, 'pages', 'title') ? 'title' : 'slug';
      }

      $excerptCol = null;
      if (mk_subjects_has_column($pdo, 'pages', 'body_html')) $excerptCol = 'body_html';
      elseif (mk_subjects_has_column($pdo, 'pages', 'body')) $excerptCol = 'body';
      elseif (mk_subjects_has_column($pdo, 'pages', 'content')) $excerptCol = 'content';

      $groupCol = mk_subjects_pick_group_col($pdo);

      $where = "WHERE subject_id = ?";
      if (mk_subjects_has_column($pdo, 'pages', 'status')) {
        $where .= " AND status IN ('active','published','public')";
      } elseif (mk_subjects_has_column($pdo, 'pages', 'is_public')) {
        $where .= " AND is_public = 1";
      } elseif (mk_subjects_has_column($pdo, 'pages', 'visible')) {
        $where .= " AND visible = 1";
      }

      $orderCol = mk_subjects_has_column($pdo, 'pages', 'nav_order') ? 'nav_order'
               : (mk_subjects_has_column($pdo, 'pages', 'position') ? 'position' : 'id');

      $select = "id, slug, {$titleCol} AS title";
      if ($excerptCol) $select .= ", {$excerptCol} AS excerpt_html";
      if ($groupCol)   $select .= ", {$groupCol} AS group_name";

      $pst = $pdo->prepare("
        SELECT {$select}
        FROM pages
        {$where}
        ORDER BY {$orderCol} IS NULL, {$orderCol} ASC, id ASC
      ");
      $pst->execute([$sid]);
      $pages = $pst->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
  }

} catch (Throwable $e) {
  $db_error = $e->getMessage();
}

/* Registry fallback */
if (!$subject) {
  mk_subjects_load_registry();
  if (function_exists('subject_by_slug_registry')) {
    $r = subject_by_slug_registry($subject_slug);
    if (is_array($r)) {
      $subject = [
        'id'          => (int)($r['id'] ?? 0),
        'slug'        => (string)($r['slug'] ?? $subject_slug),
        'name'        => (string)($r['name'] ?? $subject_slug),
        'description' => (string)($r['description'] ?? ''),
        'icon_path'   => '/lib/images/subjects/' . $subject_slug . '.svg',
      ];
      $used_registry = true;
      $pages = [];
    }
  }
}

if (!$subject) {
  mk_subjects_not_found('Subject not found', 'No subject matched the requested slug: ' . $subject_slug);
}

/* ---------------------------------------------------------
   Page vars BEFORE header
--------------------------------------------------------- */
$brand = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomi Igbo';

$s_slug = (string)($subject['slug'] ?? $subject_slug);
$s_name = trim((string)($subject['name'] ?? ''));
if ($s_name === '') $s_name = $s_slug;

$s_desc = trim((string)($subject['description'] ?? ''));

$page_title = $s_name . ' • Subjects • ' . $brand;
$page_desc  = $s_desc !== '' ? $s_desc : 'Browse pages under this subject.';
$active_nav = 'subjects';
$nav_active = 'subjects';

$extra_css = $GLOBALS['extra_css'] ?? [];
if (!is_array($extra_css)) $extra_css = [];
$extra_css[] = mk_u('/lib/css/subjects.css');
$extra_css[] = mk_u('/lib/css/public.css');
$GLOBALS['extra_css'] = array_values(array_unique($extra_css));

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'active_nav' => $active_nav,
      'nav_active' => $nav_active,
      'extra_css'  => $GLOBALS['extra_css'],
    ]);
  } catch (Throwable $e) {}
} else {
  $GLOBALS['page_title'] = $page_title;
  $GLOBALS['page_desc']  = $page_desc;
  $GLOBALS['active_nav'] = $active_nav;
  $GLOBALS['nav_active'] = $nav_active;
}

/* Header */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}
if (!$header_ok) {
  header('Content-Type: text/html; charset=utf-8');
  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($page_title) . "</title></head><body>";
}

/* URLs */
$home_url     = mk_u('/');
$subjects_url = mk_u('/subjects/');

/* Subject icon */
$icon = trim((string)($subject['icon_path'] ?? ''));
if ($icon === '') $icon = '/lib/images/subjects/' . $s_slug . '.svg';
$icon_url = mk_u($icon);

/* if subject svg missing, fallback */
$doc_root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
if ($doc_root !== '' && strpos($icon, '/') === 0) {
  $abs = $doc_root . $icon;
  if (!is_file($abs)) $icon_url = mk_u('/lib/images/subjects/_subject.svg');
}

/* ---------------------------------------------------------
   Build grouped page index (DB group column -> inferred -> Pages)
--------------------------------------------------------- */
$groups = [];
foreach ($pages as $p) {
  $p_slug = strtolower(trim((string)($p['slug'] ?? '')));
  if ($p_slug === '' || !preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $p_slug)) continue;

  $p_title = trim((string)($p['title'] ?? ''));
  if ($p_title === '') $p_title = $p_slug;

  $g = trim((string)($p['group_name'] ?? ''));
  if ($g === '') $g = mk_subjects_infer_group($p_title);

  $g = preg_replace('/\s+/u', ' ', $g) ?? $g;
  if ($g === '') $g = 'Pages';

  if (!isset($groups[$g])) $groups[$g] = [];
  $groups[$g][] = $p;
}
$groups = mk_subjects_sort_groups($groups);

?>
<?php if ($db_error && !$used_registry): ?>
  <div class="container" style="padding:12px 0;">
    <div class="notice error"><strong>DB Error:</strong> <?= h($db_error) ?></div>
  </div>
<?php elseif ($used_registry): ?>
  <div class="container" style="padding:12px 0;">
    <div class="notice" style="opacity:.9;">Showing registry fallback (DB unavailable or not yet seeded).</div>
  </div>
<?php endif; ?>

<main class="container mk-page">

  <nav class="mk-crumbs" aria-label="Breadcrumb">
    <a href="<?= h($home_url) ?>">Home</a>
    <span class="mk-crumbs__sep">›</span>
    <a href="<?= h($subjects_url) ?>">Subjects</a>
    <span class="mk-crumbs__sep">›</span>
    <span class="mk-crumbs__current"><?= h($s_name) ?></span>
  </nav>

  <header class="mk-hero mk-hero--compact">
    <div class="mk-hero__bar" aria-hidden="true"></div>
    <div class="mk-hero__inner">
      <div class="mk-subject-hero">
        <div class="mk-subject-hero__left">
          <img class="mk-subject-hero__icon"
               src="<?= h($icon_url) ?>"
               alt="<?= h($s_name) ?>"
               width="72" height="72"
               loading="lazy">
          <div class="mk-subject-hero__text">
            <h1 class="mk-hero__title"><?= h($s_name) ?></h1>
            <?php if ($s_desc !== ''): ?>
              <p class="mk-hero__subtitle"><?= h($s_desc) ?></p>
            <?php else: ?>
              <p class="mk-hero__subtitle">Explore pages in this subject.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="mk-subject-hero__right">
          <a class="mk-btn mk-btn--ghost" href="<?= h($subjects_url) ?>">← Back to Subjects</a>
          <a class="mk-btn mk-btn--ghost" href="<?= h($home_url) ?>">Home</a>
        </div>
      </div>
    </div>
  </header>

  <section class="mk-subject-landing" aria-label="Subject landing content">
    <div class="mk-card mk-subject-intro">
      <div class="mk-card__body">
        <h2 class="mk-subject-intro__title">Overview</h2>
        <p class="mk-subject-intro__text">
          <?= h($s_desc !== '' ? $s_desc : 'This subject contains curated pages, media, and references. Use the grouped index below to browse.') ?>
        </p>
      </div>
    </div>

    <?php if (count($pages) === 0): ?>
      <div class="mk-card" style="margin-top:14px;">
        <div class="mk-card__body">
          <p class="mk-muted" style="margin:0;">No pages found under this subject yet.</p>
        </div>
      </div>
    <?php else: ?>

      <div class="mk-card mk-pages-index" style="margin-top:14px;">
        <div class="mk-card__body">
          <div class="mk-pages-index__head">
            <h2 class="mk-pages-index__title">Browse pages</h2>
            <div class="mk-pages-index__meta">
              <span class="mk-pill"><?= (int)count($pages) ?> pages</span>
            </div>
          </div>

          <div class="mk-pages-index__tools">
            <label class="mk-pages-search">
              <span class="mk-pages-search__label">Filter</span>
              <input id="mkPageFilter"
                     class="mk-input mk-pages-search__input"
                     type="search"
                     placeholder="Type to filter pages…"
                     autocomplete="off">
            </label>
            <button type="button" class="mk-btn mk-btn--ghost mk-pages-clear" id="mkPageClear" aria-label="Clear filter">Clear</button>
          </div>

          <div class="mk-ddlist" id="mkPageGroups">
            <?php foreach ($groups as $gname => $list): ?>
              <?php
                $safe_id = 'grp_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($gname));
                $safe_id = trim($safe_id, '_');
                if ($safe_id === 'grp') $safe_id .= '_pages';
              ?>
              <details class="mk-dd" data-group="<?= h($gname) ?>">
                <summary class="mk-dd__summary">
                  <span class="mk-dd__title"><?= h($gname) ?></span>
                  <span class="mk-dd__count"><?= (int)count($list) ?></span>
                </summary>

                <ul class="mk-dd__items" aria-label="<?= h($gname) ?> pages">
                  <?php foreach ($list as $p): ?>
                    <?php
                      $p_slug = strtolower(trim((string)($p['slug'] ?? '')));
                      if ($p_slug === '' || !preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $p_slug)) continue;

                      $p_title = trim((string)($p['title'] ?? ''));
                      if ($p_title === '') $p_title = $p_slug;

                      $pretty = '/subjects/' . rawurlencode($s_slug) . '/' . rawurlencode($p_slug) . '/';
                      $p_href = mk_u($pretty);

                      $excerpt_html = is_string($p['excerpt_html'] ?? null) ? (string)$p['excerpt_html'] : '';
                      $excerpt = mk_excerpt_text($excerpt_html, 140);

                      $cnt = mk_subjects_attachment_count($s_slug, $p_slug);
                      $total = (int)($cnt['total'] ?? 0);
                      $badge = ($total === 1) ? '1 attachment' : ($total . ' attachments');
                    ?>
                    <li class="mk-dd__item" data-title="<?= h(mb_strtolower($p_title, 'UTF-8')) ?>" data-slug="<?= h($p_slug) ?>">
                      <a class="mk-dd__link" href="<?= h($p_href) ?>">
                        <span class="mk-dd__linktitle"><?= h($p_title) ?></span>
                        <span class="mk-dd__slug"><?= h($p_slug) ?></span>
                      </a>

                      <div class="mk-dd__meta">
                        <?php if ($excerpt !== ''): ?>
                          <span class="mk-dd__excerpt"><?= h($excerpt) ?></span>
                        <?php endif; ?>
                        <span class="mk-pill mk-pill--soft"><?= h($badge) ?></span>
                        <?php if (!empty($cnt['external'])): ?>
                          <span class="mk-pill mk-pill--soft"><?= (int)$cnt['external'] ?> external</span>
                        <?php endif; ?>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endforeach; ?>
          </div>

          <p class="mk-muted mk-pages-index__hint">
            Tip: open a group, then filter to quickly find a page by title or slug.
          </p>

        </div>
      </div>

      <script>
      (function(){
        var input = document.getElementById('mkPageFilter');
        var clear = document.getElementById('mkPageClear');
        var root  = document.getElementById('mkPageGroups');
        if (!input || !root) return;

        function norm(s){ return (s||'').toLowerCase().trim(); }

        function apply(){
          var q = norm(input.value);
          var items = root.querySelectorAll('.mk-dd__item');
          var anyVisibleInGroup = new Map();

          items.forEach(function(li){
            var t = li.getAttribute('data-title') || '';
            var s = (li.getAttribute('data-slug') || '').toLowerCase();
            var ok = (q === '') || (t.indexOf(q) !== -1) || (s.indexOf(q) !== -1);
            li.style.display = ok ? '' : 'none';

            var details = li.closest('details.mk-dd');
            if (details) {
              var key = details;
              var prev = anyVisibleInGroup.get(key) || false;
              anyVisibleInGroup.set(key, prev || ok);
            }
          });

          var groups = root.querySelectorAll('details.mk-dd');
          groups.forEach(function(d){
            var ok = anyVisibleInGroup.has(d) ? anyVisibleInGroup.get(d) : true;
            d.style.display = ok ? '' : 'none';
            if (q !== '' && ok) d.open = true;
            if (q === '') d.open = false;
          });
        }

        input.addEventListener('input', apply);
        if (clear) clear.addEventListener('click', function(){ input.value=''; apply(); input.focus(); });

        apply();
      })();
      </script>

    <?php endif; ?>
  </section>

</main>

<?php
if (function_exists('mk_require_shared')) {
  mk_require_shared('public_footer.php');
} else {
  echo "</body></html>";
}
