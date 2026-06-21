<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
auth_require_role('staff');

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

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$return = pf__safe_return_url((string)($_GET['return'] ?? '/staff/contributors/index.php'), '/staff/contributors/index.php');

if ($id <= 0) {
  header('Location: ' . $u($return), true, 302);
  exit;
}

$st = $pdo->prepare("
  SELECT id, slug, display_name, email, roles, status, is_public, sort_order, bio_raw, bio_html, deleted_at
  FROM contributors
  WHERE id = ?
  LIMIT 1
");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  http_response_code(404);
  echo "Contributor not found.";
  exit;
}

$roles_value = '';
if (!empty($row['roles'])) {
  $decoded = json_decode((string)$row['roles'], true);
  if (is_array($decoded)) {
    $roles_value = implode(', ', array_map('strval', $decoded));
  } else {
    $roles_value = (string)$row['roles'];
  }
}

$page_title = 'Edit Contributor • Staff';
$page_desc  = 'Update contributor identity, visibility, roles, and bio.';
$nav_active = 'contributors';
$active_nav = 'contributors';

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => $nav_active,
      'active_nav' => $active_nav,
    ]);
  } catch (Throwable $e) {
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
  <h1 class="staff-pagehead__title">Edit contributor</h1>
  <p class="staff-pagehead__desc">Update contributor settings for <strong><?= h((string)$row['display_name']) ?></strong>.</p>
</section>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Contributor record</h2>
          <p style="margin:6px 0 0;color:#667085;"><?= h((string)$row['slug']) ?></p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h($return) ?>">Back</a>
          <a class="staff-chip" href="<?= h('/staff/contributors/show.php?id=' . rawurlencode((string)$row['id']) . '&return=' . rawurlencode($return)) ?>">View</a>
        </div>
      </div>

      <form method="post" action="<?= h($u('/staff/contributors/update.php')) ?>" style="margin-top:14px;display:grid;gap:14px;">
        <?= pf__csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
        <input type="hidden" name="return" value="<?= h($return) ?>">

        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Display Name</label>
            <input type="text" name="display_name" required value="<?= h((string)$row['display_name']) ?>" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>

          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Slug</label>
            <input type="text" name="slug" value="<?= h((string)$row['slug']) ?>" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>

          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Email</label>
            <input type="email" name="email" value="<?= h((string)($row['email'] ?? '')) ?>" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>

          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Sort Order</label>
            <input type="number" name="sort_order" step="1" value="<?= (int)$row['sort_order'] ?>" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>

          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Status</label>
            <select name="status" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
              <option value="active" <?= ((string)$row['status'] === 'active') ? 'selected' : '' ?>>Active</option>
              <option value="draft" <?= ((string)$row['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
            </select>
          </div>

          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Visibility</label>
            <select name="is_public" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
              <option value="1" <?= ((int)$row['is_public'] === 1) ? 'selected' : '' ?>>Public</option>
              <option value="0" <?= ((int)$row['is_public'] === 0) ? 'selected' : '' ?>>Private</option>
            </select>
          </div>

          <div style="grid-column:1 / -1;">
            <label style="display:block;font-weight:700;margin-bottom:6px;">Roles (CSV or JSON array)</label>
            <input type="text" name="roles" value="<?= h($roles_value) ?>" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>

          <div style="grid-column:1 / -1;">
            <label style="display:block;font-weight:700;margin-bottom:6px;">Bio Raw</label>
            <textarea name="bio_raw" style="width:100%;min-height:160px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;resize:vertical;"><?= h((string)($row['bio_raw'] ?? '')) ?></textarea>
          </div>
        </div>

        <div class="staff-actions">
          <button type="submit" class="staff-chip" style="cursor:pointer;">Save changes</button>
          <a class="staff-chip" href="<?= h($return) ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</section>

<?php
if ($staff_footer && is_file($staff_footer)) {
  require $staff_footer;
} else {
  echo "</main></body></html>";
}
