<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
mk_require_staff_login();

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
if (!function_exists('redirect_to')) {
  function redirect_to(string $location, int $code = 302): void {
    $location = str_replace(["\r","\n"], '', $location);
    header('Location: ' . $location, true, $code);
    exit;
  }
}
if (!function_exists('pf__u')) {
  function pf__u(string $path): string { return function_exists('url_for') ? (string)url_for($path) : $path; }
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
    $msg = '';
    if (isset($_SESSION['flash']) && is_array($_SESSION['flash']) && array_key_exists($key, $_SESSION['flash'])) {
      $msg = (string)$_SESSION['flash'][$key];
      unset($_SESSION['flash'][$key]);
    }
    return $msg;
  }
}
if (!function_exists('pf__pdo')) {
  function pf__pdo(): ?PDO {
    if (function_exists('staff_pdo')) {
      $pdo = staff_pdo();
      if ($pdo instanceof PDO) return $pdo;
    }
    if (function_exists('db')) {
      $pdo = db();
      if ($pdo instanceof PDO) return $pdo;
    }
    return null;
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
if (!function_exists('pf__page_title_of')) {
  function pf__page_title_of(array $row): string {
    foreach (['title','menu_name','name'] as $k) {
      if (isset($row[$k]) && trim((string)$row[$k]) !== '') return trim((string)$row[$k]);
    }
    return 'Page #' . (string)($row['id'] ?? '0');
  }
}

$pdo = pf__pdo();
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

$id = (int)($_GET['id'] ?? 0);
$return = pf__safe_return_url((string)($_GET['return'] ?? ''), '/staff/subjects/pgs/index.php');
if ($id <= 0) redirect_to(pf__u($return));

$notice = pf__flash_get('notice');
$error  = pf__flash_get('error');

$titleCol = pf__column_exists($pdo, 'pages', 'title') ? 'title'
         : (pf__column_exists($pdo, 'pages', 'menu_name') ? 'menu_name'
         : (pf__column_exists($pdo, 'pages', 'name') ? 'name' : null));

$bodyCol = pf__column_exists($pdo, 'pages', 'body_html') ? 'body_html'
        : (pf__column_exists($pdo, 'pages', 'body') ? 'body'
        : (pf__column_exists($pdo, 'pages', 'content') ? 'content' : null));

$orderCol = pf__column_exists($pdo, 'pages', 'nav_order') ? 'nav_order'
         : (pf__column_exists($pdo, 'pages', 'position') ? 'position' : null);

$hasSlug      = pf__column_exists($pdo, 'pages', 'slug');
$hasSubjectId = pf__column_exists($pdo, 'pages', 'subject_id');
$hasIsPublic  = pf__column_exists($pdo, 'pages', 'is_public');
$hasVisible   = pf__column_exists($pdo, 'pages', 'visible');
$hasStatus    = pf__column_exists($pdo, 'pages', 'status');
$hasWorkflow  = pf__column_exists($pdo, 'pages', 'workflow_state');
$hasCreatedAt = pf__column_exists($pdo, 'pages', 'created_at');
$hasUpdatedAt = pf__column_exists($pdo, 'pages', 'updated_at');

$cols = ['p.id'];
if ($titleCol !== null) $cols[] = 'p.' . $titleCol . ' AS title';
if ($bodyCol !== null)  $cols[] = 'p.' . $bodyCol . ' AS body';
if ($hasSlug)           $cols[] = 'p.slug';
if ($hasSubjectId)      $cols[] = 'p.subject_id';
if ($orderCol !== null) $cols[] = 'p.' . $orderCol . ' AS nav_order';
if ($hasIsPublic)       $cols[] = 'p.is_public';
if ($hasVisible)        $cols[] = 'p.visible';
if ($hasStatus)         $cols[] = 'p.status';
if ($hasWorkflow)       $cols[] = 'p.workflow_state';
if ($hasCreatedAt)      $cols[] = 'p.created_at';
if ($hasUpdatedAt)      $cols[] = 'p.updated_at';

$subTitleExpr = "''";
$subSlugExpr  = "''";
if ($hasSubjectId) {
  if (pf__column_exists($pdo, 'subjects', 'menu_name')) $subTitleExpr = 's.menu_name';
  elseif (pf__column_exists($pdo, 'subjects', 'name')) $subTitleExpr = 's.name';
  if (pf__column_exists($pdo, 'subjects', 'slug')) $subSlugExpr = 's.slug';
  $cols[] = $subTitleExpr . ' AS subject_title';
  $cols[] = $subSlugExpr . ' AS subject_slug';
}

$sql = "SELECT " . implode(', ', $cols) . " FROM pages p";
if ($hasSubjectId) $sql .= " LEFT JOIN subjects s ON s.id = p.subject_id";
$sql .= " WHERE p.id = :id LIMIT 1";

$st = $pdo->prepare($sql);
$st->execute([':id' => $id]);
$page = $st->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$page) {
  if (function_exists('pf__flash_set')) pf__flash_set('error', 'Page not found.');
  redirect_to(pf__u($return));
}

$title = trim((string)($page['title'] ?? ''));
if ($title === '') $title = 'Page #' . $id;

$statusText = 'Draft';
if ($hasIsPublic) $statusText = ((int)($page['is_public'] ?? 0) === 1) ? 'Public' : 'Draft';
elseif ($hasVisible) $statusText = ((int)($page['visible'] ?? 0) === 1) ? 'Visible' : 'Hidden';
elseif ($hasWorkflow && trim((string)($page['workflow_state'] ?? '')) !== '') $statusText = trim((string)$page['workflow_state']);
elseif ($hasStatus && trim((string)($page['status'] ?? '')) !== '') $statusText = trim((string)$page['status']);

$editUrl = pf__u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));
$delUrl  = pf__u('/staff/subjects/pgs/delete.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($return));

$page_title = 'View Page • Staff';
$active_nav = 'pgs';
require_once APP_ROOT . '/private/shared/staff_header.php';
?>
<div class="container">
  <div class="hero">
    <div class="hero__row">
      <div>
        <h1 class="hero__title">Page</h1>
        <p class="hero__sub"><?= h($title) ?></p>
      </div>
      <div class="hero__actions">
        <a class="btn btn--ghost" href="<?= h(pf__u($return)) ?>">Back</a>
        <a class="btn" href="<?= h($editUrl) ?>">Edit</a>
        <a class="btn btn--danger" href="<?= h($delUrl) ?>">Delete</a>
      </div>
    </div>
  </div>

  <?php if ($notice !== ''): ?><div class="alert alert--success"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert alert--danger"><?= h($error) ?></div><?php endif; ?>

  <div class="card">
    <div class="card__body stack">
      <div class="row row--wrap row--gap">
        <div class="pill <?= in_array(strtolower($statusText), ['public','published','active','visible'], true) ? 'pill--success' : 'pill--muted' ?>">
          <?= h($statusText) ?>
        </div>
      </div>

      <div class="grid" style="gap:12px;">
        <div><span class="muted">ID</span><div class="strong"><?= h((string)$page['id']) ?></div></div>
        <div><span class="muted">Subject</span><div class="strong"><?= h((string)($page['subject_title'] ?? '')) ?></div></div>
        <div><span class="muted">Slug</span><div class="mono"><?= h((string)($page['slug'] ?? '')) ?></div></div>
        <div><span class="muted"><?= h($orderCol === 'position' ? 'Position' : 'Nav order') ?></span><div class="mono"><?= h((string)($page['nav_order'] ?? '')) ?></div></div>
        <div><span class="muted">Created</span><div class="mono"><?= h((string)($page['created_at'] ?? '')) ?></div></div>
        <div><span class="muted">Updated</span><div class="mono"><?= h((string)($page['updated_at'] ?? '')) ?></div></div>
      </div>

      <hr class="sep">

      <div>
        <div class="muted" style="margin-bottom:6px;">Body</div>
        <div class="card" style="margin:0;">
          <div class="card__body">
            <?php
              $body = (string)($page['body'] ?? '');
              echo $body !== '' ? nl2br(h($body)) : '<span class="muted">— empty —</span>';
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once APP_ROOT . '/private/shared/staff_footer.php'; ?>
