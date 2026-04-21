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
if (!function_exists('pf__flash_get')) {
  function pf__flash_get(string $key): string {
    mk_staff_session_start();
    $v = '';
    if (isset($_SESSION['flash'][$key]) && is_string($_SESSION['flash'][$key])) {
      $v = $_SESSION['flash'][$key];
    }
    unset($_SESSION['flash'][$key]);
    return $v;
  }
}
if (!function_exists('pf__table_exists')) {
  function pf__table_exists(PDO $pdo, string $table): bool {
    try {
      $st = $pdo->prepare("
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
      ");
      $st->execute([$table]);
      return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
      return false;
    }
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

$page_title = 'Subjects • Staff';
$page_desc  = 'Manage subjects in the public library.';
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

$notice = pf__flash_get('notice');
$success = pf__flash_get('success');
$error = pf__flash_get('error');

$current_uri  = (string)($_SERVER['REQUEST_URI'] ?? '/staff/subjects/');
$current_path = parse_url($current_uri, PHP_URL_PATH);
$current_path = is_string($current_path) ? $current_path : '/staff/subjects/';
$current_qs   = parse_url($current_uri, PHP_URL_QUERY);
$current_qs   = is_string($current_qs) && $current_qs !== '' ? ('?' . $current_qs) : '';
$return_path  = pf__safe_return_url($current_path . $current_qs, '/staff/subjects/index.php');
$return_enc   = rawurlencode($return_path);

$rows = [];
$warn = '';

try {
  $pdo = null;
  if (function_exists('pdo')) $pdo = pdo();
  if (!$pdo instanceof PDO && function_exists('staff_pdo')) $pdo = staff_pdo();
  if (!$pdo instanceof PDO && function_exists('db')) $pdo = db();
  if (!$pdo instanceof PDO) throw new RuntimeException('Database handle not available.');

  if (!pf__table_exists($pdo, 'subjects')) {
    $rows = [];
  } else {
    $has_display_name = pf__column_exists($pdo, 'subjects', 'display_name');
    $has_menu_name    = pf__column_exists($pdo, 'subjects', 'menu_name');
    $has_name         = pf__column_exists($pdo, 'subjects', 'name');
    $has_title        = pf__column_exists($pdo, 'subjects', 'title');
    $has_slug         = pf__column_exists($pdo, 'subjects', 'slug');
    $has_status       = pf__column_exists($pdo, 'subjects', 'status');
    $has_is_active    = pf__column_exists($pdo, 'subjects', 'is_active');
    $has_is_public    = pf__column_exists($pdo, 'subjects', 'is_public');
    $has_visible      = pf__column_exists($pdo, 'subjects', 'visible');
    $has_nav_order    = pf__column_exists($pdo, 'subjects', 'nav_order');
    $has_position     = pf__column_exists($pdo, 'subjects', 'position');
    $has_created_at   = pf__column_exists($pdo, 'subjects', 'created_at');

    $nameCol =
      $has_display_name ? 'display_name' :
      ($has_menu_name   ? 'menu_name' :
      ($has_name        ? 'name' :
      ($has_title       ? 'title' : null)));

    $cols = ['id'];
    $cols[] = $nameCol ? ("`{$nameCol}` AS display_name") : "CAST(id AS CHAR) AS display_name";
    $cols[] = $has_slug       ? "slug" : "NULL AS slug";
    $cols[] = $has_status     ? "status" : "NULL AS status";
    $cols[] = $has_is_active  ? "is_active" : "NULL AS is_active";
    $cols[] = $has_is_public  ? "is_public" : ($has_visible ? "visible AS is_public" : "NULL AS is_public");
    $cols[] = $has_created_at ? "created_at" : "NULL AS created_at";

    $order = [];
    if ($has_nav_order) $order[] = "nav_order ASC";
    if ($has_position)  $order[] = "position ASC";
    $order[] = "id DESC";

    $sql = "SELECT " . implode(', ', $cols) . " FROM subjects ORDER BY " . implode(', ', $order) . " LIMIT 500";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
} catch (Throwable $e) {
  $warn = $e->getMessage();
  $rows = [];
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
  <h1 class="staff-pagehead__title">Subjects</h1>
  <p class="staff-pagehead__desc">Create, review, and maintain subject records for the public library.</p>
</section>

<?php if ($notice !== ''): ?>
  <section class="staff-section"><div class="staff-note"><?= h($notice) ?></div></section>
<?php endif; ?>

<?php if ($success !== ''): ?>
  <section class="staff-section"><div class="staff-note" style="color:#14532d;border-color:rgba(34,197,94,.24);"><?= h($success) ?></div></section>
<?php endif; ?>

<?php if ($error !== ''): ?>
  <section class="staff-section"><div class="staff-note" style="color:#7f1d1d;border-color:rgba(239,68,68,.24);"><?= h($error) ?></div></section>
<?php endif; ?>

<?php if ($warn !== ''): ?>
  <section class="staff-section"><div class="staff-note" style="color:#7c2d12;border-color:rgba(245,158,11,.30);"><?= h($warn) ?></div></section>
<?php endif; ?>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Subject registry</h2>
          <p style="margin:6px 0 0;color:#667085;">Showing <?= h((string)count($rows)) ?> subject<?= count($rows) === 1 ? '' : 's' ?>.</p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h($u('/staff/subjects/new.php?return=' . $return_enc)) ?>">New subject</a>
          <a class="staff-chip" href="<?= h($u('/staff/')) ?>">Dashboard</a>
        </div>
      </div>

      <?php if (!$rows): ?>
        <div class="staff-note" style="margin-top:14px;">No subjects found.</div>
      <?php else: ?>
        <div style="margin-top:14px;overflow:auto;border:1px solid rgba(17,24,39,.10);border-radius:14px;background:#fff;">
          <table style="width:100%;border-collapse:collapse;min-width:860px;">
            <thead>
              <tr style="background:rgba(17,24,39,.04);">
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">ID</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Name</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Slug</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Status</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Visibility</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <?php
                  $id   = (int)($r['id'] ?? 0);
                  $name = trim((string)($r['display_name'] ?? ''));
                  $slug = trim((string)($r['slug'] ?? ''));
                  $name = ($name !== '') ? $name : ('Subject #' . $id);

                  $status = '—';
                  if (isset($r['status']) && $r['status'] !== null && $r['status'] !== '') {
                    $status = (string)$r['status'];
                  } elseif (isset($r['is_active']) && $r['is_active'] !== null) {
                    $status = ((int)$r['is_active'] === 1) ? 'active' : 'inactive';
                  }

                  $visibility = '—';
                  if (isset($r['is_public']) && $r['is_public'] !== null) {
                    $visibility = ((int)$r['is_public'] === 1) ? 'public' : 'private';
                  }

                  $u_show = $u('/staff/subjects/show.php?id=' . $id . '&return=' . $return_enc);
                  $u_edit = $u('/staff/subjects/edit.php?id=' . $id . '&return=' . $return_enc);
                  $u_del  = $u('/staff/subjects/delete.php?id=' . $id . '&return=' . $return_enc);
                ?>
                <tr>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h((string)$id) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);font-weight:700;"><?= h($name) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);color:#667085;"><?= h($slug !== '' ? $slug : '—') ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h($status) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h($visibility) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);white-space:nowrap;">
                    <a class="staff-chip" href="<?= h($u_show) ?>">View</a>
                    <a class="staff-chip" href="<?= h($u_edit) ?>">Edit</a>
                    <a class="staff-chip" href="<?= h($u_del) ?>" style="color:#7f1d1d;border-color:rgba(239,68,68,.24);">Delete</a>
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
