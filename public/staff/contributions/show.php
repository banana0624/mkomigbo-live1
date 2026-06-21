<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
auth_require_role('staff');

require_once APP_ROOT . '/private/functions/contributions.php';

require_once APP_ROOT . '/private/functions/staff_flash.php';

if (function_exists('mk__session_start')) {
    mk__session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if (!function_exists('mk_contrib_staff_csrf_token')) {
    function mk_contrib_staff_csrf_token(): string
    {
        if (empty($_SESSION['staff_contrib_csrf']) || !is_string($_SESSION['staff_contrib_csrf'])) {
            $_SESSION['staff_contrib_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['staff_contrib_csrf'];
    }
}

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

$id = (int)($_GET['id'] ?? 0);
$row = $id > 0 ? mk_contribution_find($id) : null;
$flash = mk_staff_flash_get();
$moderationLog = ($row && !empty($row['id']))
    ? mk_contribution_moderation_log_list((int)$row['id'], 100)
    : [];

$page_title = 'Contribution Detail • Staff';
$page_desc  = 'Review one submitted contribution and any attached file.';
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
  <h1 class="staff-pagehead__title">Contribution detail</h1>
  <p class="staff-pagehead__desc">
    Inspect contributor details, message content, file metadata, and moderation state.
  </p>
</section>

<?php if ($flash): ?>
  <?php
    $flashType = strtolower((string)($flash['type'] ?? 'info'));
    $flashBg = '#eff6ff';
    $flashBorder = '#bfdbfe';
    $flashColor = '#1e3a8a';

    if ($flashType === 'success') {
        $flashBg = '#ecfdf3';
        $flashBorder = '#a7f3d0';
        $flashColor = '#065f46';
    } elseif ($flashType === 'error') {
        $flashBg = '#fef2f2';
        $flashBorder = '#fecaca';
        $flashColor = '#991b1b';
    }
  ?>
  <section class="staff-section" style="padding-top:0;">
    <div class="staff-card" style="border:1px solid <?= h($flashBorder) ?>;background:<?= h($flashBg) ?>;">
      <div class="staff-card__body" style="color:<?= h($flashColor) ?>;font-weight:600;">
        <?= h((string)$flash['message']) ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.15rem;">Record</h2>
          <p style="margin:6px 0 0;color:#667085;">
            ID:
            <strong><?= h((string)$id) ?></strong>
          </p>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h(url_for('/staff/contributions/')) ?>">Back to list</a>
        </div>
      </div>

      <?php if (!$row): ?>
        <div style="margin-top:16px;color:#667085;">Contribution record not found.</div>
      <?php else: ?>

        <div style="margin-top:16px;display:grid;grid-template-columns:repeat(2,minmax(280px,1fr));gap:14px;">
          <div style="border:1px solid rgba(17,24,39,.10);border-radius:14px;padding:14px;background:#fff;">
            <h3 style="margin-top:0;">Submission</h3>
            <div><strong>Public ref:</strong> <?= h((string)$row['public_ref']) ?></div>
            <div><strong>Status:</strong> <?= h((string)$row['status']) ?></div>
            <div><strong>Subject:</strong> <?= h((string)$row['subject_area']) ?></div>
            <div><strong>Type:</strong> <?= h((string)$row['submission_type']) ?></div>
            <div><strong>Position:</strong> <?= h((string)($row['position_type'] ?? '')) ?></div>
            <div><strong>Page:</strong> <?= h((string)$row['page_path']) ?></div>
            <div><strong>Created:</strong> <?= h((string)$row['created_at']) ?></div>
            <div><strong>Updated:</strong> <?= h((string)$row['updated_at']) ?></div>
          </div>

          <div style="border:1px solid rgba(17,24,39,.10);border-radius:14px;padding:14px;background:#fff;">
            <h3 style="margin-top:0;">Contributor</h3>
            <div><strong>Name:</strong> <?= h((string)$row['contributor_name']) ?></div>
            <div><strong>Email:</strong> <?= h((string)$row['contributor_email']) ?></div>
            <div><strong>IP:</strong> <?= h((string)($row['submitter_ip'] ?? '')) ?></div>
            <div><strong>User agent:</strong> <span style="color:#667085;"><?= h((string)($row['user_agent'] ?? '')) ?></span></div>
            <div><strong>Reviewed by:</strong> <?= h((string)($row['reviewed_by'] ?? '')) ?></div>
            <div><strong>Reviewed at:</strong> <?= h((string)($row['reviewed_at'] ?? '')) ?></div>
          </div>
        </div>

        <div style="margin-top:14px;border:1px solid rgba(17,24,39,.10);border-radius:14px;padding:14px;background:#fff;">
          <h3 style="margin-top:0;">Title</h3>
          <div><?= h((string)$row['title']) ?></div>
        </div>

        <div style="margin-top:14px;border:1px solid rgba(17,24,39,.10);border-radius:14px;padding:14px;background:#fff;">
          <h3 style="margin-top:0;">Message</h3>
          <div style="white-space:pre-wrap;line-height:1.6;"><?= h((string)$row['message_text']) ?></div>
        </div>

        <div style="margin-top:14px;border:1px solid rgba(17,24,39,.10);border-radius:14px;padding:14px;background:#fff;">
          <h3 style="margin-top:0;">Attachment</h3>

          <?php if (!empty($row['file_storage_name'])): ?>
            <div><strong>Original name:</strong> <?= h((string)$row['file_original_name']) ?></div>
            <div><strong>Stored name:</strong> <?= h((string)$row['file_storage_name']) ?></div>
            <div><strong>Relative path:</strong> <?= h((string)$row['file_relative_path']) ?></div>
            <div><strong>MIME:</strong> <?= h((string)$row['file_mime']) ?></div>
            <div><strong>Extension:</strong> <?= h((string)$row['file_ext']) ?></div>
            <div><strong>Size:</strong> <?= h((string)$row['file_size']) ?></div>
            <div><strong>SHA-256:</strong> <span style="font-family:monospace;word-break:break-all;"><?= h((string)$row['file_sha256']) ?></span></div>

            <div class="staff-actions" style="margin-top:12px;">
              <a class="staff-chip" href="<?= h(url_for('/staff/contributions/download.php?id=' . (int)$row['id'])) ?>">Download file</a>
            </div>
          <?php else: ?>
            <div style="color:#667085;">No file attached to this submission.</div>
          <?php endif; ?>
        </div>

        <?php if ((string)($row['moderation_note'] ?? '') !== ''): ?>
          <div style="margin-top:14px;border:1px solid rgba(17,24,39,.10);border-radius:14px;padding:14px;background:#fff;">
            <h3 style="margin-top:0;">Current moderation note</h3>
            <div style="white-space:pre-wrap;line-height:1.6;"><?= h((string)$row['moderation_note']) ?></div>
          </div>
        <?php endif; ?>

                <div style="margin-top:14px;border:1px solid rgba(17,24,39,.10);border-radius:14px;padding:14px;background:#fff;">
          <h3 style="margin-top:0;">Moderation actions</h3>

          <?php if (strtolower((string)($row['status'] ?? '')) === 'deleted'): ?>
            <div style="padding:12px 14px;border:1px solid #fecaca;border-radius:12px;background:#fef2f2;color:#991b1b;">
              This contribution is already marked as deleted. No further moderation actions are available.
            </div>
          <?php else: ?>

            <form method="post" action="<?= h(url_for('/staff/contributions/approve.php')) ?>" style="margin:0 0 14px;">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= h(mk_contrib_staff_csrf_token()) ?>">
              <label for="moderation_note_approve"><strong>Moderation note</strong> <span style="color:#667085;">(optional)</span></label><br>
              <textarea id="moderation_note_approve" name="moderation_note" rows="4" style="width:100%;max-width:780px;padding:10px;"></textarea>
              <div class="staff-actions" style="margin-top:10px;">
                <button type="submit" class="staff-chip" style="cursor:pointer;">Approve</button>
              </div>
            </form>

            <form method="post" action="<?= h(url_for('/staff/contributions/reject.php')) ?>" style="margin:0 0 14px;">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= h(mk_contrib_staff_csrf_token()) ?>">
              <label for="moderation_note_reject"><strong>Rejection note</strong> <span style="color:#667085;">(optional)</span></label><br>
              <textarea id="moderation_note_reject" name="moderation_note" rows="4" style="width:100%;max-width:780px;padding:10px;"></textarea>
              <div class="staff-actions" style="margin-top:10px;">
                <button type="submit" class="staff-chip" style="cursor:pointer;">Reject</button>
              </div>
            </form>

            <form method="post" action="<?= h(url_for('/staff/contributions/delete.php')) ?>" onsubmit="return confirm('Mark this contribution as deleted?');" style="margin:0;">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <input type="hidden" name="csrf_token" value="<?= h(mk_contrib_staff_csrf_token()) ?>">
              <label for="moderation_note_delete"><strong>Deletion note</strong> <span style="color:#667085;">(optional)</span></label><br>
              <textarea id="moderation_note_delete" name="moderation_note" rows="3" style="width:100%;max-width:780px;padding:10px;"></textarea>
              <div class="staff-actions" style="margin-top:10px;">
                <button type="submit" class="staff-chip" style="cursor:pointer;">Delete</button>
              </div>
            </form>

          <?php endif; ?>
        </div>
        
        <div style="margin-top:14px;border:1px solid rgba(17,24,39,.10);border-radius:14px;padding:14px;background:#fff;">
          <h3 style="margin-top:0;">Moderation log</h3>

          <?php if (empty($moderationLog)): ?>
            <div style="color:#667085;">No moderation log entries yet.</div>
          <?php else: ?>
            <div style="overflow-x:auto;">
              <table style="width:100%;border-collapse:collapse;">
                <thead>
                  <tr>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid rgba(17,24,39,.10);">Time</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid rgba(17,24,39,.10);">Action</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid rgba(17,24,39,.10);">Staff user</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid rgba(17,24,39,.10);">Note</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($moderationLog as $log): ?>
                    <tr>
                      <td style="vertical-align:top;padding:10px;border-bottom:1px solid rgba(17,24,39,.06);">
                        <?= h((string)($log['created_at'] ?? '')) ?>
                      </td>
                      <td style="vertical-align:top;padding:10px;border-bottom:1px solid rgba(17,24,39,.06);">
                        <?= h((string)($log['action_type'] ?? '')) ?>
                      </td>
                      <td style="vertical-align:top;padding:10px;border-bottom:1px solid rgba(17,24,39,.06);">
                        <?= h((string)($log['staff_user_id'] ?? '')) ?>
                      </td>
                      <td style="vertical-align:top;padding:10px;border-bottom:1px solid rgba(17,24,39,.06);white-space:pre-wrap;line-height:1.5;">
                        <?= h((string)($log['note_text'] ?? '')) ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
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