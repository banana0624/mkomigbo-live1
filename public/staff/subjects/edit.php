<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
auth_require_role('staff');

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
if (!function_exists('pf__flash_set')) {
  function pf__flash_set(string $key, string $msg): void {
    mk_staff_session_start();
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
      $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = $msg;
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
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];

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
  }
}
if (!function_exists('pf__slugify')) {
  function pf__slugify(string $s): string {
    $s = trim($s);
    if ($s === '') return 'subject';
    $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    $s = preg_replace('~[^\pL\pN]+~u', '-', $s) ?? '';
    $s = trim($s, '-');
    $s = preg_replace('~-{2,}~', '-', $s) ?? '';
    return $s !== '' ? $s : 'subject';
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
if (!function_exists('pf__csrf_require')) {
  function pf__csrf_require(): void {
    if (function_exists('csrf_require')) {
      try { csrf_require(); return; } catch (Throwable $e) {}
    }
    $sent = (string)($_POST['csrf_token'] ?? '');
    $sess = (string)($_SESSION['csrf_token'] ?? '');
    if ($sent === '' || $sess === '' || !hash_equals($sess, $sent)) {
      http_response_code(400);
      header('Content-Type: text/plain; charset=utf-8');
      echo "Bad Request (CSRF)\n";
      exit;
    }
  }
}

$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

$pdo = null;
if (function_exists('db')) $pdo = db();
if (!$pdo instanceof PDO && function_exists('pdo')) $pdo = pdo();
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  redirect_to($list_url);
}

$has_menu_name   = pf__column_exists($pdo, 'subjects', 'menu_name');
$has_name        = pf__column_exists($pdo, 'subjects', 'name');
$has_slug        = pf__column_exists($pdo, 'subjects', 'slug');
$has_description = pf__column_exists($pdo, 'subjects', 'description');
$has_nav_order   = pf__column_exists($pdo, 'subjects', 'nav_order');
$has_position    = pf__column_exists($pdo, 'subjects', 'position');
$has_is_public   = pf__column_exists($pdo, 'subjects', 'is_public');

$order_col = $has_nav_order ? 'nav_order' : ($has_position ? 'position' : null);

$cols = ['id'];
if ($has_menu_name)   $cols[] = 'menu_name';
if ($has_name)        $cols[] = 'name';
if ($has_slug)        $cols[] = 'slug';
if ($has_description) $cols[] = 'description';
if ($order_col)       $cols[] = $order_col;
if ($has_is_public)   $cols[] = 'is_public';

$st = $pdo->prepare("SELECT " . implode(', ', $cols) . " FROM subjects WHERE id = ? LIMIT 1");
$st->execute([$id]);
$subject = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$subject) {
  pf__flash_set('error', 'Subject not found.');
  redirect_to($list_url);
}

$errors = [];
$notice = pf__flash_get('notice');
$success = pf__flash_get('success');
$error = pf__flash_get('error');

$menu_name   = $has_menu_name   ? trim((string)($subject['menu_name'] ?? '')) : '';
$name        = $has_name        ? trim((string)($subject['name'] ?? '')) : '';
$slug        = $has_slug        ? trim((string)($subject['slug'] ?? '')) : '';
$description = $has_description ? trim((string)($subject['description'] ?? '')) : '';
$order_raw   = $order_col       ? trim((string)($subject[$order_col] ?? '')) : '';
$is_public   = $has_is_public   ? (int)($subject['is_public'] ?? 0) : 0;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  pf__csrf_require();

  $posted_return = (string)($_POST['return'] ?? '');
  $return_path = pf__safe_return_url($posted_return, $default_list_path);
  $list_url = $u($return_path);

  if ($has_menu_name)   $menu_name   = trim((string)($_POST['menu_name'] ?? ''));
  if ($has_name)        $name        = trim((string)($_POST['name'] ?? ''));
  if ($has_slug)        $slug        = trim((string)($_POST['slug'] ?? ''));
  if ($has_description) $description = trim((string)($_POST['description'] ?? ''));
  if ($order_col)       $order_raw   = trim((string)($_POST['nav_order'] ?? ''));
  if ($has_is_public)   $is_public   = isset($_POST['is_public']) ? 1 : 0;

  if ($has_menu_name && $has_name) {
    if ($menu_name === '' && $name === '') $errors[] = 'Provide at least Menu Name or Name.';
  } elseif ($has_menu_name) {
    if ($menu_name === '') $errors[] = 'Menu Name is required.';
  } elseif ($has_name) {
    if ($name === '') $errors[] = 'Name is required.';
  }

  if ($has_slug && $slug === '') {
    $base = ($menu_name !== '') ? $menu_name : (($name !== '') ? $name : 'subject');
    $slug = pf__slugify($base);
  }

  $order_val = null;
  if ($order_col && $order_raw !== '') {
    if (!preg_match('/^-?\d+$/', $order_raw)) {
      $errors[] = 'Order must be a whole number or blank.';
    } else {
      $order_val = (int)$order_raw;
    }
  }

  if (!$errors) {
    try {
      $sets = [];
      $vals = [];

      if ($has_menu_name)   { $sets[] = 'menu_name = ?';   $vals[] = $menu_name; }
      if ($has_name)        { $sets[] = 'name = ?';        $vals[] = $name; }
      if ($has_slug)        { $sets[] = 'slug = ?';        $vals[] = $slug; }
      if ($has_description) { $sets[] = 'description = ?'; $vals[] = $description; }
      if ($order_col)       { $sets[] = $order_col . ' = ?'; $vals[] = $order_val; }
      if ($has_is_public)   { $sets[] = 'is_public = ?';   $vals[] = $is_public; }

      if (pf__column_exists($pdo, 'subjects', 'updated_at')) {
        $sets[] = 'updated_at = NOW()';
      }

      if (!$sets) throw new RuntimeException('No updatable columns detected on subjects.');

      $vals[] = $id;
      $sql = "UPDATE subjects SET " . implode(', ', $sets) . " WHERE id = ? LIMIT 1";
      $up = $pdo->prepare($sql);
      $up->execute($vals);

      pf__flash_set('success', 'Subject updated successfully.');
      redirect_to($u('/staff/subjects/edit.php?id=' . $id . '&return=' . rawurlencode($return_path)));
    } catch (Throwable $e) {
      $msg = $e->getMessage();
      if (stripos($msg, 'Duplicate') !== false && stripos($msg, 'slug') !== false) {
        $errors[] = 'Slug already exists. Choose a different slug.';
      } else {
        $errors[] = 'Update failed: ' . $msg;
      }
    }
  }
}

$subject_title = ($menu_name !== '') ? $menu_name : (($name !== '') ? $name : ('Subject #' . $id));

$page_title = 'Edit Subject • Staff';
$page_desc  = 'Edit subject details and keep the record consistent.';
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
  <h1 class="staff-pagehead__title">Edit subject</h1>
  <p class="staff-pagehead__desc">Update metadata for <strong><?= h($subject_title) ?></strong> and keep the public library organized.</p>
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

<?php if ($errors): ?>
  <section class="staff-section">
    <div class="staff-note" style="color:#7f1d1d;border-color:rgba(239,68,68,.24);">
      <strong>Fix the following:</strong>
      <ul style="margin:8px 0 0 18px;">
        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </section>
<?php endif; ?>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Subject record</h2>
          <p style="margin:6px 0 0;color:#667085;">Edit supported fields only. Unsupported columns are ignored safely.</p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h($u('/staff/subjects/show.php?id=' . $id . '&return=' . rawurlencode($return_path))) ?>">View subject</a>
          <a class="staff-chip" href="<?= h($list_url) ?>">Back to subjects</a>
        </div>
      </div>

      <form method="post" action="" style="margin-top:14px;display:grid;gap:14px;">
        <?= pf__csrf_field() ?>
        <input type="hidden" name="return" value="<?= h($return_path) ?>">

        <div class="staff-note"><strong>ID:</strong> <?= h((string)$id) ?></div>

        <?php if ($has_menu_name): ?>
          <div>
            <label for="menu_name" style="display:block;font-weight:700;margin-bottom:6px;">Menu Name<?= $has_name ? ' (optional)' : '' ?></label>
            <input id="menu_name" type="text" name="menu_name" value="<?= h($menu_name) ?>" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>
        <?php endif; ?>

        <?php if ($has_name || !$has_menu_name): ?>
          <div>
            <label for="name" style="display:block;font-weight:700;margin-bottom:6px;">Name<?= $has_menu_name ? ' (optional)' : '' ?></label>
            <input id="name" type="text" name="name" value="<?= h($name) ?>" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>
        <?php endif; ?>

        <?php if ($has_slug): ?>
          <div>
            <label for="slug" style="display:block;font-weight:700;margin-bottom:6px;">Slug</label>
            <input id="slug" type="text" name="slug" value="<?= h($slug) ?>" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>
        <?php endif; ?>

        <?php if ($has_description): ?>
          <div>
            <label for="description" style="display:block;font-weight:700;margin-bottom:6px;">Description</label>
            <textarea id="description" name="description" rows="5" style="width:100%;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;resize:vertical;"><?= h($description) ?></textarea>
          </div>
        <?php endif; ?>

        <?php if ($order_col): ?>
          <div>
            <label for="nav_order" style="display:block;font-weight:700;margin-bottom:6px;"><?= h($order_col) ?></label>
            <input id="nav_order" type="text" name="nav_order" value="<?= h($order_raw) ?>" style="width:100%;min-height:44px;padding:10px 12px;border:1px solid rgba(17,24,39,.14);border-radius:12px;">
          </div>
        <?php endif; ?>

        <?php if ($has_is_public): ?>
          <label style="display:flex;gap:10px;align-items:flex-start;">
            <input type="checkbox" name="is_public" value="1" <?= $is_public ? 'checked' : '' ?>>
            <span><strong>Public visibility</strong><br><span style="color:#667085;">Allow this subject to appear publicly.</span></span>
          </label>
        <?php endif; ?>

        <div class="staff-actions">
          <button type="submit" class="staff-chip" style="cursor:pointer;">Save changes</button>
          <a class="staff-chip" href="<?= h($u('/staff/subjects/show.php?id=' . $id . '&return=' . rawurlencode($return_path))) ?>">View subject</a>
          <a class="staff-chip" href="<?= h($list_url) ?>">Cancel</a>
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
