<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
auth_require_role('staff');

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

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
if (!function_exists('mk_staff_session_start')) {
  function mk_staff_session_start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      @session_start();
    }
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
if (!function_exists('pf__csrf_token')) {
  function pf__csrf_token(): string {
    mk_staff_session_start();
    if (function_exists('csrf_token')) {
      try {
        $v = (string)csrf_token();
        if ($v !== '') return $v;
      } catch (Throwable $e) {
      }
    }
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
  }
}
if (!function_exists('pf__csrf_field')) {
  function pf__csrf_field(): string {
    if (function_exists('csrf_field')) {
      try { return (string)csrf_field(); } catch (Throwable $e) {}
    }
    return '<input type="hidden" name="csrf_token" value="' . h(pf__csrf_token()) . '">';
  }
}

$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

$page_title = 'Subjects • Staff';
$page_desc  = 'Manage subject categories, status, visibility, and ordering.';
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

$pdo = db();

$q          = trim((string)($_GET['q'] ?? ''));
$status     = trim((string)($_GET['status'] ?? ''));
$visibility = trim((string)($_GET['visibility'] ?? ''));
$trash      = ((string)($_GET['trash'] ?? '0') === '1');

$where  = [];
$params = [];

$where[] = $trash ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL';

if ($q !== '') {
  $where[] = '(name LIKE :q OR slug LIKE :q)';
  $params[':q'] = '%' . $q . '%';
}
if ($status !== '' && in_array($status, ['active', 'draft'], true)) {
  $where[] = 'status = :status';
  $params[':status'] = $status;
}
if ($visibility !== '' && in_array($visibility, ['public', 'private'], true)) {
  $where[] = 'is_public = :is_public';
  $params[':is_public'] = ($visibility === 'public') ? 1 : 0;
}

$sql = "
  SELECT id, slug, name, status, is_public, sort_order, deleted_at, created_at, updated_at
  FROM subjects
  WHERE " . implode(' AND ', $where) . "
  ORDER BY sort_order ASC, name ASC, id ASC
  LIMIT 300
";

try {
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $rows = [];
}

$notice  = pf__flash_get('notice');
$success = pf__flash_get('success');
$error   = pf__flash_get('error');

$return_params = [];
if ($q !== '') $return_params['q'] = $q;
if ($status !== '') $return_params['status'] = $status;
if ($visibility !== '') $return_params['visibility'] = $visibility;
if ($trash) $return_params['trash'] = '1';
$return_qs = $return_params ? ('?' . http_build_query($return_params, '', '&', PHP_QUERY_RFC3986)) : '';
$return = '/staff/subjects/index.php' . $return_qs;

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
  <p class="staff-pagehead__desc">Manage subject categories, visibility, status, and ordering.</p>
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

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <form method="get" class="staff-actions" style="align-items:flex-end;">
        <div style="flex:1 1 280px;">
          <label for="q" style="display:block;font-weight:700;margin-bottom:6px;">Search</label>
          <input id="q" type="text" name="q" value="<?= h($q) ?>" placeholder="Search name, slug" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
        </div>

        <div style="min-width:160px;">
          <label for="status" style="display:block;font-weight:700;margin-bottom:6px;">Status</label>
          <select id="status" name="status" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
            <option value="">All statuses</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="draft"  <?= $status === 'draft'  ? 'selected' : '' ?>>Draft</option>
          </select>
        </div>

        <div style="min-width:160px;">
          <label for="visibility" style="display:block;font-weight:700;margin-bottom:6px;">Visibility</label>
          <select id="visibility" name="visibility" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
            <option value="">All visibility</option>
            <option value="public"  <?= $visibility === 'public'  ? 'selected' : '' ?>>Public</option>
            <option value="private" <?= $visibility === 'private' ? 'selected' : '' ?>>Private</option>
          </select>
        </div>

        <div style="min-width:140px;">
          <label for="trash" style="display:block;font-weight:700;margin-bottom:6px;">List</label>
          <select id="trash" name="trash" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
            <option value="0" <?= !$trash ? 'selected' : '' ?>>Active</option>
            <option value="1" <?= $trash  ? 'selected' : '' ?>>Trash</option>
          </select>
        </div>

        <div class="staff-actions">
          <button type="submit" class="staff-chip" style="cursor:pointer;">Filter</button>
          <a class="staff-chip" href="<?= h($u('/staff/subjects/new.php')) ?>">New subject</a>
        </div>
      </form>
    </div>
  </div>
</section>

<section class="staff-section">
  <form method="post" action="<?= h($u('/staff/subjects/bulk.php')) ?>">
    <?= pf__csrf_field() ?>
    <input type="hidden" name="return" value="<?= h($return) ?>">

    <div class="staff-card">
      <div class="staff-card__body">
        <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
          <div>
            <h2 style="margin:0;font-size:1.15rem;">Subject registry</h2>
            <p style="margin:6px 0 0;color:#667085;"><?= h((string)count($rows)) ?> row(s) found.</p>
          </div>

          <div class="staff-actions">
            <select name="action" required style="min-width:220px;min-height:42px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
              <option value="">Bulk action</option>
              <option value="set_active">Set Active</option>
              <option value="set_draft">Set Draft</option>
              <option value="set_public">Set Public</option>
              <option value="set_private">Set Private</option>
              <option value="soft_delete">Soft Delete</option>
              <option value="restore">Restore</option>
            </select>
            <button type="submit" class="staff-chip" style="cursor:pointer;">Apply</button>
          </div>
        </div>

        <div style="margin-top:14px;overflow:auto;border:1px solid rgba(17,24,39,.10);border-radius:14px;background:#fff;">
          <table style="width:100%;border-collapse:collapse;min-width:780px;">
            <thead>
              <tr style="background:rgba(17,24,39,.04);">
                <th style="width:36px;text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">
                  <input type="checkbox" onclick="document.querySelectorAll('.cb').forEach(x => x.checked = this.checked)">
                </th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Name</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Slug</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Status</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Visibility</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Order</th>
                <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
              <tr>
                <td colspan="7" style="padding:16px 14px;color:#667085;">No subjects found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
                <?php
                  $id   = (int)$r['id'];
                  $show = '/staff/subjects/show.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return);
                  $edit = '/staff/subjects/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return);
                  $del  = '/staff/subjects/delete.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return);
                ?>
                <tr>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);">
                    <input class="cb" type="checkbox" name="ids[]" value="<?= $id ?>">
                  </td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);">
                    <strong><?= h((string)$r['name']) ?></strong>
                  </td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);color:#667085;"><?= h((string)$r['slug']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h((string)$r['status']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= ((int)$r['is_public'] === 1) ? 'public' : 'private' ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h((string)$r['sort_order']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);white-space:nowrap;">
                    <a class="staff-chip" href="<?= h($show) ?>">View</a>
                    <a class="staff-chip" href="<?= h($edit) ?>">Edit</a>
                    <a class="staff-chip" href="<?= h($del) ?>" style="color:#7f1d1d;border-color:rgba(239,68,68,.24);">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </form>
</section>

<?php
if ($staff_footer && is_file($staff_footer)) {
  require $staff_footer;
} else {
  echo "</main></body></html>";
}