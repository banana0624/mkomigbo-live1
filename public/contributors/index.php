<?php
declare(strict_types=1);

/**
 * /public/contributors/index.php
 * Public: Contributors landing (premium, schema-tolerant).
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

/* Optional short cache (HTML only) */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && function_exists('mk_public_cache_headers')) {
  mk_public_cache_headers(60);
}

/* Helpers */
if (!function_exists('h')) {
  function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
}
$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

/* Asset versioning */
$asset = static function(string $path) use ($u): string {
  if (function_exists('mk_asset_ver')) {
    try { return (string)mk_asset_ver($path); } catch (Throwable $e) {}
  }

  $url = $u($path);
  $p = parse_url($url, PHP_URL_PATH);
  if (!is_string($p) || $p === '') return $url;

  $docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
  if ($docRoot === '') return $url;

  $file = rtrim($docRoot, '/') . $p;
  if (!is_file($file)) return $url;

  $mtime = @filemtime($file);
  if (!$mtime) return $url;

  $sep = (strpos($url, '?') !== false) ? '&' : '?';
  return $url . $sep . 'v=' . (int)$mtime;
};

/* Schema helpers */
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];
    try {
      $sql = "SELECT 1
              FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
              LIMIT 1";
      $st = $pdo->prepare($sql);
      $st->execute([$table, $column]);
      $cache[$key] = (bool)$st->fetchColumn();
      return (bool)$cache[$key];
    } catch (Throwable $e) {
      $cache[$key] = false;
      return false;
    }
  }
}

if (!function_exists('pf__table_exists')) {
  function pf__table_exists(PDO $pdo, string $table): bool {
    try {
      $st = $pdo->prepare("SELECT 1
                           FROM information_schema.TABLES
                           WHERE TABLE_SCHEMA = DATABASE()
                             AND TABLE_NAME = ?
                           LIMIT 1");
      $st->execute([$table]);
      return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
      return false;
    }
  }
}

/* Page vars */
$brand_name = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomigbo';
$page_title = 'Contributors • ' . $brand_name;
$page_desc  = 'Authors, editors, researchers, and collaborators helping to build and refine the library.';
$nav_active = 'contributors';
$active_nav = 'contributors';

$extra_css = [
  $asset('/lib/css/public.css'),
  $asset('/lib/css/article.css'),
];

$extra_head =
  "<style>\n"
  . ".contributors-page .mk-grid--contributors{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;}\n"
  . ".contributors-page .mk-contrib-card{position:relative;}\n"
  . ".contributors-page .mk-contrib-top{display:flex;gap:12px;align-items:flex-start;}\n"
  . ".contributors-page .mk-contrib-avatar{width:56px;height:56px;border-radius:16px;border:1px solid rgba(0,0,0,.10);background:rgba(0,0,0,.02);display:flex;align-items:center;justify-content:center;font-weight:900;flex:0 0 auto;}\n"
  . ".contributors-page .mk-contrib-sub{font-size:.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}\n"
  . "</style>\n";

/* Globals fallback */
$GLOBALS['page_title'] = $page_title;
$GLOBALS['page_desc']  = $page_desc;
$GLOBALS['nav_active'] = $nav_active;
$GLOBALS['active_nav'] = $active_nav;
$GLOBALS['extra_css']  = $extra_css;
$GLOBALS['extra_head'] = $extra_head;

/* mk_view_set optional */
if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => $nav_active,
      'active_nav' => $active_nav,
      'extra_css'  => $extra_css,
      'extra_head' => $extra_head,
    ]);
  } catch (Throwable $e) {}
}

/* Fetch contributors */
$items = [];
$note  = '';

try {
  $pdo = function_exists('db') ? db() : null;
  if (!$pdo instanceof PDO) throw new RuntimeException('DB not available.');

  if (!pf__table_exists($pdo, 'contributors')) {
    $items = [];
  } else {
    $has_slug   = pf__column_exists($pdo, 'contributors', 'slug');
    $has_status = pf__column_exists($pdo, 'contributors', 'status');
    $has_public = pf__column_exists($pdo, 'contributors', 'is_public');

    $display_col = null;
    foreach (['display_name','name','username','email'] as $c) {
      if (pf__column_exists($pdo, 'contributors', $c)) { $display_col = $c; break; }
    }

    $select = "id";
    $select .= ($display_col !== null) ? ", `{$display_col}` AS display_name" : ", CAST(id AS CHAR) AS display_name";
    $select .= ($has_slug) ? ", `slug` AS slug" : ", NULL AS slug";

    $sql = "SELECT {$select} FROM contributors";
    if ($has_status)     $sql .= " WHERE status = 'active'";
    elseif ($has_public) $sql .= " WHERE is_public = 1";
    $sql .= " ORDER BY id ASC LIMIT 200";

    $items = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
} catch (Throwable $e) {
  $note = $e->getMessage();
  $items = [];
}

/* Render helpers */
if (!function_exists('pf__initial')) {
  function pf__initial(string $name): string {
    $name = trim($name);
    if ($name === '') return 'C';
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
      return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return strtoupper(substr($name, 0, 1));
  }
}
if (!function_exists('pf__profile_href')) {
  function pf__profile_href(array $c): string {
    $id   = (int)($c['id'] ?? 0);
    $slug = trim((string)($c['slug'] ?? ''));
    if ($slug !== '') {
      $path = '/contributors/' . rawurlencode($slug) . '/';
      return function_exists('url_for') ? (string)url_for($path) : $path;
    }
    $path = '/contributors/id/' . rawurlencode((string)$id) . '/';
    return function_exists('url_for') ? (string)url_for($path) : $path;
  }
}

/* Header (prefer contributors_header.php if you have it; else public_header.php) */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('contributors_header.php'); $header_ok = true; } catch (Throwable $e) {}
  if (!$header_ok) {
    try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e2) {}
  }
}
if (!$header_ok) {
  $base = defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, '/') : '';
  $hdr = $base !== '' ? ($base . '/shared/public_header.php') : '';
  if ($hdr !== '' && is_file($hdr)) require_once $hdr;
  else {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
    echo "<title>" . h($page_title) . "</title><link rel='stylesheet' href='/assets/css/ui.css'><link rel='stylesheet' href='/assets/css/public.css'><link rel='stylesheet' href='/assets/css/contributors.css'></head><body>";
    echo '<header style="background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:20;"><div style="max-width:1200px;margin:0 auto;padding:0 16px;display:flex;align-items:center;justify-content:space-between;min-height:60px;gap:14px;flex-wrap:wrap;"><a href="/" style="display:inline-flex;align-items:center;gap:9px;text-decoration:none;color:inherit;"><img src="/assets/images/logos/mk-logo.png" width="30" height="30" style="border-radius:8px;" alt="Mkomigbo"><strong style="font-size:1rem;">Mkomigbo</strong></a><nav style="display:flex;gap:4px;flex-wrap:wrap;"><a href="/subjects/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Subjects</a><a href="/platforms/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Platforms</a><a href="/contributors/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:800;font-size:.9rem;color:#0d6efd;border:1px solid rgba(13,110,253,.30);background:rgba(13,110,253,.08);">Contributors</a><a href="/awag/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">AWAG</a><a href="/igbo-calendar/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Calendar</a></nav></div></header>';
  }
}
?>

<main class="container contributors-page" style="padding:24px 0;">

  <?php if ($note !== ''): ?>
    <div class="mk-alert mk-alert--danger" style="margin:12px 0;">
      <strong>DB Error:</strong> <?= h($note) ?>
    </div>
  <?php endif; ?>

  <header class="mk-hero" style="margin-top:14px;">
    <div class="mk-hero__bar" aria-hidden="true"></div>
    <div class="mk-hero__inner">
      <h1 class="mk-hero__title">Contributors</h1>
      <p class="mk-hero__subtitle" style="max-width:88ch;">
        Authors, editors, researchers, and collaborators helping to build and refine the library.
        Over time, each article will credit its contributors.
      </p>
      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <span class="mk-pill"><?= h((string)count($items)) ?> listed</span>
        <span class="mk-pill">Profiles</span>
        <span class="mk-pill">Credits</span>
      </div>
    </div>
  </header>

  <div style="margin-top:18px;">
    <h2 style="margin:0 0 10px; font-size:1.2rem;">Featured contributors</h2>

    <?php if (!is_array($items) || count($items) === 0): ?>
      <div class="mk-card" style="padding:14px;">
        <div class="mk-muted" style="line-height:1.7;">
          <div style="font-weight:800; color:#111; margin-bottom:6px;">No active contributors yet</div>
          <div>
            This section populates automatically once staff publish contributor profiles
            (set <code>status</code> to <code>active</code> or <code>is_public</code> to <code>1</code>).
          </div>
        </div>

        <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
          <a class="btn" href="<?= h($u('/subjects/')) ?>">Explore Subjects</a>
          <a class="btn btn--ghost" href="<?= h($u('/platforms/')) ?>">Platforms</a>
          <a class="btn btn--ghost" href="<?= h($u('/')) ?>">Home</a>
        </div>
      </div>
    <?php else: ?>
      <section class="mk-grid--contributors">
        <?php foreach ($items as $c): ?>
          <?php
            $name = trim((string)($c['display_name'] ?? 'Contributor'));
            if ($name === '') $name = 'Contributor';

            $href    = pf__profile_href($c);
            $initial = pf__initial($name);

            $rawSlug = trim((string)($c['slug'] ?? ''));
            $id      = (string)($c['id'] ?? '');
            $subline = ($rawSlug !== '') ? $rawSlug : (($id !== '') ? ('id: ' . $id) : 'profile coming soon');
          ?>
          <article class="mk-card mk-contrib-card">
            <div class="mk-card__bar" aria-hidden="true"></div>
            <a class="mk-card__link" href="<?= h($href) ?>">
              <div class="mk-card__body">
                <div class="mk-contrib-top">
                  <div class="mk-contrib-avatar" aria-hidden="true"><?= h($initial) ?></div>
                  <div style="min-width:0;">
                    <h3 style="margin:2px 0 6px; font-size:1.1rem; line-height:1.2;"><?= h($name) ?></h3>
                    <div class="mk-muted mk-contrib-sub"><?= h($subline) ?></div>
                  </div>
                </div>
                <div class="mk-card__meta" style="margin-top:12px;">
                  <span class="mk-pill">Profile</span>
                  <span class="mk-pill">Credits</span>
                </div>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </div>

</main>

<?php
if (function_exists('mk_require_shared')) {
  mk_require_shared('public_footer.php');
} else {
  $base = defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, '/') : '';
  $f = $base !== '' ? ($base . '/shared/public_footer.php') : '';
  if ($f !== '' && is_file($f)) require $f;
  else echo "</body></html>";
}
