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
if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r","\n"], '', trim($location));
    if ($location === '') $location = '/staff/platforms/index.php';
    header('Location: ' . $location, true, 302);
    exit;
  }
}

$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$return = pf__safe_return_url((string)($_GET['return'] ?? '/staff/platforms/index.php'), '/staff/platforms/index.php');

if ($id <= 0) {
  redirect_to($return);
}

$st = $pdo->prepare("
  SELECT id, slug, name, description, status, is_public, sort_order, sections_json, created_at, updated_at, deleted_at
  FROM platforms
  WHERE id = ?
  LIMIT 1
");
$st->execute([$id]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  redirect_to($return);
}

$sections_pretty = '—';
if (!empty($row['sections_json'])) {
  $decoded = json_decode((string)$row['sections_json'], true);
  if ($decoded !== null) {
    $sections_pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  } else {
    $sections_pretty = (string)$row['sections_json'];
  }
}

$page_title = 'Platform • Staff';
$page_desc  = 'Review platform details and structured section data.';
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
?>

<section class="staff-pagehead">
  <h1 class="staff-pagehead__title"><?= h((string)$row['name']) ?></h1>
  <p class="staff-pagehead__desc">Review platform metadata, visibility, lifecycle status, and sections JSON.</p>
</section>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Platform details</h2>
          <p style="margin:6px 0 0;color:#667085;"><?= h((string)$row['slug']) ?></p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h($return) ?>">Back</a>
          <a class="staff-chip" href="<?= h('/staff/platforms/edit.php?id=' . rawurlencode((string)$row['id']) . '&return=' . rawurlencode($return)) ?>">Edit</a>
        </div>
      </div>

      <div style="margin-top:14px;display:grid;grid-template-columns:220px 1fr;gap:12px;">
        <div class="staff-note"><strong>ID</strong></div><div class="staff-note"><?= (int)$row['id'] ?></div>
        <div class="staff-note"><strong>Name</strong></div><div class="staff-note"><?= h((string)$row['name']) ?></div>
        <div class="staff-note"><strong>Slug</strong></div><div class="staff-note"><?= h((string)$row['slug']) ?></div>
        <div class="staff-note"><strong>Status</strong></div><div class="staff-note"><?= h((string)$row['status']) ?></div>
        <div class="staff-note"><strong>Visibility</strong></div><div class="staff-note"><?= ((int)$row['is_public'] === 1) ? 'public' : 'private' ?></div>
        <div class="staff-note"><strong>Sort Order</strong></div><div class="staff-note"><?= (int)$row['sort_order'] ?></div>
        <div class="staff-note"><strong>Deleted</strong></div><div class="staff-note"><?= $row['deleted_at'] === null ? 'No' : h((string)$row['deleted_at']) ?></div>
        <div class="staff-note"><strong>Created</strong></div><div class="staff-note"><?= h((string)$row['created_at']) ?></div>
        <div class="staff-note"><strong>Updated</strong></div><div class="staff-note"><?= h((string)$row['updated_at']) ?></div>
        <div class="staff-note"><strong>Description</strong></div>
        <div class="staff-note"><?= nl2br(h((string)($row['description'] ?? '')), false) ?></div>
      </div>

      <div style="margin-top:14px;">
        <div style="font-weight:700;margin-bottom:8px;">Sections JSON</div>
        <pre style="white-space:pre-wrap;word-break:break-word;background:#f9fafb;border:1px solid rgba(17,24,39,.10);border-radius:12px;padding:12px;margin:0;"><?= h($sections_pretty) ?></pre>
      </div>
    </div>
  </div>
</section>

<?php
if ($staff_footer && is_file($staff_footer)) {
  require $staff_footer;
} else {
  echo "</main></body></html>";
}
