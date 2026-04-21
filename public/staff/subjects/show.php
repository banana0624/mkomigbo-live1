<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
mk_require_staff_login();

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('url_for')) {
  function url_for(string $path): string { return '/' . ltrim($path, '/'); }
}
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, 302);
    exit;
  }
}
if (!function_exists('pf__safe_return_url')) {
  function pf__safe_return_url(string $raw, string $default): string {
    $raw = trim($raw);
    if ($raw === '') return $default;
    $raw = rawurldecode($raw);
    if ($raw === '' || $raw[0] !== '/') return $default;
    if (preg_match('~^//~', $raw)) return $default;
    if (preg_match('~^[a-z]+:~i', $raw)) return $default;
    if (strpos($raw, '/staff/') !== 0) return $default;
    return $raw;
  }
}
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
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
    } catch (Throwable $e) {
      $cache[$key] = false;
    }
    return (bool)$cache[$key];
  }
}

$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

$id = (int)($_GET['id'] ?? 0);

$q = trim((string)($_GET['q'] ?? ''));
$only_unpub = ((string)($_GET['only_unpub'] ?? '') === '1');
$return_params = [];
if ($q !== '') $return_params['q'] = $q;
if ($only_unpub) $return_params['only_unpub'] = '1';
$return_qs = $return_params ? ('?' . http_build_query($return_params, '', '&', PHP_QUERY_RFC3986)) : '';
$default_list_path = '/staff/subjects/index.php' . $return_qs;
$return_raw = (string)($_GET['return'] ?? '');
$return_path = pf__safe_return_url($return_raw, $default_list_path);
$list_url = $u($return_path);

if ($id <= 0) {
  redirect_to($list_url);
}

$pdo = null;
if (function_exists('staff_pdo')) $pdo = staff_pdo();
if (!$pdo instanceof PDO && function_exists('db')) $pdo = db();
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

$has_subject_menu_name   = pf__column_exists($pdo, 'subjects', 'menu_name');
$has_subject_name        = pf__column_exists($pdo, 'subjects', 'name');
$has_subject_slug        = pf__column_exists($pdo, 'subjects', 'slug');
$has_subject_description = pf__column_exists($pdo, 'subjects', 'description');
$has_subject_is_public   = pf__column_exists($pdo, 'subjects', 'is_public');
$has_subject_visible     = pf__column_exists($pdo, 'subjects', 'visible');

$pub_col = $has_subject_is_public ? 'is_public' : ($has_subject_visible ? 'visible' : null);

$subject_cols = ['id'];
if ($has_subject_menu_name)   $subject_cols[] = 'menu_name';
if ($has_subject_name)        $subject_cols[] = 'name';
if ($has_subject_slug)        $subject_cols[] = 'slug';
if ($has_subject_description) $subject_cols[] = 'description';
if ($pub_col)                 $subject_cols[] = $pub_col;

$st = $pdo->prepare("SELECT " . implode(', ', $subject_cols) . " FROM subjects WHERE id = ? LIMIT 1");
$st->execute([$id]);
$subject = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$subject) {
  redirect_to($list_url);
}

$subject_id = (int)$subject['id'];
$subject_name =
  ($has_subject_menu_name && trim((string)($subject['menu_name'] ?? '')) !== '') ? trim((string)$subject['menu_name']) :
  (($has_subject_name && trim((string)($subject['name'] ?? '')) !== '') ? trim((string)$subject['name']) : ('Subject #' . $subject_id));
$subject_slug = $has_subject_slug ? trim((string)($subject['slug'] ?? '')) : '';
$subject_desc = $has_subject_description ? trim((string)($subject['description'] ?? '')) : '';
$subject_vis  = ($pub_col && isset($subject[$pub_col])) ? (((int)$subject[$pub_col] === 1) ? 'public' : 'private') : '—';

$has_pages_subject_id    = pf__column_exists($pdo, 'pages', 'subject_id');
$has_pages_title         = pf__column_exists($pdo, 'pages', 'title');
$has_pages_menu_name     = pf__column_exists($pdo, 'pages', 'menu_name');
$has_pages_name          = pf__column_exists($pdo, 'pages', 'name');
$has_pages_slug          = pf__column_exists($pdo, 'pages', 'slug');
$has_pages_nav_order     = pf__column_exists($pdo, 'pages', 'nav_order');
$has_pages_position      = pf__column_exists($pdo, 'pages', 'position');
$has_pages_is_public     = pf__column_exists($pdo, 'pages', 'is_public');
$has_pages_published     = pf__column_exists($pdo, 'pages', 'published');
$has_pages_is_published  = pf__column_exists($pdo, 'pages', 'is_published');
$has_pages_status        = pf__column_exists($pdo, 'pages', 'status');
$has_pages_workflow      = pf__column_exists($pdo, 'pages', 'workflow_state');

$page_rows = [];
if ($has_pages_subject_id) {
  $page_name_col =
    $has_pages_menu_name ? 'menu_name' :
    ($has_pages_title ? 'title' :
    ($has_pages_name ? 'name' : null));

  $page_cols = ['id', 'subject_id'];
  $page_cols[] = $page_name_col ? ("`{$page_name_col}` AS page_name") : "CAST(id AS CHAR) AS page_name";
  $page_cols[] = $has_pages_slug ? "slug" : "NULL AS slug";
  $page_cols[] = $has_pages_is_public ? "is_public" : "NULL AS is_public";
  $page_cols[] = $has_pages_published ? "published" : "NULL AS published";
  $page_cols[] = $has_pages_is_published ? "is_published" : "NULL AS is_published";
  $page_cols[] = $has_pages_status ? "status" : "NULL AS status";
  $page_cols[] = $has_pages_workflow ? "workflow_state" : "NULL AS workflow_state";

  $order = [];
  if ($has_pages_nav_order) $order[] = "nav_order ASC";
  if ($has_pages_position)  $order[] = "position ASC";
  $order[] = "id DESC";

  $sql = "SELECT " . implode(', ', $page_cols) . " FROM pages WHERE subject_id = ? ORDER BY " . implode(', ', $order) . " LIMIT 500";
  $pst = $pdo->prepare($sql);
  $pst->execute([$subject_id]);
  $page_rows = $pst->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$page_title = $subject_name . ' • Staff';
$page_desc  = 'Review subject details and the pages under this subject.';
$nav_active = 'subjects';
$active_nav = 'subjects';

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => $nav_active,
      'active_nav' => $active_nav,
    ]);
  } catch (Throwable $e) {
    // swallow
  }
}

$staff_header = (defined('PRIVATE_PATH') && is_string(PRIVATE_PATH) && PRIVATE_PATH !== '')
  ? (rtrim(PRIVATE_PATH, "/\\") . '/shared/staff_header.php')
  : (defined('APP_ROOT') && is_string(APP_ROOT) && APP_ROOT !== ''
      ? (rtrim(APP_ROOT, "/\\") . '/private/shared/staff_header.php')
      : '');

$staff_footer = (defined('PRIVATE_PATH') && is_string(PRIVATE_PATH) && PRIVATE_PATH !== '')
  ? (rtrim(PRIVATE_PATH, "/\\") . '/shared/staff_footer.php')
  : (defined('APP_ROOT') && is_string(APP_ROOT) && APP_ROOT !== ''
      ? (rtrim(APP_ROOT, "/\\") . '/private/shared/staff_footer.php')
      : '');

if ($staff_header && is_file($staff_header)) {
  require $staff_header;
}
?>

<section class="staff-pagehead">
  <h1 class="staff-pagehead__title"><?= h($subject_name) ?></h1>
  <p class="staff-pagehead__desc"><?= h($subject_desc !== '' ? $subject_desc : 'Subject details and related pages.') ?></p>
</section>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Subject details</h2>
          <p style="margin:6px 0 0;color:#667085;">Review core subject metadata and move into editing or page management.</p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h($list_url) ?>">Back to subjects</a>
          <a class="staff-chip" href="<?= h($u('/staff/subjects/edit.php?id=' . $subject_id . '&return=' . rawurlencode($return_path))) ?>">Edit subject</a>
          <a class="staff-chip" href="<?= h($u('/staff/subjects/pgs/new.php?subject_id=' . $subject_id . '&return=' . rawurlencode($return_path))) ?>">New page</a>
        </div>
      </div>

      <div style="margin-top:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
        <div class="staff-note"><strong>ID:</strong> <?= h((string)$subject_id) ?></div>
        <div class="staff-note"><strong>Slug:</strong> <?= h($subject_slug !== '' ? $subject_slug : '—') ?></div>
        <div class="staff-note"><strong>Visibility:</strong> <?= h($subject_vis) ?></div>
        <div class="staff-note"><strong>Pages:</strong> <?= h((string)count($page_rows)) ?></div>
      </div>
    </div>
  </div>
</section>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <h2 style="margin:0 0 10px;font-size:1.15rem;">Pages in this subject</h2>

      <?php if (!$has_pages_subject_id): ?>
        <div class="staff-note">The pages table does not expose a subject_id column in this schema.</div>
      <?php elseif (!$page_rows): ?>
        <div class="staff-note">No pages found under this subject yet.</div>
      <?php else: ?>
        <div style="overflow:auto;border:1px solid rgba(17,24,39,.10);border-radius:14px;background:#fff;">
          <table style="width:100%;border-collapse:collapse;min-width:860px;">
            <thead>
              <tr style="background:rgba(17,24,39,.04);">
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">ID</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Page</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Slug</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">State</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($page_rows as $row): ?>
                <?php
                  $pid = (int)($row['id'] ?? 0);
                  $pname = trim((string)($row['page_name'] ?? ''));
                  $pslug = trim((string)($row['slug'] ?? ''));
                  $pname = ($pname !== '') ? $pname : ('Page #' . $pid);

                  $state = 'draft';
                  if (isset($row['status']) && $row['status'] !== null && trim((string)$row['status']) !== '') {
                    $state = (string)$row['status'];
                  } elseif (isset($row['workflow_state']) && $row['workflow_state'] !== null && trim((string)$row['workflow_state']) !== '') {
                    $state = (string)$row['workflow_state'];
                  } elseif (isset($row['published']) && $row['published'] !== null) {
                    $state = ((int)$row['published'] === 1) ? 'published' : 'draft';
                  } elseif (isset($row['is_published']) && $row['is_published'] !== null) {
                    $state = ((int)$row['is_published'] === 1) ? 'published' : 'draft';
                  } elseif (isset($row['is_public']) && $row['is_public'] !== null) {
                    $state = ((int)$row['is_public'] === 1) ? 'public' : 'private';
                  }

                  $showUrl = $u('/staff/pages/show.php?id=' . $pid . '&return=' . rawurlencode($return_path));
                  $editUrl = $u('/staff/subjects/pgs/edit.php?id=' . $pid . '&return=' . rawurlencode($return_path));
                ?>
                <tr>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h((string)$pid) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);font-weight:700;"><?= h($pname) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);color:#667085;"><?= h($pslug !== '' ? $pslug : '—') ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h($state) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);white-space:nowrap;">
                    <a class="staff-chip" href="<?= h($showUrl) ?>">View</a>
                    <a class="staff-chip" href="<?= h($editUrl) ?>">Edit</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php
if ($staff_footer && is_file($staff_footer)) {
  require $staff_footer;
} else {
  echo "</main></body></html>";
}
