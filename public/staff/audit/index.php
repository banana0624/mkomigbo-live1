<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
auth_require_role('staff');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!function_exists('h')) {
  function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
  }
}
if (!function_exists('url_for')) {
  function url_for(string $path): string {
    return '/' . ltrim($path, '/');
  }
}

$pdo = db();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$off = ($page - 1) * $limit;

$rows = $pdo->prepare("
  SELECT id, ts, staff_user_id, event, uri, ip
  FROM staff_audit_log
  ORDER BY id DESC
  LIMIT :lim OFFSET :off
");
$rows->bindValue(':lim', $limit, PDO::PARAM_INT);
$rows->bindValue(':off', $off, PDO::PARAM_INT);
$rows->execute();

$data = $rows->fetchAll(PDO::FETCH_ASSOC);
$total = (int)$pdo->query("SELECT COUNT(*) FROM staff_audit_log")->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$page_title = 'Audit Log • Staff';
$page_desc  = 'Review staff authentication and security-related activity.';
$nav_active = 'audit';
$active_nav = 'audit';

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
  <h1 class="staff-pagehead__title">Audit log</h1>
  <p class="staff-pagehead__desc">
    Review login, logout, access denial, and other staff-side security events in one place.
  </p>
</section>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Recent activity</h2>
          <p style="margin:6px 0 0;color:#667085;">Showing <?= h((string)count($data)) ?> entries on this page out of <?= h((string)$total) ?> total.</p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h(url_for('/staff/')) ?>">Dashboard</a>
          <a class="staff-chip" href="<?= h(url_for('/staff/account/')) ?>">Account</a>
        </div>
      </div>

      <div style="margin-top:14px;overflow:auto;border:1px solid rgba(17,24,39,.10);border-radius:14px;background:#fff;">
        <table style="width:100%;border-collapse:collapse;min-width:860px;">
          <thead>
            <tr style="background:rgba(17,24,39,.04);">
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">ID</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Timestamp</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">User</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Event</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">IP</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">URI</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($data): ?>
              <?php foreach ($data as $r): ?>
                <tr>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h((string)$r['id']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);white-space:nowrap;"><?= h((string)$r['ts']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h((string)$r['staff_user_id']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);font-weight:700;"><?= h((string)$r['event']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);white-space:nowrap;"><?= h((string)$r['ip']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);color:#667085;"><?= h((string)$r['uri']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" style="padding:18px 14px;color:#667085;">No audit records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="staff-actions" style="margin-top:14px;justify-content:space-between;">
        <div style="color:#667085;">Page <?= h((string)$page) ?> of <?= h((string)$pages) ?></div>
        <div class="staff-actions">
          <?php if ($page > 1): ?>
            <a class="staff-chip" href="?page=<?= (int)($page - 1) ?>">Previous</a>
          <?php endif; ?>
          <?php if ($page < $pages): ?>
            <a class="staff-chip" href="?page=<?= (int)($page + 1) ?>">Next</a>
          <?php endif; ?>
        </div>
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