<?php
declare(strict_types=1);

/**
 * /public/subjects/subject.php
 * Subject landing page (canonical + 5 core cards + "More pages").
 *
 * Route:
 *   /subjects/{slug}/
 *
 * Guarantees:
 * - Never 500 (fatal shutdown handler + safe 404)
 * - DB-first subject metadata
 * - DB-first page listing, FS fallback only
 * - Does NOT execute subject page PHP files
 * - Core cards are ALWAYS the canonical 5 (fixed order):
 *     intro -> overview -> topics -> people -> sources
 * - Optional Sxx-P0y UI code pills for core pages
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* Optional: registry helpers for stable UI codes (Sxx-P0y) */
try {
  if (defined('APP_ROOT')) {
    $regHelpers = rtrim((string)APP_ROOT, "/\\") . '/private/functions/subjects_registry_helpers.php';
    if (is_file($regHelpers)) require_once $regHelpers;
  }
} catch (Throwable $e) {}

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
    exit;
  }
}
if (!function_exists('mk_is_slug')) {
  function mk_is_slug(string $s): bool {
    $s = strtolower(trim($s));
    return $s !== '' && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
  }
}
if (!function_exists('mk_excerpt_text')) {
  function mk_excerpt_text(string $html, int $limit = 160): string {
    $txt = trim(strip_tags($html));
    $txt = preg_replace('/\s+/u', ' ', $txt) ?? $txt;
    if ($txt === '') return '';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
      if (mb_strlen($txt, 'UTF-8') <= $limit) return $txt;
      return rtrim((string)mb_substr($txt, 0, $limit, 'UTF-8')) . '…';
    }
    if (strlen($txt) <= $limit) return $txt;
    return rtrim(substr($txt, 0, $limit)) . '…';
  }
}

/* ---------------------------------------------------------
   Canonical core pages (single source of truth)
--------------------------------------------------------- */
if (!function_exists('mk_subject_core_slugs')) {
  function mk_subject_core_slugs(): array { return ['intro','overview','topics','people','sources']; }
}
if (!function_exists('mk_subject_core_pos')) {
  function mk_subject_core_pos(string $page_slug): int {
    static $m = ['intro'=>1,'overview'=>2,'topics'=>3,'people'=>4,'sources'=>5];
    $k = strtolower(trim($page_slug));
    return (int)($m[$k] ?? 0);
  }
}
if (!function_exists('mk_subject_core_title')) {
  function mk_subject_core_title(string $slug): string {
    $slug = strtolower(trim($slug));
    if ($slug === 'intro') return 'Intro';
    if ($slug === 'overview') return 'Overview';
    if ($slug === 'topics') return 'Topics';
    if ($slug === 'people') return 'People';
    if ($slug === 'sources') return 'Sources';
    return ucfirst(str_replace(['-','_'], ' ', $slug));
  }
}

/* Optional Sxx-P0y code support */
if (!function_exists('mk_subject_registry_id_for_slug')) {
  function mk_subject_registry_id_for_slug(string $subject_slug): int {
    $subject_slug = strtolower(trim($subject_slug));
    if ($subject_slug === '') return 0;

    if (function_exists('mk_registry_subjects_by_slug')) {
      $map = mk_registry_subjects_by_slug();
      $row = $map[$subject_slug] ?? null;
      if (is_array($row) && isset($row['id']) && is_numeric($row['id'])) return (int)$row['id'];
    }
    return 0;
  }
}

/* ---------------------------------------------------------
   Crash-proofing (fatal shutdown handler)
--------------------------------------------------------- */
$req_id = bin2hex(random_bytes(6));
$log_dir  = (defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '') . '/logs';
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
   Safe 404 (never 500)
--------------------------------------------------------- */
if (!function_exists('mk_subjects_not_found')) {
  function mk_subjects_not_found(string $title = 'Not Found', string $message = 'The page you requested does not exist.'): void {
    http_response_code(404);

    $brand = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomigbo';
    $page_title = $title . ' • ' . $brand;

    $GLOBALS['page_title'] = $page_title;
    $GLOBALS['page_desc']  = $message;
    $GLOBALS['active_nav'] = 'subjects';
    $GLOBALS['nav_active'] = 'subjects';

    if (function_exists('mk_view_set')) {
      try {
        mk_view_set([
          'page_title' => $page_title,
          'page_desc'  => $message,
          'active_nav' => 'subjects',
          'nav_active' => 'subjects',
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
      echo "<title>" . h($page_title) . "</title><link rel='stylesheet' href='/assets/css/ui.css'><link rel='stylesheet' href='/assets/css/public.css'><link rel='stylesheet' href='/assets/css/subjects.css'><link rel='stylesheet' href='/assets/css/subjects-public.css'></head><body>"; echo '<header style="background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:20;"><div style="max-width:1200px;margin:0 auto;padding:0 16px;display:flex;align-items:center;justify-content:space-between;min-height:60px;gap:14px;flex-wrap:wrap;"><a href="/" style="display:inline-flex;align-items:center;gap:9px;text-decoration:none;color:inherit;"><img src="/assets/images/logos/mk-logo.png" width="30" height="30" style="border-radius:8px;" alt="Mkomigbo"><strong style="font-size:1rem;">Mkomigbo</strong></a><nav style="display:flex;gap:4px;flex-wrap:wrap;"><a href="/subjects/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:800;font-size:.9rem;color:#0d6efd;border:1px solid rgba(13,110,253,.30);background:rgba(13,110,253,.08);">Subjects</a><a href="/platforms/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Platforms</a><a href="/contributors/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Contributors</a><a href="/awag/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">AWAG</a><a href="/igbo-calendar/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Calendar</a></nav></div></header>';
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
      try { mk_require_shared('public_footer.php'); } catch (Throwable $e) { echo "</body></html>"; }
    } else {
      echo "</body></html>";
    }
    exit;
  }
}

/* ---------------------------------------------------------
   Inputs + canonical enforcement
--------------------------------------------------------- */
$subject_slug = '';
if (isset($_GET['slug']) && is_scalar($_GET['slug'])) $subject_slug = trim((string)$_GET['slug']);
if ($subject_slug === '' && isset($_GET['subject']) && is_scalar($_GET['subject'])) $subject_slug = trim((string)$_GET['subject']);
$subject_slug = strtolower($subject_slug);

if ($subject_slug === '') mk_safe_redirect(mk_u('/subjects/'), 302);
if (!mk_is_slug($subject_slug)) mk_subjects_not_found('Subject not found', 'Invalid subject slug.');

/* Slug governance: redirect alias -> canonical (301) */
if (function_exists('mk_subject_apply_subject_alias_redirect')) {
  mk_subject_apply_subject_alias_redirect($subject_slug, '', 301, '/subjects/');
}

/* Canonical redirect from controller path + normalize missing trailing slash */
$req_path = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
$expected = '/subjects/' . $subject_slug . '/';
if (stripos($req_path, '/subjects/subject.php') !== false || stripos($req_path, '/public/subjects/subject.php') !== false) {
  mk_safe_redirect(mk_u($expected), 301);
}
if ($req_path === rtrim($expected, '/') && $req_path !== $expected) {
  mk_safe_redirect(mk_u($expected), 301);
}

/* ---------------------------------------------------------
   Schema helper (DB)
--------------------------------------------------------- */
if (!function_exists('mk_has_col')) {
  function mk_has_col(PDO $pdo, string $table, string $column): bool {
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

/* ---------------------------------------------------------
   Filesystem index (NO execution)
   We only list available slugs by filename.
--------------------------------------------------------- */
$fs_map = []; // slug => ['slug','title','exists'=>true,'source'=>'fs']
$fs_dir = __DIR__ . '/pages/' . $subject_slug;
$fs_dir_real = is_dir($fs_dir) ? realpath($fs_dir) : false;

if ($fs_dir_real !== false && is_readable($fs_dir_real)) {
  $files = glob(rtrim($fs_dir_real, "/\\") . '/*.php') ?: [];
  foreach ($files as $f) {
    $slug = strtolower(trim((string)basename($f, '.php')));
    if (!mk_is_slug($slug)) continue;

    $title = ucfirst(str_replace(['-','_'], ' ', $slug));
    $fs_map[$slug] = [
      'slug'    => $slug,
      'title'   => $title,
      'exists'  => true,
      'source'  => 'fs',
      'excerpt' => '',
      'sort_order' => 999999,
    ];
  }
}

/* ---------------------------------------------------------
   Load subject + DB pages
--------------------------------------------------------- */
$subject = null;
$db_error = '';
$db_pages_map = []; // slug => merged page meta

try {
  if (!function_exists('db')) throw new RuntimeException('db() not available.');
  $pdo = db();
  if (!$pdo instanceof PDO) throw new RuntimeException('DB connection not available.');

  $subNameCol = mk_has_col($pdo,'subjects','name') ? 'name'
            : (mk_has_col($pdo,'subjects','menu_name') ? 'menu_name'
            : (mk_has_col($pdo,'subjects','subject_name') ? 'subject_name' : 'slug'));

  $subDescCol = mk_has_col($pdo,'subjects','meta_description') ? 'meta_description'
            : (mk_has_col($pdo,'subjects','short_desc') ? 'short_desc'
            : (mk_has_col($pdo,'subjects','description') ? 'description'
            : (mk_has_col($pdo,'subjects','content') ? 'content' : null)));

  $hasIcon = mk_has_col($pdo,'subjects','icon_path');

  $subCols = ['id','slug', "{$subNameCol} AS name"];
  if ($subDescCol) $subCols[] = "{$subDescCol} AS description";
  if ($hasIcon)    $subCols[] = "icon_path";

  $st = $pdo->prepare("SELECT " . implode(', ', $subCols) . " FROM subjects WHERE slug = ? LIMIT 1");
  $st->execute([$subject_slug]);
  $subject = $st->fetch(PDO::FETCH_ASSOC) ?: null;

  if ($subject) {
    $sid = (int)($subject['id'] ?? 0);

    if ($sid > 0) {
      $hasTitle     = mk_has_col($pdo, 'pages', 'title');
      $hasNavLabel  = mk_has_col($pdo, 'pages', 'nav_label');
      $hasMenuName  = mk_has_col($pdo, 'pages', 'menu_name');
      $hasName      = mk_has_col($pdo, 'pages', 'name');
      $hasSlug      = mk_has_col($pdo, 'pages', 'slug');
      $hasBodyHtml  = mk_has_col($pdo, 'pages', 'body_html');
      $hasBody      = mk_has_col($pdo, 'pages', 'body');
      $hasContent   = mk_has_col($pdo, 'pages', 'content');
      $hasSummary   = mk_has_col($pdo, 'pages', 'summary');
      $hasExcerpt   = mk_has_col($pdo, 'pages', 'excerpt');
      $hasIsPublic  = mk_has_col($pdo, 'pages', 'is_public');
      $hasVisible   = mk_has_col($pdo, 'pages', 'visible');
      $hasStatus    = mk_has_col($pdo, 'pages', 'status');
      $hasWF        = mk_has_col($pdo, 'pages', 'workflow_state');
      $hasNavOrder  = mk_has_col($pdo, 'pages', 'nav_order');
      $hasPosition  = mk_has_col($pdo, 'pages', 'position');

      $titleExpr = "'Page'";
      if ($hasTitle && $hasNavLabel) {
        $titleExpr = "COALESCE(NULLIF(title,''), NULLIF(nav_label,''), slug)";
      } elseif ($hasTitle) {
        $titleExpr = "COALESCE(NULLIF(title,''), slug)";
      } elseif ($hasNavLabel) {
        $titleExpr = "COALESCE(NULLIF(nav_label,''), slug)";
      } elseif ($hasMenuName) {
        $titleExpr = "COALESCE(NULLIF(menu_name,''), slug)";
      } elseif ($hasName) {
        $titleExpr = "COALESCE(NULLIF(name,''), slug)";
      } elseif ($hasSlug) {
        $titleExpr = "slug";
      }

      $excerptExpr = "''";
      if ($hasSummary) {
        $excerptExpr = "summary";
      } elseif ($hasExcerpt) {
        $excerptExpr = "excerpt";
      } elseif ($hasBodyHtml) {
        $excerptExpr = "body_html";
      } elseif ($hasBody) {
        $excerptExpr = "body";
      } elseif ($hasContent) {
        $excerptExpr = "content";
      }

      $orderExpr = "999999";
      if ($hasNavOrder) {
        $orderExpr = "COALESCE(nav_order, 999999)";
      } elseif ($hasPosition) {
        $orderExpr = "COALESCE(position, 999999)";
      }

      $cols = [
        "id",
        "slug",
        "{$titleExpr} AS page_title",
        "{$excerptExpr} AS page_excerpt",
        "{$orderExpr} AS sort_order",
      ];

      $where = ["subject_id = ?"];
      $params = [$sid];

      if ($hasSlug) {
        $where[] = "slug IS NOT NULL";
        $where[] = "slug <> ''";
      }

      if ($hasIsPublic) {
        $where[] = "is_public = 1";
      } elseif ($hasVisible) {
        $where[] = "visible = 1";
      }

      if ($hasWF) {
        $where[] = "LOWER(COALESCE(workflow_state,'')) IN ('published','review')";
      } elseif ($hasStatus) {
        $where[] = "LOWER(COALESCE(status,'')) IN ('published','active','public')";
      }

      $orderBy = "sort_order ASC, page_title ASC, slug ASC, id ASC";

      $sql = "SELECT " . implode(', ', $cols) . " FROM pages WHERE " . implode(' AND ', $where) . " ORDER BY {$orderBy}";
      $stp = $pdo->prepare($sql);
      $stp->execute($params);
      $pageRows = $stp->fetchAll(PDO::FETCH_ASSOC) ?: [];

      foreach ($pageRows as $row) {
        $slug = strtolower(trim((string)($row['slug'] ?? '')));
        if (!mk_is_slug($slug)) continue;

        $title = trim((string)($row['page_title'] ?? ''));
        if ($title === '') $title = ucfirst(str_replace(['-','_'], ' ', $slug));

        $excerptRaw = trim((string)($row['page_excerpt'] ?? ''));
        $excerpt = $excerptRaw !== '' ? mk_excerpt_text($excerptRaw, 160) : '';

        $db_pages_map[$slug] = [
          'slug'       => $slug,
          'title'      => $title,
          'exists'     => true,
          'source'     => 'db',
          'excerpt'    => $excerpt,
          'sort_order' => (int)($row['sort_order'] ?? 999999),
        ];
      }
    }
  }

} catch (Throwable $e) {
  $db_error = $e->getMessage();
  $mk_log('DB error: ' . $db_error);
}

if (!$subject) {
  $subject = [
    'id' => 0,
    'slug' => $subject_slug,
    'name' => ucfirst(str_replace(['-','_'], ' ', $subject_slug)),
    'description' => '',
    'icon_path' => '',
  ];
}

/* ---------------------------------------------------------
   Merge DB pages + FS pages
   DB wins for title/excerpt/sort order; FS still supplies existence fallback
--------------------------------------------------------- */
$pages_map = $fs_map;
foreach ($db_pages_map as $slug => $row) {
  if (!isset($pages_map[$slug])) {
    $pages_map[$slug] = $row;
  } else {
    $pages_map[$slug] = array_merge($pages_map[$slug], $row, ['exists' => true]);
  }
}

/* ---------------------------------------------------------
   Build core cards (always 5) + more pages
--------------------------------------------------------- */
$core_cards = [];
foreach (mk_subject_core_slugs() as $core) {
  $exists = isset($pages_map[$core]) && !empty($pages_map[$core]['exists']);

  $title = mk_subject_core_title($core);
  $excerpt = '';

  if ($exists) {
    $t = trim((string)($pages_map[$core]['title'] ?? ''));
    if ($t !== '') $title = $t;

    $ex = trim((string)($pages_map[$core]['excerpt'] ?? ''));
    if ($ex !== '') $excerpt = $ex;
  }

  $core_cards[] = [
    'slug'    => $core,
    'title'   => $title,
    'exists'  => $exists,
    'excerpt' => $excerpt,
  ];
}

$more_pages = [];
foreach ($pages_map as $slug => $row) {
  if (mk_subject_core_pos($slug) > 0) continue;

  $title = trim((string)($row['title'] ?? ''));
  if ($title === '') $title = ucfirst(str_replace(['-','_'], ' ', $slug));

  $more_pages[] = [
    'slug'       => $slug,
    'title'      => $title,
    'excerpt'    => trim((string)($row['excerpt'] ?? '')),
    'sort_order' => (int)($row['sort_order'] ?? 999999),
    'source'     => (string)($row['source'] ?? ''),
  ];
}

usort($more_pages, static function(array $a, array $b): int {
  $ao = (int)($a['sort_order'] ?? 999999);
  $bo = (int)($b['sort_order'] ?? 999999);
  if ($ao !== $bo) return $ao <=> $bo;

  $at = strtolower(trim((string)($a['title'] ?? '')));
  $bt = strtolower(trim((string)($b['title'] ?? '')));
  if ($at !== $bt) return strcmp($at, $bt);

  return strcmp((string)($a['slug'] ?? ''), (string)($b['slug'] ?? ''));
});

/* ---------------------------------------------------------
   SEO + view vars BEFORE header
--------------------------------------------------------- */
$brand = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomigbo';

$s_name = trim((string)($subject['name'] ?? $subject_slug));
if ($s_name === '') $s_name = $subject_slug;

$s_desc = trim((string)($subject['description'] ?? ''));
$canonical_pretty = mk_u('/subjects/' . rawurlencode($subject_slug) . '/');

$seo = [];
if (function_exists('mk_seo_for_subject_slug')) {
  try {
    $tmp = mk_seo_for_subject_slug($subject_slug, ['name' => $s_name, 'description' => $s_desc]);
    if (is_array($tmp)) $seo = $tmp;
  } catch (Throwable $e) {}
}
if (!isset($seo['title']) || !is_string($seo['title']) || trim($seo['title']) === '') {
  $seo['title'] = $s_name . ' • ' . $brand;
}
if (!isset($seo['description']) || !is_string($seo['description']) || trim($seo['description']) === '') {
  $seo['description'] = ($s_desc !== '') ? $s_desc : ('Explore core pages and curated references under ' . $s_name . '.');
}
$seo['canonical'] = (isset($seo['canonical']) && is_string($seo['canonical']) && trim($seo['canonical']) !== '')
  ? (string)$seo['canonical']
  : $canonical_pretty;

$seo['og_type'] = (isset($seo['og_type']) && is_string($seo['og_type']) && trim($seo['og_type']) !== '')
  ? (string)$seo['og_type']
  : 'website';

$GLOBALS['seo']         = $seo;
$GLOBALS['page_title']  = (string)$seo['title'];
$GLOBALS['page_desc']   = (string)$seo['description'];
$GLOBALS['active_nav']  = 'subjects';
$GLOBALS['nav_active']  = 'subjects';

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $GLOBALS['page_title'],
      'page_desc'  => $GLOBALS['page_desc'],
      'active_nav' => 'subjects',
      'nav_active' => 'subjects',
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
  echo "<title>" . h($GLOBALS['page_title']) . "</title><link rel='stylesheet' href='/assets/css/ui.css'><link rel='stylesheet' href='/assets/css/public.css'><link rel='stylesheet' href='/assets/css/subjects.css'><link rel='stylesheet' href='/assets/css/subjects-public.css'></head><body>"; echo '<header style="background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:20;"><div style="max-width:1200px;margin:0 auto;padding:0 16px;display:flex;align-items:center;justify-content:space-between;min-height:60px;gap:14px;flex-wrap:wrap;"><a href="/" style="display:inline-flex;align-items:center;gap:9px;text-decoration:none;color:inherit;"><img src="/assets/images/logos/mk-logo.png" width="30" height="30" style="border-radius:8px;" alt="Mkomigbo"><strong style="font-size:1rem;">Mkomigbo</strong></a><nav style="display:flex;gap:4px;flex-wrap:wrap;"><a href="/subjects/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:800;font-size:.9rem;color:#0d6efd;border:1px solid rgba(13,110,253,.30);background:rgba(13,110,253,.08);">Subjects</a><a href="/platforms/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Platforms</a><a href="/contributors/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Contributors</a><a href="/awag/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">AWAG</a><a href="/igbo-calendar/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Calendar</a></nav></div></header>';
}

/* URLs */
$home_url     = mk_u('/');
$subjects_url = mk_u('/subjects/');
$subject_url  = mk_u('/subjects/' . rawurlencode($subject_slug) . '/');

/* Subject icon (best-effort) */
$icon = trim((string)($subject['icon_path'] ?? ''));
if ($icon === '') $icon = '/assets/images/subjects/' . $subject_slug . '.svg';
$icon_url = mk_u($icon);

$doc_root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
if ($doc_root !== '' && strpos($icon, '/') === 0) {
  $abs = $doc_root . $icon;
  if (!is_file($abs)) $icon_url = mk_u('/assets/images/subjects/_subject.svg');
}

/* UI code switch */
$show_codes = (defined('MK_SHOW_SUBJECT_PAGE_CODES') && MK_SHOW_SUBJECT_PAGE_CODES === true)
  || (isset($_GET['codes']) && (string)$_GET['codes'] === '1');

?>
<?php if ($db_error !== ''): ?>
  <div class="container mk-page">
    <div class="mk-alert mk-alert--danger">
      <strong>DB Error:</strong> <?= h($db_error) ?>
      <div class="mk-muted">Request id: <code><?= h($req_id) ?></code></div>
    </div>
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
          <img
            class="mk-subject-hero__icon"
            src="<?= h($icon_url) ?>"
            alt="<?= h($s_name) ?>"
            width="72"
            height="72"
            loading="lazy"
          >
          <div class="mk-subject-hero__text">
            <h1 class="mk-hero__title"><?= h($s_name) ?></h1>
            <?php if ($s_desc !== ''): ?>
              <p class="mk-hero__subtitle"><?= h($s_desc) ?></p>
            <?php else: ?>
              <p class="mk-hero__subtitle">Explore core pages and curated references in this subject.</p>
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

    <div class="mk-card mk-section">
      <div class="mk-card__body">

        <div class="mk-section-head">
          <h2 class="mk-section-title">Core pages</h2>
          <span class="mk-pill">5</span>
        </div>

        <div class="mk-grid subjects-grid" role="list">
          <?php foreach ($core_cards as $cp): ?>
            <?php
              $p_slug  = (string)$cp['slug'];
              $p_title = trim((string)($cp['title'] ?? $p_slug)) ?: $p_slug;
              $p_url   = mk_u('/subjects/' . rawurlencode($subject_slug) . '/' . rawurlencode($p_slug) . '/');
              $ex      = trim((string)($cp['excerpt'] ?? ''));
              $exists  = (bool)($cp['exists'] ?? false);

              $code = '';
              if ($show_codes) {
                $sid_reg = mk_subject_registry_id_for_slug($subject_slug);
                $pos = mk_subject_core_pos($p_slug);
                if ($sid_reg > 0 && $pos > 0 && function_exists('mk_page_ui_code')) {
                  $code = (string)mk_page_ui_code($sid_reg, $pos);
                }
              }
            ?>

            <?php if ($exists): ?>
              <a class="mk-card subjects-grid__item" href="<?= h($p_url) ?>" role="listitem">
                <div class="mk-card__body">
                  <div class="mk-card__meta">
                    <span class="mk-pill" aria-hidden="true"><?= h(strtoupper(substr($p_slug, 0, 1))) ?></span>
                    <?php if ($code !== ''): ?>
                      <span class="mk-pill mk-pill--soft"><?= h($code) ?></span>
                    <?php endif; ?>
                  </div>

                  <h3 class="mk-h3"><?= h($p_title) ?></h3>

                  <?php if ($ex !== ''): ?>
                    <p class="mk-muted"><?= h($ex) ?></p>
                  <?php else: ?>
                    <p class="mk-muted">Open this core page.</p>
                  <?php endif; ?>
                </div>
              </a>
            <?php else: ?>
              <div class="mk-card subjects-grid__item is-disabled" aria-disabled="true" role="listitem">
                <div class="mk-card__body">
                  <div class="mk-card__meta">
                    <span class="mk-pill" aria-hidden="true"><?= h(strtoupper(substr($p_slug, 0, 1))) ?></span>
                    <?php if ($code !== ''): ?>
                      <span class="mk-pill mk-pill--soft"><?= h($code) ?></span>
                    <?php endif; ?>
                  </div>

                  <h3 class="mk-h3"><?= h($p_title) ?></h3>
                  <p class="mk-muted">Coming soon.</p>
                </div>
              </div>
            <?php endif; ?>

          <?php endforeach; ?>
        </div>

      </div>
    </div>

    <?php if (!empty($more_pages)): ?>
      <div class="mk-card mk-section">
        <div class="mk-card__body">

          <div class="mk-section-head">
            <h2 class="mk-section-title">More pages</h2>
            <span class="mk-pill"><?= (int)count($more_pages) ?></span>
          </div>

          <ul class="mk-aside__list">
            <?php foreach ($more_pages as $mp): ?>
              <?php
                $mp_slug = strtolower(trim((string)($mp['slug'] ?? '')));
                if (!mk_is_slug($mp_slug)) continue;

                $mp_title = trim((string)($mp['title'] ?? ''));
                if ($mp_title === '') $mp_title = $mp_slug;

                $mp_url = mk_u('/subjects/' . rawurlencode($subject_slug) . '/' . rawurlencode($mp_slug) . '/');
              ?>
              <li>
                <a href="<?= h($mp_url) ?>">
                  <?= h($mp_title) ?>
                  <span class="mk-muted mk-aside__meta"><?= h($mp_slug) ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>

        </div>
      </div>
    <?php endif; ?>

  </section>

</main>

<?php
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_footer.php'); } catch (Throwable $e) { echo "</body></html>"; }
} else {
  echo "</body></html>";
}