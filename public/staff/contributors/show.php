<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';
auth_require_role('staff');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!function_exists('h')) {
  function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, 302);
    exit;
  }
}
if (!function_exists('pf__u')) {
  function pf__u(string $path): string {
    return function_exists('url_for') ? (string)url_for($path) : $path;
  }
}
if (!function_exists('pf__flash_get')) {
  function pf__flash_get(string $key): string {
    mk_staff_session_start();
    $msg = '';
    if (isset($_SESSION['flash']) && is_array($_SESSION['flash']) && array_key_exists($key, $_SESSION['flash'])) {
      $msg = (string)$_SESSION['flash'][$key];
      unset($_SESSION['flash'][$key]);
    }
    return $msg;
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
if (!function_exists('pf__table_exists')) {
  function pf__table_exists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1");
    $st->execute([$table]);
    return (bool)$st->fetchColumn();
  }
}
if (!function_exists('pf__column_exists')) {
  function pf__column_exists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];

    $sql = "SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$table, $column]);
    $cache[$key] = ((int)$st->fetchColumn() > 0);
    return (bool)$cache[$key];
  }
}
if (!function_exists('pf__quote_ident')) {
  function pf__quote_ident(string $col): string {
    return '`' . str_replace('`', '', $col) . '`';
  }
}
if (!function_exists('pf__pill_for_status')) {
  function pf__pill_for_status(?string $status): array {
    $s = strtolower(trim((string)$status));
    if ($s === '') $s = 'active';

    if ($s === 'active' || $s === 'published' || $s === 'public') {
      return ['text' => 'Active', 'class' => 'success'];
    }
    if ($s === 'draft' || $s === 'inactive' || $s === 'hidden') {
      return ['text' => 'Draft', 'class' => 'muted'];
    }
    return ['text' => strtoupper($s), 'class' => 'muted'];
  }
}
if (!function_exists('pf__roles_display')) {
  function pf__roles_display(string $raw): string {
    $raw = trim($raw);
    if ($raw === '') return '';
    if (isset($raw[0]) && $raw[0] === '[') {
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) {
        $out = [];
        foreach ($decoded as $v) {
          $v = trim((string)$v);
          if ($v !== '') $out[] = $v;
        }
        $out = array_values(array_unique($out));
        return implode(', ', $out);
      }
    }
    return $raw;
  }
}

$pdo = function_exists('staff_pdo') ? staff_pdo() : (function_exists('db') ? db() : null);
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

$id = (int)($_GET['id'] ?? 0);
$default_return = '/staff/contributors/index.php';
$return = pf__safe_return_url((string)($_GET['return'] ?? $default_return), $default_return);
if ($id <= 0) redirect_to(pf__u($return));

$notice = pf__flash_get('notice');
$error  = pf__flash_get('error');

$contributor = null;
$warn = '';

try {
  if (!pf__table_exists($pdo, 'contributors')) {
    $warn = 'Table "contributors" not found yet.';
  } else {
    $select = ['id'];
    $wanted = [
      'display_name','email','roles','status',
      'created_at','updated_at',
      'bio_raw','bio_html',
      'name','username','slug','avatar_path','bio',
      'is_public','visible'
    ];

    foreach ($wanted as $c) {
      if (pf__column_exists($pdo, 'contributors', $c)) $select[] = $c;
    }

    $select = array_values(array_unique($select));
    $cols = [];
    foreach ($select as $c) { $cols[] = pf__quote_ident((string)$c); }

    $sql = "SELECT " . implode(', ', $cols) . " FROM contributors WHERE id = ? LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
    $contributor = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$contributor) $warn = 'Contributor not found.';
  }
} catch (Throwable $e) {
  $warn = 'Failed to load contributor.';
  $contributor = null;
}

$name = 'Contributor #' . $id;
if ($contributor) {
  $name = (string)(
    $contributor['display_name']
    ?? $contributor['name']
    ?? $contributor['username']
    ?? $contributor['email']
    ?? $contributor['slug']
    ?? $name
  );
  $name = trim($name) !== '' ? $name : ('Contributor #' . $id);
}

$active_nav = 'contributors';
$page_title = $name . ' • Contributor • Staff';
$page_desc  = 'View contributor details.';

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'active_nav' => $active_nav,
      'nav_active' => $active_nav,
    ]);
  } catch (Throwable $e) {
  }
}

$staff_header = (defined('PRIVATE_PATH') && is_string(PRIVATE_PATH) && PRIVATE_PATH !== '')
  ? (rtrim(PRIVATE_PATH, "/\\") . '/shared/staff_header.php')
  : (defined('APP_ROOT') && is_string(APP_ROOT) && APP_ROOT !== ''
      ? (rtrim(APP_ROOT, "/\\") . '/app/mkomigbo/private/shared/staff_header.php')
      : '');

$staff_footer = (defined('PRIVATE_PATH') && is_string(PRIVATE_PATH) && PRIVATE_PATH !== '')
  ? (rtrim(PRIVATE_PATH, "/\\") . '/shared/staff_footer.php')
  : (defined('APP_ROOT') && is_string(APP_ROOT) && APP_ROOT !== ''
      ? (rtrim(APP_ROOT, "/\\") . '/app/mkomigbo/private/shared/staff_footer.php')
      : '');

if ($staff_header && is_file($staff_header)) {
  require $staff_header;
}

$back_url = pf__u($return);
$edit_url = pf__u('/staff/contributors/edit.php') . '?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return);
$del_url  = pf__u('/staff/contributors/delete.php') . '?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return);

$status_val  = ($contributor && array_key_exists('status', $contributor)) ? (string)$contributor['status'] : 'active';
$status_pill = pf__pill_for_status($status_val);

$bio_html = '';
$bio_text = '';
if ($contributor) {
  if (isset($contributor['bio_html']) && trim((string)$contributor['bio_html']) !== '') {
    $bio_html = (string)$contributor['bio_html'];
  } else {
    $candidate = '';
    foreach (['bio_raw', 'bio'] as $k) {
      if (isset($contributor[$k]) && trim((string)$contributor[$k]) !== '') {
        $candidate = (string)$contributor[$k];
        break;
      }
    }
    $bio_text = trim($candidate);
  }
}

$roles = $contributor ? pf__roles_display((string)($contributor['roles'] ?? '')) : '';
$is_public = $contributor ? ((int)($contributor['is_public'] ?? ($contributor['visible'] ?? 0)) === 1) : false;
?>

<section class="staff-pagehead">
  <h1 class="staff-pagehead__title"><?= h($name) ?></h1>
  <p class="staff-pagehead__desc">Review contributor identity, visibility, roles, and profile content.</p>
</section>

<?php if ($notice !== ''): ?>
  <section class="staff-section"><div class="staff-note"><?= h($notice) ?></div></section>
<?php endif; ?>

<?php if ($error !== ''): ?>
  <section class="staff-section"><div class="staff-note" style="color:#7f1d1d;border-color:rgba(239,68,68,.24);"><?= h($error) ?></div></section>
<?php endif; ?>

<?php if ($warn !== ''): ?>
  <section class="staff-section"><div class="staff-note" style="color:#7c2d12;border-color:rgba(245,158,11,.24);"><?= h($warn) ?></div></section>
<?php endif; ?>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Contributor details</h2>
          <p style="margin:6px 0 0;color:#667085;"><?= h((string)($contributor['slug'] ?? '')) ?></p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h($back_url) ?>">Back</a>
          <a class="staff-chip" href="<?= h($edit_url) ?>">Edit</a>
          <a class="staff-chip" href="<?= h($del_url) ?>" style="color:#7f1d1d;border-color:rgba(239,68,68,.24);">Delete</a>
        </div>
      </div>

      <?php if ($contributor): ?>
        <div style="margin-top:14px;display:grid;grid-template-columns:220px 1fr;gap:12px;">
          <div class="staff-note"><strong>ID</strong></div><div class="staff-note"><?= (int)$contributor['id'] ?></div>
          <div class="staff-note"><strong>Display Name</strong></div><div class="staff-note"><?= h($name) ?></div>
          <div class="staff-note"><strong>Slug</strong></div><div class="staff-note"><?= h((string)($contributor['slug'] ?? '')) ?></div>
          <div class="staff-note"><strong>Email</strong></div><div class="staff-note"><?= h((string)($contributor['email'] ?? '')) ?></div>
          <div class="staff-note"><strong>Status</strong></div><div class="staff-note"><?= h($status_pill['text']) ?></div>
          <div class="staff-note"><strong>Visibility</strong></div><div class="staff-note"><?= $is_public ? 'public' : 'private' ?></div>
          <div class="staff-note"><strong>Roles</strong></div><div class="staff-note"><?= h($roles !== '' ? $roles : '—') ?></div>
          <div class="staff-note"><strong>Created</strong></div><div class="staff-note"><?= h((string)($contributor['created_at'] ?? '')) ?></div>
          <div class="staff-note"><strong>Updated</strong></div><div class="staff-note"><?= h((string)($contributor['updated_at'] ?? '')) ?></div>
        </div>

        <div style="margin-top:14px;">
          <div style="font-weight:700;margin-bottom:8px;">Bio</div>
          <div class="staff-note">
            <?php if ($bio_html !== ''): ?>
              <?= $bio_html ?>
            <?php elseif ($bio_text !== ''): ?>
              <?= nl2br(h($bio_text), false) ?>
            <?php else: ?>
              <span style="color:#667085;">No bio available.</span>
            <?php endif; ?>
          </div>
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
