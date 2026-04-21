<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
mk_require_staff_login();

require_once APP_ROOT . '/private/functions/contributions.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!function_exists('h')) {
    function h(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('url_for')) {
    function url_for(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}

$status = trim((string)($_GET['status'] ?? 'pending'));
$allowedStatuses = ['pending', 'approved', 'rejected', 'deleted', 'all'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'pending';
}

$limit = 100;
$rows = mk_contribution_list($status, $limit);

$page_title = 'Contributions • Staff';
$page_desc  = 'Review public submissions, attachments, and moderation status.';
$nav_active = 'contributions';
$active_nav = 'contributions';

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
  <h1 class="staff-pagehead__title">Contributions</h1>
  <p class="staff-pagehead__desc">
    Review pending submissions, uploaded files, and moderation outcomes.
  </p>
</section>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Submission queue</h2>
          <p style="margin:6px 0 0;color:#667085;">
            Showing <?= h((string)count($rows)) ?> item(s) for status:
            <strong><?= h($status) ?></strong>
          </p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h(url_for('/staff/contributions/?status=pending')) ?>">Pending</a>
          <a class="staff-chip" href="<?= h(url_for('/staff/contributions/?status=approved')) ?>">Approved</a>
          <a class="staff-chip" href="<?= h(url_for('/staff/contributions/?status=rejected')) ?>">Rejected</a>
          <a class="staff-chip" href="<?= h(url_for('/staff/contributions/?status=all')) ?>">All</a>
          <a class="staff-chip" href="<?= h(url_for('/staff/')) ?>">Dashboard</a>
        </div>
      </div>

      <div style="margin-top:14px;overflow:auto;border:1px solid rgba(17,24,39,.10);border-radius:14px;background:#fff;">
        <table style="width:100%;border-collapse:collapse;min-width:1120px;">
          <thead>
            <tr style="background:rgba(17,24,39,.04);">
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">ID</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Created</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Subject</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Type</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Title</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Contributor</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Page</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">File</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Status</th>
              <th style="text-align:left;padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.10);">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows): ?>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);white-space:nowrap;"><?= h((string)$r['id']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);white-space:nowrap;"><?= h((string)$r['created_at']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h((string)$r['subject_area']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);"><?= h((string)$r['submission_type']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);font-weight:700;"><?= h((string)$r['title']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);">
                    <?= h((string)$r['contributor_name']) ?><br>
                    <span style="color:#667085;"><?= h((string)$r['contributor_email']) ?></span>
                  </td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);color:#667085;"><?= h((string)$r['page_path']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);">
                    <?= !empty($r['file_storage_name']) ? 'Yes' : 'No' ?>
                  </td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);font-weight:700;"><?= h((string)$r['status']) ?></td>
                  <td style="padding:12px 14px;border-bottom:1px solid rgba(17,24,39,.08);white-space:nowrap;">
                    <a class="staff-chip" href="<?= h(url_for('/staff/contributions/show.php?id=' . (int)$r['id'])) ?>">Open</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" style="padding:18px 14px;color:#667085;">No contribution records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
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