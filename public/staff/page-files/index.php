<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

/**
 * /public/staff/page-files/index.php
 * Staff: Attachments (page_files) browser.
 *
 * Features:
 * - filter by page_id, external/local, kind, source_key (if columns exist)
 * - open/download actions use existing /staff/page-files/open.php and download.php
 * - delete uses existing /staff/pages/attachments_delete.php
 * - optional "Copy signed link (15m)" if staff_signed_open helper exists
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
/* Auth */
auth_require_role('staff');

/* Minimal fallbacks */
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('staff_csrf_field')) {
  function staff_csrf_field(): string {
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . h((string)$_SESSION['csrf_token']) . '">';
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
      return (bool)$cache[$key];
    } catch (Throwable $e) {
      $cache[$key] = false;
      return false;
    }
  }
}

$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

$pdo = (function_exists('db') && db() instanceof PDO) ? db() : null;
if (!$pdo instanceof PDO) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Database handle not available.\n";
  exit;
}

/* Ensure page_files exists */
$pageFilesExists = false;
try {
  $pdo->query("SELECT 1 FROM page_files LIMIT 1");
  $pageFilesExists = true;
} catch (Throwable $e) {
  $pageFilesExists = false;
}

/* Signed URL helper (optional, load once) */
$mk_signed_open_url = null;
try {
  if (defined('PRIVATE_PATH') && is_string(PRIVATE_PATH) && PRIVATE_PATH !== '') {
    $sf = rtrim(PRIVATE_PATH, '/\\') . '/functions/staff_signed_open.php';
    if (is_file($sf)) require_once $sf;
    if (function_exists('mk_staff_signed_open_url')) {
      $mk_signed_open_url = static fn(int $fid, int $pid): string => mk_staff_signed_open_url($fid, $pid, 900);
    }
  }
} catch (Throwable $e) {}

$active_nav = 'page-files';
$page_title = 'Attachments • Staff';
require_once APP_ROOT . '/app/mkomigbo/private/shared/staff_header.php';

?>
<div class="container">

  <div class="hero">
    <div class="hero__row">
      <div>
        <h1 class="hero__title">Attachments</h1>
        <p class="hero__sub">Browse and manage <code>page_files</code>.</p>
      </div>
      <div class="hero__actions">
        <a class="btn btn--ghost" href="<?php echo h($u('/staff/')); ?>">← Staff</a>
        <a class="btn" href="<?php echo h($u('/staff/page-files/normalize.php')); ?>">Normalize / Backfill</a>
      </div>
    </div>
  </div>

  <?php if (!$pageFilesExists): ?>
    <div class="alert alert--danger">
      Table <code>page_files</code> is not accessible.
    </div>
  <?php else: ?>

    <?php
      $has_kind      = pf__column_exists($pdo, 'page_files', 'kind');
      $has_sourcekey = pf__column_exists($pdo, 'page_files', 'source_key');
      $has_sourcelab = pf__column_exists($pdo, 'page_files', 'source_label');
      $has_host      = pf__column_exists($pdo, 'page_files', 'host');
      $has_canon     = pf__column_exists($pdo, 'page_files', 'canonical_url');

      $page_id  = (int)($_GET['page_id'] ?? 0);
      $is_ext   = trim((string)($_GET['external'] ?? ''));  // '', '1', '0'
      $kind     = trim((string)($_GET['kind'] ?? ''));
      $source_k = trim((string)($_GET['source_key'] ?? ''));

      $where = [];
      $bind  = [];

      if ($page_id > 0) { $where[] = 'page_id = :pid'; $bind[':pid'] = $page_id; }
      if ($is_ext === '1') { $where[] = 'is_external = 1'; }
      if ($is_ext === '0') { $where[] = 'is_external = 0'; }

      if ($has_kind && $kind !== '') { $where[] = 'kind = :k'; $bind[':k'] = $kind; }
      if ($has_sourcekey && $source_k !== '') { $where[] = 'source_key = :sk'; $bind[':sk'] = $source_k; }

      $cols = [
        'id','page_id','is_external',
        'original_name','stored_name','file_path','stored_path',
        'external_url','external_host',
        'mime_type','file_size','created_at'
      ];
      if ($has_kind)      $cols[] = 'kind';
      if ($has_sourcekey) $cols[] = 'source_key';
      if ($has_sourcelab) $cols[] = 'source_label';
      if ($has_host)      $cols[] = 'host';
      if ($has_canon)     $cols[] = 'canonical_url';

      $sql = "SELECT " . implode(', ', array_unique($cols)) . " FROM page_files";
      if ($where) $sql .= " WHERE " . implode(" AND ", $where);
      $sql .= " ORDER BY id DESC LIMIT 200";

      $st = $pdo->prepare($sql);
      foreach ($bind as $k => $v) {
        $st->bindValue($k, is_int($v) ? $v : (string)$v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
      }
      $st->execute();
      $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    ?>

    <div class="card">
      <div class="card__body">
        <form method="get" action="<?php echo h($u('/staff/page-files/index.php')); ?>" class="row row--gap" style="flex-wrap:wrap; align-items:flex-end;">
          <div class="field" style="min-width:160px;">
            <label class="label">page_id</label>
            <input class="input mono" type="number" name="page_id" value="<?php echo h($page_id > 0 ? (string)$page_id : ''); ?>" placeholder="e.g. 123">
          </div>

          <div class="field" style="min-width:160px;">
            <label class="label">External</label>
            <select class="input" name="external">
              <option value="" <?php echo $is_ext===''?'selected':''; ?>>All</option>
              <option value="0" <?php echo $is_ext==='0'?'selected':''; ?>>Local only</option>
              <option value="1" <?php echo $is_ext==='1'?'selected':''; ?>>External only</option>
            </select>
          </div>

          <?php if ($has_kind): ?>
            <div class="field" style="min-width:160px;">
              <label class="label">kind</label>
              <input class="input mono" name="kind" value="<?php echo h($kind); ?>" placeholder="doc, image, video...">
            </div>
          <?php endif; ?>

          <?php if ($has_sourcekey): ?>
            <div class="field" style="min-width:180px;">
              <label class="label">source_key</label>
              <input class="input mono" name="source_key" value="<?php echo h($source_k); ?>" placeholder="wikipedia:..., youtube:...">
            </div>
          <?php endif; ?>

          <div class="row row--gap">
            <button class="btn btn--primary" type="submit">Filter</button>
            <a class="btn btn--ghost" href="<?php echo h($u('/staff/page-files/index.php')); ?>">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card" style="margin-top:14px;">
      <div class="card__body">
        <h2 style="margin:0;">Latest (max 200)</h2>

        <?php if (!$rows): ?>
          <p class="muted" style="margin-top:10px;"><em>No results.</em></p>
        <?php else: ?>
          <div style="margin-top:12px; overflow:auto;">
            <table class="table" style="width:100%; table-layout:fixed;">
              <thead>
                <tr>
                  <th style="width:72px;">ID</th>
                  <th style="width:84px;">page_id</th>
                  <th style="width:46%;">Name / URL</th>
                  <th style="width:26%;">Meta</th>
                  <th style="width:220px; text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <?php
                    $id  = (int)($r['id'] ?? 0);
                    $pid = (int)($r['page_id'] ?? 0);
                    $isExternal = ((int)($r['is_external'] ?? 0) === 1) || (!empty($r['external_url'] ?? ''));

                    $name = trim((string)($r['original_name'] ?? ''));
                    if ($name === '') $name = $isExternal ? 'External link' : 'Attachment';

                    $meta = [];
                    if ($has_kind && !empty($r['kind'])) $meta[] = (string)$r['kind'];
                    if ($has_sourcelab && !empty($r['source_label'])) $meta[] = (string)$r['source_label'];
                    if ($has_host && !empty($r['host'])) $meta[] = (string)$r['host'];
                    if (!empty($r['mime_type'])) $meta[] = (string)$r['mime_type'];
                    if (!$isExternal && !empty($r['file_size'])) $meta[] = ((int)$r['file_size']) . ' B';

                    $detail = $isExternal
                      ? trim((string)($r['external_url'] ?? ''))
                      : trim((string)($r['file_path'] ?? $r['stored_path'] ?? ''));

                    $open_action     = $u('/staff/page-files/open.php');
                    $download_action = $u('/staff/page-files/download.php');
                    $delete_action   = $u('/staff/pages/attachments_delete.php');
                    $csrf            = staff_csrf_field();
                    $return          = '/staff/page-files/index.php';

                    $signedCopy = is_callable($mk_signed_open_url) ? (string)$mk_signed_open_url($id, $pid) : '';
                  ?>
                  <tr>
                    <td class="mono" style="white-space:nowrap;"><?php echo (int)$id; ?></td>
                    <td class="mono" style="white-space:nowrap;"><?php echo (int)$pid; ?></td>
                    <td>
                      <div style="font-weight:800;"><?php echo h($name); ?></div>
                      <?php if ($detail !== ''): ?>
                        <div class="muted" style="margin-top:4px; font-size:.92rem; word-break:break-word; overflow-wrap:anywhere;"><?php echo h($detail); ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="muted" style="overflow-wrap:anywhere;"><?php echo h(implode(' • ', array_filter($meta))); ?></td>
                    <td style="text-align:right; white-space:normal;">

                      <?php if ($isExternal): ?>
                        <form method="post" action="<?php echo h($open_action); ?>" target="_blank" style="display:inline;">
                          <?php echo $csrf; ?>
                          <input type="hidden" name="file_id" value="<?php echo (int)$id; ?>">
                          <input type="hidden" name="page_id" value="<?php echo (int)$pid; ?>">
                          <button class="btn" type="submit">Open</button>
                        </form>
                      <?php else: ?>
                        <form method="post" action="<?php echo h($open_action); ?>" target="_blank" style="display:inline;">
                          <?php echo $csrf; ?>
                          <input type="hidden" name="file_id" value="<?php echo (int)$id; ?>">
                          <input type="hidden" name="page_id" value="<?php echo (int)$pid; ?>">
                          <button class="btn" type="submit">Open</button>
                        </form>

                        <form method="post" action="<?php echo h($download_action); ?>" target="_blank" style="display:inline; margin-left:6px;">
                          <?php echo $csrf; ?>
                          <input type="hidden" name="file_id" value="<?php echo (int)$id; ?>">
                          <input type="hidden" name="page_id" value="<?php echo (int)$pid; ?>">
                          <button class="btn btn--ghost" type="submit">Download</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($signedCopy !== ''): ?>
                        <button
                          type="button"
                          class="btn btn--secondary"
                          data-copy="<?php echo h($signedCopy); ?>"
                          data-copied-text="Copied"
                          data-copy-reset-ms="1200"
                          style="margin-left:6px;"
                        >Copy signed link (15m)</button>
                      <?php endif; ?>

                      <form method="post" action="<?php echo h($delete_action); ?>" style="display:inline; margin-left:6px;">
                        <?php echo $csrf; ?>
                        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                        <input type="hidden" name="page_id" value="<?php echo (int)$pid; ?>">
                        <input type="hidden" name="return" value="<?php echo h($return); ?>">
                        <button class="btn btn--ghost" type="submit" onclick="return confirm('Delete this attachment?');">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>

  <?php endif; ?>
</div>
<?php require_once APP_ROOT . '/app/mkomigbo/private/shared/staff_footer.php'; ?>
