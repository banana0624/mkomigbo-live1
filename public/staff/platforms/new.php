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
if (!function_exists('pf__csrf_token')) {
  function pf__csrf_token(): string {
    mk_staff_session_start();
    if (function_exists('csrf_token')) {
      try {
        $v = (string)csrf_token();
        if ($v !== '') return $v;
      } catch (Throwable $e) {
        // continue
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

$return = '/staff/platforms/index.php';

$page_title = 'New Platform • Staff';
$page_desc  = 'Create a platform with slug, lifecycle, visibility, order, and sections.';
$nav_active = 'platforms';
$active_nav = 'platforms';

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
  <h1 class="staff-pagehead__title">New platform</h1>
  <p class="staff-pagehead__desc">Create a platform with core metadata, visibility, sort order, and sections JSON.</p>
</section>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Create platform</h2>
          <p style="margin:6px 0 0;color:#667085;">This form posts to the existing create handler.</p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h($return) ?>">Back</a>
        </div>
      </div>

      <form method="post" action="<?= h($u('/staff/platforms/create.php')) ?>" style="margin-top:14px;display:grid;gap:14px;">
        <?= pf__csrf_field() ?>
        <input type="hidden" name="return" value="<?= h($return) ?>">

        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Name</label>
            <input type="text" name="name" required style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>

          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Slug</label>
            <input type="text" name="slug" placeholder="leave blank to auto-generate" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>

          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Status</label>
            <select name="status" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
              <option value="draft">Draft</option>
              <option value="live">Live</option>
            </select>
          </div>

          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Visibility</label>
            <select name="is_public" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
              <option value="1">Public</option>
              <option value="0">Private</option>
            </select>
          </div>

          <div>
            <label style="display:block;font-weight:700;margin-bottom:6px;">Sort Order</label>
            <input type="number" name="sort_order" value="0" step="1" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>

          <div style="grid-column:1 / -1;">
            <label style="display:block;font-weight:700;margin-bottom:6px;">Description</label>
            <textarea name="description" style="width:100%;min-height:140px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;resize:vertical;"></textarea>
          </div>

          <div style="grid-column:1 / -1;">
            <label style="display:block;font-weight:700;margin-bottom:6px;">Sections JSON</label>
            <textarea name="sections_json" placeholder='["overview","posts","gallery"]' style="width:100%;min-height:140px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;resize:vertical;"></textarea>
          </div>
        </div>

        <div class="staff-actions">
          <button type="submit" class="staff-chip" style="cursor:pointer;">Create platform</button>
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
