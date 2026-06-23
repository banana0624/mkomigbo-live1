<?php
declare(strict_types=1);

/**
 * /public/subjects/index.php
 * Subjects index (registry-backed, DB-overlay optional, never-empty).
 *
 * Route:
 *   /subjects/ -> this file
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';
require_once "/home/mkomigbo/repos/releases/2026-04-25-120559/app/mkomigbo/private/functions/public_subjects_list.php";


/* Optional short cache (HTML only) */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && function_exists('mk_public_cache_headers')) {
  try { mk_public_cache_headers(60); } catch (Throwable $e) {}
}

/* ---------------------------------------------------------
   Core helpers
--------------------------------------------------------- */
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$mk_u = static function (string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

$mk_valid_slug = static function (string $slug): bool {
  $slug = strtolower(trim($slug));
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
   Preferred: shared helper (single source of truth)
--------------------------------------------------------- */
$final = [];
$helper_error = '';

try {
  if (defined('APP_ROOT')) {
    $p = rtrim((string)APP_ROOT, "/\\") . '/private/functions/public_subjects_list.php';
    if (is_file($p)) {
      /** @noinspection PhpIncludeInspection */
      require_once $p;
    }
  }
} catch (Throwable $e) {
  $helper_error = 'Helper include failed: ' . $e->getMessage();
}

/* Real deployment fallback include (your filesystem layout) */
if (!function_exists('mk_public_subjects_list')) {
  try {
    $p = __DIR__ . '/../../app/mkomigbo/private/functions/public_subjects_list.php';
    if (is_file($p)) {
      /** @noinspection PhpIncludeInspection */
      require_once $p;
    }
  } catch (Throwable $e) {
    if ($helper_error === '') $helper_error = 'Helper include failed: ' . $e->getMessage();
  }
}

if (function_exists('mk_public_subjects_list')) {
  try {
    $tmp = mk_public_subjects_list();
    if (is_array($tmp) && $tmp) $final = $tmp;
  } catch (Throwable $e) {
    $helper_error = 'Helper run failed: ' . $e->getMessage();
    $final = [];
  }
}

/* ---------------------------------------------------------
   Fallback path: local registry + optional DB overlay
   (keeps your old behavior, but only used if helper missing)
--------------------------------------------------------- */
$registryError = '';
$db_error = '';

if (!$final) {

  $mk_hardcoded_registry_20 = static function(): array {
    return [
      ['id'=>1,  'slug'=>'history',      'name'=>'History'],
      ['id'=>2,  'slug'=>'slavery',      'name'=>'Slavery'],
      ['id'=>3,  'slug'=>'people',       'name'=>'People'],
      ['id'=>4,  'slug'=>'persons',      'name'=>'Persons'],
      ['id'=>5,  'slug'=>'culture',      'name'=>'Culture'],
      ['id'=>6,  'slug'=>'religion',     'name'=>'Religion'],
      ['id'=>7,  'slug'=>'esoterism',    'name'=>'Esoterism'],
      ['id'=>8,  'slug'=>'tradition',    'name'=>'Tradition'],
      ['id'=>9,  'slug'=>'language1',    'name'=>'Language1'],
      ['id'=>10, 'slug'=>'language2',    'name'=>'Language2'],
      ['id'=>11, 'slug'=>'struggles',    'name'=>'Struggles'],
      ['id'=>12, 'slug'=>'biafra',       'name'=>'Biafra'],
      ['id'=>13, 'slug'=>'nigeria',      'name'=>'Nigeria'],
      ['id'=>14, 'slug'=>'resistance',   'name'=>'Resistance'],
      ['id'=>15, 'slug'=>'africa',       'name'=>'Africa'],
      ['id'=>16, 'slug'=>'uk',           'name'=>'UK'],
      ['id'=>17, 'slug'=>'europe',       'name'=>'Europe'],
      ['id'=>18, 'slug'=>'arabs',        'name'=>'Arabs'],
      ['id'=>19, 'slug'=>'about',        'name'=>'About'],
      ['id'=>20, 'slug'=>'pogrom',       'name'=>'Pogrom'],
    ];
  };

  /* Load registry helpers + registry file best-effort */
  try {
    $helpersFile = '';
    if (defined('APP_ROOT')) {
      $cand = rtrim((string)APP_ROOT, "/\\") . '/private/functions/subjects_registry_helpers.php';
      if (is_file($cand)) $helpersFile = $cand;
    }
    if ($helpersFile === '') {
      $cand = __DIR__ . '/../../app/mkomigbo/private/functions/subjects_registry_helpers.php';
      if (is_file($cand)) $helpersFile = $cand;
    }
    if ($helpersFile !== '') {
      /** @noinspection PhpIncludeInspection */
      require_once $helpersFile;
    }

    $regFile = '';
    if (defined('APP_ROOT')) {
      $cand = rtrim((string)APP_ROOT, "/\\") . '/private/registry/subjects_register.php';
      if (is_file($cand)) $regFile = $cand;
    }
    if ($regFile === '') {
      $cand = __DIR__ . '/../../app/mkomigbo/private/registry/subjects_register.php';
      if (is_file($cand)) $regFile = $cand;
    }

    if ($regFile !== '') {
      /** @noinspection PhpIncludeInspection */
      require_once $regFile;
    } else {
      $registryError = 'Registry file not found: subjects_register.php';
    }
  } catch (Throwable $e) {
    $registryError = 'Registry load failed: ' . $e->getMessage();
  }

  /* Pull registry list (ID order must win) */
  $registry_subjects = [];
  try {
    if (function_exists('mk_subjects_registry_sorted')) {
      $tmp = mk_subjects_registry_sorted();
      if (is_array($tmp)) $registry_subjects = $tmp;
    } elseif (function_exists('subjects_sorted_registry')) {
      $tmp = subjects_sorted_registry();
      if (is_array($tmp)) $registry_subjects = $tmp;
    } elseif (function_exists('subjects_all_registry')) {
      $tmp = subjects_all_registry();
      if (is_array($tmp)) $registry_subjects = array_values($tmp);
    }
  } catch (Throwable $e) {
    $registryError = ($registryError !== '' ? $registryError . ' | ' : '') . 'Registry function failed: ' . $e->getMessage();
  }
  $registry_subjects = array_values(array_filter((array)$registry_subjects, static fn($r): bool => is_array($r)));
  if (!$registry_subjects) $registry_subjects = $mk_hardcoded_registry_20();

  /* DB overlay (optional) */
  $db_by_slug = [];
  try {
    $pdo = function_exists('db') ? db() : null;
    if ($pdo instanceof PDO) {
      $st = $pdo->query("SELECT id, slug FROM subjects WHERE slug IS NOT NULL AND slug <> ''");
      $baseRows = $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

      $ids = [];
      foreach ($baseRows as $r) {
        $slug = strtolower(trim((string)($r['slug'] ?? '')));
        if (!$mk_valid_slug($slug)) continue;
        $id = (int)($r['id'] ?? 0);
        if ($id <= 0) continue;
        $ids[] = $id;
        $db_by_slug[$slug] = ['id' => $id, 'slug' => $slug];
      }

      if ($ids) {
        $try_col = static function (PDO $pdo, array $ids, string $col): array {
          $ph = implode(',', array_fill(0, count($ids), '?'));
          $sql = "SELECT id, {$col} AS v FROM subjects WHERE id IN ({$ph})";
          $st = $pdo->prepare($sql);
          $st->execute($ids);
          return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        };

        $nameMap = [];
        foreach (['name', 'menu_name', 'subject_name'] as $c) {
          try {
            foreach ($try_col($pdo, $ids, $c) as $rr) {
              $id = (int)($rr['id'] ?? 0);
              $v  = trim((string)($rr['v'] ?? ''));
              if ($id > 0 && $v !== '') $nameMap[$id] = $v;
            }
            if ($nameMap) break;
          } catch (Throwable $e) {}
        }

        $descMap = [];
        foreach (['meta_description', 'short_desc', 'description', 'content'] as $c) {
          try {
            $any = false;
            foreach ($try_col($pdo, $ids, $c) as $rr) {
              $id = (int)($rr['id'] ?? 0);
              $v  = trim((string)($rr['v'] ?? ''));
              if ($id > 0 && $v !== '') { $descMap[$id] = $v; $any = true; }
            }
            if ($any) break;
          } catch (Throwable $e) {}
        }

        $iconMap = [];
        foreach (['icon_path', 'icon', 'logo', 'logo_path'] as $c) {
          try {
            foreach ($try_col($pdo, $ids, $c) as $rr) {
              $id = (int)($rr['id'] ?? 0);
              $v  = trim((string)($rr['v'] ?? ''));
              if ($id > 0 && $v !== '') $iconMap[$id] = $v;
            }
            if ($iconMap) break;
          } catch (Throwable $e) {}
        }

        $visibleIds = array_fill_keys($ids, true);
        try {
          foreach ($try_col($pdo, $ids, 'status') as $rr) {
            $id = (int)($rr['id'] ?? 0);
            $v  = strtolower(trim((string)($rr['v'] ?? '')));
            if ($id > 0) $visibleIds[$id] = in_array($v, ['active','published','public'], true);
          }
        } catch (Throwable $e) {
          try {
            foreach ($try_col($pdo, $ids, 'is_public') as $rr) {
              $id = (int)($rr['id'] ?? 0);
              $v  = $rr['v'] ?? null;
              if ($id > 0) $visibleIds[$id] = ((int)$v === 1);
            }
          } catch (Throwable $e2) {}
        }

        foreach ($db_by_slug as $slug => $row) {
          $id = (int)($row['id'] ?? 0);
          if ($id <= 0) continue;
          $db_by_slug[$slug]['name']        = $nameMap[$id] ?? '';
          $db_by_slug[$slug]['description'] = $descMap[$id] ?? '';
          $db_by_slug[$slug]['icon_path']   = $iconMap[$id] ?? '';
          $db_by_slug[$slug]['is_visible']  = $visibleIds[$id] ?? true;
        }
      }
    }
  } catch (Throwable $e) {
    $db_error = $e->getMessage();
    $db_by_slug = [];
  }

  /* Build final list: registry order FINAL */
  foreach ($registry_subjects as $r) {
    if (!is_array($r)) continue;

    $slug = strtolower(trim((string)($r['slug'] ?? '')));
    if (!$mk_valid_slug($slug)) continue;

    $registry_id = (int)($r['id'] ?? 0);

    $name = trim((string)($r['name'] ?? ''));
    if ($name === '') $name = $slug;

    $desc = '';
    if (isset($r['description']) && is_string($r['description'])) $desc = trim($r['description']);
    elseif (isset($r['meta_description']) && is_string($r['meta_description'])) $desc = trim($r['meta_description']);

    $row = [
      'registry_id' => $registry_id,
      'db_id'       => 0,
      'slug'        => $slug,
      'name'        => $name,
      'description' => $desc,
      'icon_path'   => trim((string)($r['icon'] ?? '')),
    ];

    if (isset($db_by_slug[$slug])) {
      $dbrow = $db_by_slug[$slug];

      if (array_key_exists('is_visible', $dbrow) && $dbrow['is_visible'] === false) continue;

      $dbId = (int)($dbrow['id'] ?? 0);
      if ($dbId > 0) $row['db_id'] = $dbId;

      $dbName = trim((string)($dbrow['name'] ?? ''));
      if ($dbName !== '') $row['name'] = $dbName;

      $dbDesc = trim((string)($dbrow['description'] ?? ''));
      if ($dbDesc !== '') $row['description'] = $dbDesc;

      $dbIcon = trim((string)($dbrow['icon_path'] ?? ''));
      if ($dbIcon !== '') $row['icon_path'] = $dbIcon;
    }

    $final[] = $row;
  }

  if (!$final) {
    foreach ($mk_hardcoded_registry_20() as $x) {
      $final[] = [
        'registry_id' => (int)($x['id'] ?? 0),
        'db_id'       => 0,
        'slug'        => (string)($x['slug'] ?? ''),
        'name'        => (string)($x['name'] ?? ''),
        'description' => '',
        'icon_path'   => '',
      ];
    }
  }
}

/* ---------------------------------------------------------
   Page vars (BEFORE header)
--------------------------------------------------------- */
$brand_name = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomigbo';
$brand_name = trim($brand_name) !== '' ? trim($brand_name) : 'Mkomigbo';

$page_title = 'Subjects • ' . $brand_name;
$page_desc  = 'Browse topics and explore their pages.';
$nav_active = 'subjects';
$active_nav = 'subjects';
$extra_css  = [];

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => $nav_active,
      'active_nav' => $active_nav,
      'extra_css'  => $extra_css,
    ]);
  } catch (Throwable $e) {}
}

$GLOBALS['page_title'] = $page_title;
$GLOBALS['page_desc']  = $page_desc;
$GLOBALS['nav_active'] = $nav_active;
$GLOBALS['active_nav'] = $active_nav;
$GLOBALS['extra_css']  = $extra_css;

/* Header */
$header_ok = false;
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_header.php'); $header_ok = true; } catch (Throwable $e) {}
}
if (!$header_ok) {
  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($page_title) . "</title><link rel='stylesheet' href='/assets/css/ui.css'><link rel='stylesheet' href='/assets/css/public.css'><link rel='stylesheet' href='/assets/css/subjects.css'><link rel='stylesheet' href='/assets/css/subjects-public.css'></head><body>"; echo '<header style="background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:20;"><div style="max-width:1200px;margin:0 auto;padding:0 16px;display:flex;align-items:center;justify-content:space-between;min-height:60px;gap:14px;"><a href="/" style="display:inline-flex;align-items:center;gap:9px;text-decoration:none;color:inherit;"><img src="/assets/images/logos/mk-logo.png" width="30" height="30" style="border-radius:8px;" alt="Mkomigbo"><strong style="font-size:1rem;">Mkomigbo</strong></a><nav style="display:flex;gap:4px;flex-wrap:wrap;"><a href="/subjects/" style="padding:7px 14px;border-radius:9px;font-weight:800;font-size:.9rem;color:#0d6efd;border:1px solid rgba(13,110,253,.3);background:rgba(13,110,253,.08);">Subjects</a><a href="/platforms/" style="padding:7px 14px;border-radius:9px;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Platforms</a><a href="/contributors/" style="padding:7px 14px;border-radius:9px;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Contributors</a><a href="/awag/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">AWAG</a><a href="/igbo-calendar/" style="padding:7px 14px;border-radius:9px;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Calendar</a></nav></div></header>';
}

/* Optional theme hooks */
require_once '/home/mkomigbo/repos/releases/2026-04-25-120559/private/functions/theme_functions.php';
$has_accent = function_exists('pf__accent_for');

?>
<div class="subjects-page">

<?php if ($helper_error !== ''): ?>
  <div class="mk-alert mk-alert--warning" style="margin:12px 0;">
    <strong>Helper:</strong> <?= h($helper_error) ?>
  </div>
<?php endif; ?>

<?php if ($registryError !== ''): ?>
  <div class="mk-alert mk-alert--warning" style="margin:12px 0;">
    <strong>Registry:</strong> <?= h($registryError) ?>
  </div>
<?php endif; ?>

<?php if ($db_error !== ''): ?>
  <div class="mk-alert mk-alert--danger" style="margin:12px 0;">
    <strong>DB Error:</strong> <?= h($db_error) ?>
  </div>
<?php endif; ?>

<header class="mk-hero" style="margin-top:14px;">
  <div class="mk-hero__bar" aria-hidden="true"></div>
  <div class="mk-hero__inner">
    <h1 class="mk-hero__title">Knowledge Library</h1>
    <p class="mk-hero__subtitle">20 subject areas · 137+ pages · thesis-level scholarship · free access to source texts · cross-linked throughout</p>
  </div>
</header>

<section class="mk-grid mk-grid--subjects" style="margin-top:16px;">
  <?php foreach ($final as $s): ?>
    <?php
      $slug = strtolower(trim((string)($s['slug'] ?? '')));
      if (!$mk_valid_slug($slug)) continue;

      $registry_id = (int)($s['registry_id'] ?? 0);
      $code = ($registry_id > 0 && function_exists('mk_subject_ui_code'))
        ? (string)mk_subject_ui_code($registry_id)
        : (($registry_id > 0) ? ('S' . str_pad((string)$registry_id, 2, '0', STR_PAD_LEFT)) : '');

      $name = trim((string)($s['name'] ?? ''));
      if ($name === '') $name = $slug;

      $desc = trim((string)($s['description'] ?? ''));

      $accent = $has_accent ? (string)pf__accent_for($slug) : '';
      if (trim($accent) === '') $accent = '#0d6efd';

      $icon = trim((string)($s['icon_path'] ?? ''));
      if ($icon === '' && function_exists('subject_logo_webpath')) {
        try { $icon = (string)subject_logo_webpath($slug); } catch (Throwable $e) {}
      }
      if ($icon === '' && function_exists('pf__subject_logo_url')) {
        try { $icon = (string)pf__subject_logo_url($slug); } catch (Throwable $e) {}
      }
      if ($icon === '') $icon = '/lib/images/subjects/' . $slug . '.svg';
      if ($icon === '') $icon = '/lib/images/logos/mk-logo.png';

      $icon_url = $mk_u($icon);
      $href     = $mk_u('/subjects/' . rawurlencode($slug) . '/');

      $ini = $mk_initial($name);
      $ini_js = json_encode($ini, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?>
    <article class="mk-card mk-subject-card" style="--accent: <?= h($accent) ?>;">
      <div class="mk-card__bar" aria-hidden="true" style="background:<?= htmlspecialchars($accent,ENT_QUOTES) ?>;height:10px;width:100%;display:block;"></div>

      <a class="mk-card__link" href="<?= h($href) ?>">
        <div class="mk-card__body">
          <div class="mk-subject-card__top">
            <div class="mk-subject-card__logo" aria-hidden="true">
              <img
                src="<?= h($icon_url) ?>"
                alt=""
                loading="lazy"
                width="64"
                height="64"
                style="display:block;width:64px;height:64px;border-radius:14px;object-fit:cover;"
                onerror="this.onerror=null;this.style.display='none';var p=this.parentNode;if(p){var sp=document.createElement('span');sp.className='mk-subject-card__initial';sp.textContent=<?= $ini_js ?>;p.appendChild(sp);}">
            </div>

            <div class="mk-subject-card__titlewrap">
              <h2 class="mk-subject-card__title">
                <?= h($name) ?>
                <?php if ($code !== ''): ?>
                  <span class="mk-pill" style="margin-left:8px; font-size:.85em; opacity:.85;"><?= h($code) ?></span>
                <?php endif; ?>
              </h2>
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
</section>

</div>

<?php
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('public_footer.php'); } catch (Throwable $e) { echo "</body></html>"; }
} else {
  echo "</body></html>";
}
