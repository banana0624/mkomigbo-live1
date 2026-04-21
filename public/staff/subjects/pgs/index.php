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
if (!function_exists('pf__u')) {
  function pf__u(string $path): string { return function_exists('url_for') ? (string)url_for($path) : $path; }
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
if (!function_exists('pf__csrf_field')) {
  function pf__csrf_field(): string {
    mk_staff_session_start();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . h((string)$_SESSION['csrf_token']) . '">';
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
    foreach (['title', 'menu_name', 'name'] as $k) {
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

$hasTitle     = pf__column_exists($pdo, 'pages', 'title');
$hasMenuName  = pf__column_exists($pdo, 'pages', 'menu_name');
$hasName      = pf__column_exists($pdo, 'pages', 'name');
$hasSlug      = pf__column_exists($pdo, 'pages', 'slug');
$hasSubjectId = pf__column_exists($pdo, 'pages', 'subject_id');
$hasNavOrder  = pf__column_exists($pdo, 'pages', 'nav_order');
$hasPosition  = pf__column_exists($pdo, 'pages', 'position');
$hasIsPublic  = pf__column_exists($pdo, 'pages', 'is_public');
$hasVisible   = pf__column_exists($pdo, 'pages', 'visible');
$hasStatus    = pf__column_exists($pdo, 'pages', 'status');
$hasWorkflow  = pf__column_exists($pdo, 'pages', 'workflow_state');
$hasUpdatedAt = pf__column_exists($pdo, 'pages', 'updated_at');
$hasCreatedAt = pf__column_exists($pdo, 'pages', 'created_at');

$orderCol = $hasNavOrder ? 'nav_order' : ($hasPosition ? 'position' : null);

$q = trim((string)($_GET['q'] ?? ''));
$onlyDrafts = ((string)($_GET['only_unpub'] ?? '') === '1');

$returnParams = [];
if ($q !== '') $returnParams['q'] = $q;
if ($onlyDrafts) $returnParams['only_unpub'] = '1';

$returnPath = '/staff/subjects/pgs/index.php';
if ($returnParams) {
  $returnPath .= '?' . http_build_query($returnParams, '', '&', PHP_QUERY_RFC3986);
}

$notice = pf__flash_get('notice');
$error  = pf__flash_get('error');

$cols = ['p.id'];
if ($hasTitle)     $cols[] = 'p.title';
if ($hasMenuName)  $cols[] = 'p.menu_name';
if ($hasName)      $cols[] = 'p.name';
if ($hasSlug)      $cols[] = 'p.slug';
if ($hasSubjectId) $cols[] = 'p.subject_id';
if ($orderCol)     $cols[] = 'p.' . $orderCol . ' AS nav_order';
if ($hasIsPublic)  $cols[] = 'p.is_public';
if ($hasVisible)   $cols[] = 'p.visible';
if ($hasStatus)    $cols[] = 'p.status';
if ($hasWorkflow)  $cols[] = 'p.workflow_state';
if ($hasUpdatedAt) $cols[] = 'p.updated_at';
if ($hasCreatedAt) $cols[] = 'p.created_at';

$join = '';
if ($hasSubjectId) {
  $subTitleCol = null;
  if (pf__column_exists($pdo, 'subjects', 'menu_name')) $subTitleCol = 'menu_name';
  elseif (pf__column_exists($pdo, 'subjects', 'name')) $subTitleCol = 'name';

  if ($subTitleCol !== null) {
    $join = ' LEFT JOIN subjects s ON s.id = p.subject_id ';
    $cols[] = 's.' . $subTitleCol . ' AS subject_title';
  }
}

$sql = "SELECT " . implode(', ', $cols) . " FROM pages p" . $join . " WHERE 1=1";
$params = [];

if ($q !== '') {
  $parts = [];
  if ($hasTitle)    $parts[] = 'p.title LIKE :q';
  if ($hasMenuName) $parts[] = 'p.menu_name LIKE :q';
  if ($hasName)     $parts[] = 'p.name LIKE :q';
  if ($hasSlug)     $parts[] = 'p.slug LIKE :q';
  if ($join !== '') $parts[] = 'subject_title LIKE :q';
  if (!$parts)      $parts[] = 'CAST(p.id AS CHAR) LIKE :q';
  $sql .= ' AND (' . implode(' OR ', $parts) . ')';
  $params[':q'] = '%' . $q . '%';
}

if ($onlyDrafts) {
  if ($hasIsPublic) {
    $sql .= ' AND p.is_public = 0';
  } elseif ($hasVisible) {
    $sql .= ' AND p.visible = 0';
  } elseif ($hasStatus) {
    $sql .= " AND LOWER(COALESCE(p.status,'')) NOT IN ('published','active','public')";
  } elseif ($hasWorkflow) {
    $sql .= " AND LOWER(COALESCE(p.workflow_state,'')) <> 'published'";
  }
}

if ($orderCol) {
  $sql .= ' ORDER BY p.' . $orderCol . ' IS NULL, p.' . $orderCol . ' ASC, p.id ASC';
} else {
  $sql .= ' ORDER BY p.id DESC';
}

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

$page_title = 'Manage Pages • Staff';
$active_nav = 'pgs';
require_once APP_ROOT . '/private/shared/staff_header.php';

$listUrl = pf__u('/staff/subjects/pgs/index.php');
$newUrl  = pf__u('/staff/subjects/pgs/new.php?return=' . rawurlencode($returnPath));
$bulkUrl = pf__u('/staff/subjects/pgs/bulk.php');
?>
<style>
  .pgs-list-wrap{
    overflow-x:auto;
  }
  .pgs-list-table{
    width:100%;
    table-layout:auto;
    border-collapse:collapse;
  }
  .pgs-list-table th,
  .pgs-list-table td{
    vertical-align:top;
  }
  .pgs-col-check{
    width:44px;
  }
  .pgs-col-subject{
    width:180px;
    white-space:nowrap;
  }
  .pgs-col-status{
    width:120px;
    white-space:nowrap;
  }
  .pgs-col-order{
    width:96px;
    white-space:nowrap;
  }
  .pgs-col-actions{
    width:250px;
    white-space:nowrap;
  }
  .pgs-actions{
    display:flex;
    gap:8px;
    flex-wrap:nowrap;
    justify-content:flex-end;
  }
  .pgs-col-order .input{
    max-width:78px !important;
    margin-right:0;
  }
  .pgs-title-cell{
    min-width:260px;
  }
</style>

<div class="container" style="padding:24px 0;">
  <section class="hero">
    <div class="hero-bar"></div>
    <div class="hero-inner">
      <h1>Pages</h1>
      <p class="muted" style="margin:6px 0 0;">Manage page records, ordering, and publishing state.</p>
      <div class="actions" style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-primary" href="<?= h($newUrl) ?>">New Page</a>
        <a class="btn" href="<?= h(pf__u('/staff/')) ?>">Dashboard</a>
      </div>
    </div>
  </section>

  <?php if ($notice !== ''): ?>
    <div class="notice success" style="margin-top:14px;"><strong><?= h($notice) ?></strong></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="notice error" style="margin-top:14px;"><strong><?= h($error) ?></strong></div>
  <?php endif; ?>

  <section class="card" style="margin-top:14px;">
    <div class="form-card">
      <form method="get" action="<?= h($listUrl) ?>">
        <div class="row" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
          <div class="field" style="min-width:260px; flex:1;">
            <label class="label" for="q">Search</label>
            <input class="input" id="q" name="q" value="<?= h($q) ?>" placeholder="title / slug / subject">
          </div>

          <div class="field">
            <label style="display:flex; align-items:center; gap:10px; font-weight:700;">
              <input type="checkbox" name="only_unpub" value="1" <?= $onlyDrafts ? 'checked' : '' ?>>
              Only drafts
            </label>
          </div>

          <div class="field">
            <button class="btn" type="submit">Apply</button>
            <a class="btn" href="<?= h($listUrl) ?>">Clear</a>
          </div>
        </div>
      </form>

      <hr class="sep" style="margin:14px 0;">

      <form method="post" action="<?= h($bulkUrl) ?>">
        <?= pf__csrf_field() ?>
        <input type="hidden" name="return" value="<?= h($returnPath) ?>">

        <div class="actions" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between; margin-bottom:10px;">
          <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <button type="button" class="btn" data-js="select-all">Select all</button>
            <button type="button" class="btn" data-js="select-none">Select none</button>
            <button type="button" class="btn" data-js="select-invert">Invert</button>
            <button class="btn" type="submit" name="action" value="publish" onclick="return confirm('Publish selected pages?');">Publish selected</button>
            <button class="btn" type="submit" name="action" value="unpublish" onclick="return confirm('Unpublish selected pages?');">Unpublish selected</button>
            <?php if ($orderCol !== null): ?>
              <button class="btn" type="submit" name="action" value="save_order">Save order</button>
            <?php endif; ?>
          </div>
          <div class="muted"><?= count($rows) ?> row(s)</div>
        </div>

        <div class="pgs-list-wrap">
          <table class="table pgs-list-table">
            <thead>
              <tr>
                <th class="pgs-col-check"></th>
                <th class="pgs-title-cell">Title</th>
                <th>Slug</th>
                <th class="pgs-col-subject">Subject</th>
                <th class="pgs-col-status">Status</th>
                <?php if ($orderCol !== null): ?>
                  <th class="pgs-col-order" style="text-align:center;"><?= h($orderCol === 'position' ? 'Position' : 'Nav order') ?></th>
                <?php endif; ?>
                <th class="pgs-col-actions" style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr>
                  <td colspan="<?= $orderCol !== null ? '7' : '6' ?>" class="muted">No pages found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($rows as $r): ?>
                  <?php
                    $id = (int)($r['id'] ?? 0);
                    $label = pf__page_title_of($r);
                    $statusText = 'Draft';

                    if ($hasIsPublic) {
                      $statusText = ((int)($r['is_public'] ?? 0) === 1) ? 'Public' : 'Draft';
                    } elseif ($hasVisible) {
                      $statusText = ((int)($r['visible'] ?? 0) === 1) ? 'Visible' : 'Hidden';
                    } elseif ($hasWorkflow && trim((string)($r['workflow_state'] ?? '')) !== '') {
                      $statusText = trim((string)$r['workflow_state']);
                    } elseif ($hasStatus && trim((string)($r['status'] ?? '')) !== '') {
                      $statusText = trim((string)$r['status']);
                    }

                    $showUrl = pf__u('/staff/subjects/pgs/show.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($returnPath));
                    $editUrl = pf__u('/staff/subjects/pgs/edit.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($returnPath));
                    $delUrl  = pf__u('/staff/subjects/pgs/delete.php?id=' . rawurlencode((string)$id) . '&return=' . rawurlencode($returnPath));
                  ?>
                  <tr>
                    <td class="pgs-col-check"><input type="checkbox" name="ids[]" value="<?= $id ?>" class="js-bulk-cb"></td>
                    <td class="pgs-title-cell">
                      <div style="font-weight:700;"><?= h($label) ?></div>
                      <?php if (!empty($r['updated_at'])): ?>
                        <div class="muted" style="font-size:.92rem;">Updated: <?= h((string)$r['updated_at']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="mono"><?= h((string)($r['slug'] ?? '')) ?></td>
                    <td class="pgs-col-subject"><?= h((string)($r['subject_title'] ?? '')) ?></td>
                    <td class="pgs-col-status"><?= h($statusText) ?></td>
                    <?php if ($orderCol !== null): ?>
                      <td class="pgs-col-order" style="text-align:center;">
                        <input class="input mono" style="max-width:78px; text-align:center;" type="number" name="nav_order[<?= $id ?>]" value="<?= h((string)($r['nav_order'] ?? '')) ?>">
                      </td>
                    <?php endif; ?>
                    <td class="pgs-col-actions">
                      <div class="pgs-actions">
                        <a class="btn btn--ghost" href="<?= h($showUrl) ?>">View</a>
                        <a class="btn" href="<?= h($editUrl) ?>">Edit</a>
                        <a class="btn btn--danger" href="<?= h($delUrl) ?>">Delete</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </form>
    </div>
  </section>
</div>

<script>
(function () {
  var boxes = Array.prototype.slice.call(document.querySelectorAll('.js-bulk-cb'));
  function each(fn){ boxes.forEach(fn); }
  var all = document.querySelector('[data-js="select-all"]');
  var none = document.querySelector('[data-js="select-none"]');
  var inv = document.querySelector('[data-js="select-invert"]');
  if (all) all.addEventListener('click', function () { each(function (b) { b.checked = true;  }); });
  if (none) none.addEventListener('click', function () { each(function (b) { b.checked = false; }); });
  if (inv) inv.addEventListener('click', function () { each(function (b) { b.checked = !b.checked; }); });
})();
</script>
<?php require_once APP_ROOT . '/private/shared/staff_footer.php'; ?>