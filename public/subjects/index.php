<?php
declare(strict_types=1);

/**
 * /public/subjects/index.php
 * Subjects index (schema-tolerant, premium, registry-backed).
 *
 * Route:
 *   /subjects/ -> this file
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

/* Optional short cache (HTML only) */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && function_exists('mk_public_cache_headers')) {
  mk_public_cache_headers(60);
}

/* ---------------------------------------------------------
   Core helpers (minimal safe fallback)
--------------------------------------------------------- */
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$mk_u = static function (string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

$mk_valid_slug = static function (string $slug): bool {
  $slug = trim($slug);
  return ($slug !== '') && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $slug);
};

$mk_initial = static function (string $name): string {
  $name = trim($name);
  if ($name === '') return 'S';
  if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
    return (string)mb_strtoupper((string)mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
  }
  return strtoupper(substr($name, 0, 1));
};

/* ---------------------------------------------------------
   Asset versioning (busts immutable cache safely)
--------------------------------------------------------- */
$mk_asset_ver = static function (string $urlPath) use ($mk_u): string {
  if (function_exists('mk_asset_ver')) {
    try { return (string)mk_asset_ver($urlPath); } catch (Throwable $e) {}
  }

  $url = $mk_u($urlPath);
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

/* ---------------------------------------------------------
   Page vars (MUST be set before header)
--------------------------------------------------------- */
$brand_name = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomigbo';
$page_title = 'Subjects • ' . $brand_name;
$page_desc  = 'Browse topics and explore their pages.';
$nav_active = 'subjects';
$active_nav = 'subjects';

/* ---------------------------------------------------------
   Module CSS stack (only include what exists)
--------------------------------------------------------- */
$extra_css = [];
$extra_css[] = $mk_asset_ver('/lib/css/public.css');

$docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
$maybeAdd = static function(string $path) use (&$extra_css, $mk_asset_ver, $docRoot): void {
  $p = parse_url($path, PHP_URL_PATH);
  if (!is_string($p) || $p === '') $p = $path;
  if ($docRoot !== '') {
    $file = rtrim($docRoot, '/') . $p;
    if (!is_file($file)) return;
  }
  $extra_css[] = $mk_asset_ver($path);
};

$maybeAdd('/lib/css/subjects-grid.css');
$maybeAdd('/lib/css/subjects-public.css');
$maybeAdd('/lib/css/subjects-mk-bridge.css');
$maybeAdd('/lib/css/subjects.css');

$extra_css = array_values(array_unique(array_filter($extra_css, 'is_string')));

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

$GLOBALS['page_title'] = $page_title;
$GLOBALS['page_desc']  = $page_desc;
$GLOBALS['nav_active'] = $nav_active;
$GLOBALS['active_nav'] = $active_nav;
$GLOBALS['extra_css']  = $extra_css;

/* ---------------------------------------------------------
   Header include (FORCE public_header.php)
--------------------------------------------------------- */
$header_ok = false;

if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}

if (!$header_ok) {
  $base = defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, '/') : '';
  $cand = $base !== '' ? ($base . '/shared/public_header.php') : '';
  if ($cand !== '' && is_file($cand)) { require $cand; $header_ok = true; }
}

if (!$header_ok) {
  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($page_title) . "</title></head><body>";
}

/* ---------------------------------------------------------
   Theme helpers (optional)
--------------------------------------------------------- */
$has_theme = function_exists('pf__accent_for') && function_exists('pf__subject_logo_url');

/* ---------------------------------------------------------
   Registry (preferred fallback for 19 subjects)
--------------------------------------------------------- */
$registry = [];
$registry_file = (defined('APP_ROOT') ? rtrim((string)APP_ROOT, '/') : '') . '/private/registry/subjects_register.php';

if ($registry_file !== '' && is_file($registry_file)) {
  require_once $registry_file;
  if (function_exists('subjects_sorted_registry')) {
    $registry = subjects_sorted_registry();
  } elseif (function_exists('subjects_all_registry')) {
    $registry = subjects_all_registry();
  }
}

/* ---------------------------------------------------------
   DB overlay (schema-tolerant) + icon_path capture
--------------------------------------------------------- */
$db_error = '';
$db_rows_by_slug = [];

try {
  $pdo = function_exists('db') ? db() : null;
  if (!$pdo instanceof PDO) throw new RuntimeException('DB not available.');

  $base = $pdo->query("SELECT id, slug FROM subjects WHERE slug IS NOT NULL AND slug <> ''");
  $baseRows = $base ? ($base->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

  $ids = [];
  foreach ($baseRows as $r) {
    $slug = strtolower(trim((string)($r['slug'] ?? '')));
    if (!$mk_valid_slug($slug)) continue;
    $id = (int)($r['id'] ?? 0);
    if ($id <= 0) continue;

    $ids[] = $id;
    $db_rows_by_slug[$slug] = ['id' => $id, 'slug' => $slug];
  }

  if ($ids) {
    $try_col = static function (PDO $pdo, array $ids, string $col): array {
      $ph = implode(',', array_fill(0, count($ids), '?'));
      $sql = "SELECT id, {$col} AS v FROM subjects WHERE id IN ({$ph})";
      $st = $pdo->prepare($sql);
      $st->execute($ids);
      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    };

    // name
    $nameMap = [];
    foreach (['name', 'menu_name', 'subject_name'] as $c) {
      try {
        $rows = $try_col($pdo, $ids, $c);
        foreach ($rows as $rr) {
          $id = (int)($rr['id'] ?? 0);
          $v  = trim((string)($rr['v'] ?? ''));
          if ($id > 0 && $v !== '') $nameMap[$id] = $v;
        }
        if ($nameMap) break;
      } catch (Throwable $e) {}
    }

    // description
    $descMap = [];
    foreach (['meta_description', 'short_desc', 'description', 'content'] as $c) {
      try {
        $rows = $try_col($pdo, $ids, $c);
        $any = false;
        foreach ($rows as $rr) {
          $id = (int)($rr['id'] ?? 0);
          $v  = trim((string)($rr['v'] ?? ''));
          if ($id > 0 && $v !== '') { $descMap[$id] = $v; $any = true; }
        }
        if ($any) break;
      } catch (Throwable $e) {}
    }

    // icon_path
    $iconMap = [];
    try {
      $rows = $try_col($pdo, $ids, 'icon_path');
      foreach ($rows as $rr) {
        $id = (int)($rr['id'] ?? 0);
        $v  = trim((string)($rr['v'] ?? ''));
        if ($id > 0 && $v !== '') $iconMap[$id] = $v;
      }
    } catch (Throwable $e) {}

    // order
    $orderMap = [];
    foreach (['nav_order', 'position'] as $c) {
      try {
        $rows = $try_col($pdo, $ids, $c);
        $any = false;
        foreach ($rows as $rr) {
          $id = (int)($rr['id'] ?? 0);
          $v  = $rr['v'] ?? null;
          if ($id > 0 && $v !== null && $v !== '') { $orderMap[$id] = (int)$v; $any = true; }
        }
        if ($any) break;
      } catch (Throwable $e) {}
    }

    // visibility
    $visibleIds = array_fill_keys($ids, true);

    $applyBoolMap = static function (array &$visibleIds, array $tmp): void {
      foreach ($visibleIds as $id => $_) {
        if (array_key_exists($id, $tmp)) $visibleIds[$id] = (bool)$tmp[$id];
      }
    };

    try {
      $rows = $try_col($pdo, $ids, 'status');
      $tmp = [];
      foreach ($rows as $rr) {
        $id = (int)($rr['id'] ?? 0);
        $v  = strtolower(trim((string)($rr['v'] ?? '')));
        if ($id > 0) $tmp[$id] = in_array($v, ['active','published','public'], true);
      }
      if ($tmp) $applyBoolMap($visibleIds, $tmp);
    } catch (Throwable $e) {
      try {
        $rows = $try_col($pdo, $ids, 'is_public');
        $tmp = [];
        foreach ($rows as $rr) {
          $id = (int)($rr['id'] ?? 0);
          $v  = $rr['v'] ?? null;
          if ($id > 0) $tmp[$id] = ((int)$v === 1);
        }
        if ($tmp) $applyBoolMap($visibleIds, $tmp);
      } catch (Throwable $e2) {
        try {
          $rows = $try_col($pdo, $ids, 'visible');
          $tmp = [];
          foreach ($rows as $rr) {
            $id = (int)($rr['id'] ?? 0);
            $v  = $rr['v'] ?? null;
            if ($id > 0) $tmp[$id] = ((int)$v === 1);
          }
          if ($tmp) $applyBoolMap($visibleIds, $tmp);
        } catch (Throwable $e3) {}
      }
    }

    foreach ($db_rows_by_slug as $slug => $row) {
      $id = (int)($row['id'] ?? 0);
      if ($id <= 0) continue;

      $db_rows_by_slug[$slug]['name']        = $nameMap[$id] ?? $slug;
      $db_rows_by_slug[$slug]['description'] = $descMap[$id] ?? '';
      $db_rows_by_slug[$slug]['icon_path']   = $iconMap[$id] ?? '';
      $db_rows_by_slug[$slug]['nav_order']   = $orderMap[$id] ?? null;
      $db_rows_by_slug[$slug]['is_visible']  = $visibleIds[$id] ?? true;
    }
  }
} catch (Throwable $e) {
  $db_error = $e->getMessage();
  $db_rows_by_slug = [];
}

/* ---------------------------------------------------------
   Build final list
--------------------------------------------------------- */
$final = [];

if (is_array($registry) && $registry) {
  foreach ($registry as $r) {
    if (!is_array($r)) continue;

    $slug = strtolower(trim((string)($r['slug'] ?? '')));
    if (!$mk_valid_slug($slug)) continue;

    $row = [
      'id'          => (int)($r['id'] ?? 0),
      'slug'        => $slug,
      'name'        => trim((string)($r['name'] ?? '')) ?: $slug,
      'description' => trim((string)($r['meta_description'] ?? '')),
      'nav_order'   => isset($r['nav_order']) ? (int)$r['nav_order'] : PHP_INT_MAX,
      'icon_path'   => '',
    ];

    if (isset($db_rows_by_slug[$slug])) {
      $dbrow = $db_rows_by_slug[$slug];
      if (array_key_exists('is_visible', $dbrow) && $dbrow['is_visible'] === false) continue;

      $row['id']          = (int)($dbrow['id'] ?? $row['id']);
      $row['name']        = trim((string)($dbrow['name'] ?? '')) ?: $row['name'];
      $row['description'] = trim((string)($dbrow['description'] ?? '')) ?: $row['description'];
      $row['icon_path']   = trim((string)($dbrow['icon_path'] ?? ''));

      if (isset($dbrow['nav_order']) && $dbrow['nav_order'] !== null) {
        $row['nav_order'] = (int)$dbrow['nav_order'];
      }
    }

    $final[] = $row;
  }
} else {
  foreach ($db_rows_by_slug as $slug => $dbrow) {
    if (!$mk_valid_slug($slug)) continue;
    if (array_key_exists('is_visible', $dbrow) && $dbrow['is_visible'] === false) continue;

    $final[] = [
      'id'          => (int)($dbrow['id'] ?? 0),
      'slug'        => $slug,
      'name'        => trim((string)($dbrow['name'] ?? '')) ?: $slug,
      'description' => trim((string)($dbrow['description'] ?? '')),
      'nav_order'   => isset($dbrow['nav_order']) && $dbrow['nav_order'] !== null ? (int)$dbrow['nav_order'] : PHP_INT_MAX,
      'icon_path'   => trim((string)($dbrow['icon_path'] ?? '')),
    ];
  }
}

usort($final, static function (array $a, array $b): int {
  $na = (int)($a['nav_order'] ?? PHP_INT_MAX);
  $nb = (int)($b['nav_order'] ?? PHP_INT_MAX);
  if ($na === $nb) return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
  return $na <=> $nb;
});
?>

<div class="subjects-page">

<?php if ($db_error !== ''): ?>
  <div class="mk-alert mk-alert--danger" style="margin:12px 0;">
    <strong>DB Error:</strong> <?= h($db_error) ?>
  </div>
<?php endif; ?>

<header class="mk-hero" style="margin-top:14px;">
  <div class="mk-hero__bar" aria-hidden="true"></div>
  <div class="mk-hero__inner">
    <h1 class="mk-hero__title">Subjects</h1>
    <p class="mk-hero__subtitle">Browse topics and explore their pages.</p>
  </div>
</header>

<section class="mk-grid mk-grid--subjects" style="margin-top:16px;">
  <?php if (!$final): ?>
    <div class="mk-card" style="padding:14px;">
      <p class="mk-muted" style="margin:0;">No public subjects available yet.</p>
    </div>
  <?php else: ?>
    <?php foreach ($final as $s): ?>
      <?php
        $slug = strtolower(trim((string)($s['slug'] ?? '')));
        if (!$mk_valid_slug($slug)) continue;

        $name = trim((string)($s['name'] ?? ''));
        if ($name === '') $name = $slug;

        $desc = trim((string)($s['description'] ?? ''));

        $accent = $has_theme ? (string)pf__accent_for($slug) : '#0d6efd';
        if ($accent === '') $accent = '#0d6efd';

        // icon selection: DB icon_path -> theme -> default svg
        $icon = trim((string)($s['icon_path'] ?? ''));
        if ($icon === '' && $has_theme) $icon = (string)pf__subject_logo_url($slug);
        if ($icon === '') $icon = '/lib/images/subjects/' . $slug . '.svg';

        $icon_url = $mk_u($icon);
        $href     = $mk_u('/subjects/' . rawurlencode($slug) . '/');
        $initial  = $mk_initial($name);
      ?>
      <article class="mk-card mk-subject-card" style="--accent: <?= h($accent) ?>;">
        <div class="mk-card__bar" aria-hidden="true"></div>

        <a class="mk-card__link" href="<?= h($href) ?>">
          <div class="mk-card__body">
            <div class="mk-subject-card__top">
              <div class="mk-subject-card__logo" aria-hidden="true">
                <img src="<?= h($icon_url) ?>"
                     alt=""
                     loading="lazy"
                     width="64" height="64"
                     onerror="this.style.display='none';this.parentNode.innerHTML='<span class=&quot;mk-subject-card__initial&quot;><?= h($initial) ?></span>';"
                     style="display:block;width:64px;height:64px;border-radius:14px;object-fit:cover;">
              </div>

              <div class="mk-subject-card__titlewrap">
                <h2 class="mk-subject-card__title"><?= h($name) ?></h2>
                <div class="mk-subject-card__slug mk-muted"><?= h($slug) ?></div>
              </div>
            </div>

            <?php if ($desc !== ''): ?>
              <p class="mk-muted" style="margin-top:10px;"><?= h($desc) ?></p>
            <?php endif; ?>

            <div class="mk-card__meta" style="margin-top:12px;">
              <span class="mk-pill">Explore</span>
            </div>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

</div>

<?php
if (function_exists('mk_require_shared')) {
  mk_require_shared('public_footer.php');
} else {
  $base = defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, '/') : '';
  $f = $base !== '' ? ($base . '/shared/public_footer.php') : '';
  if ($f !== '' && is_file($f)) require $f;
  else echo "</body></html>";
}
