<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
auth_require_role('staff');
@ini_set('display_errors','0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
$staffUser   = $_SESSION['staff_user']    ?? [];
$staffUserId = $_SESSION['staff_user_id'] ?? null;
$staffEmail  = is_array($staffUser) ? (string)($staffUser['email'] ?? '') : '';
$staffRole   = is_array($staffUser) ? (string)($staffUser['role']  ?? 'staff') : 'staff';
$staff_header = (defined('PRIVATE_PATH') && PRIVATE_PATH !== '') ? rtrim(PRIVATE_PATH,"/\\").'/shared/staff_header.php' : (defined('APP_ROOT') && APP_ROOT !== '' ? rtrim(APP_ROOT,"/\\").'/app/mkomigbo/private/shared/staff_header.php' : '');
$staff_footer = (defined('PRIVATE_PATH') && PRIVATE_PATH !== '') ? rtrim(PRIVATE_PATH,"/\\").'/shared/staff_footer.php' : (defined('APP_ROOT') && APP_ROOT !== '' ? rtrim(APP_ROOT,"/\\").'/app/mkomigbo/private/shared/staff_footer.php' : '');
if ($staff_header && is_file($staff_header)) require $staff_header;

// ── Stats ──────────────────────────────────────────────
$stats = ['contributors'=>0,'subjects'=>0,'platforms'=>0,'pages'=>0];
try {
  $pdo = db();
  $stats['contributors'] = (int)$pdo->query('SELECT COUNT(*) FROM contributors')->fetchColumn();
  $stats['subjects']     = (int)$pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
  $stats['pages']        = (int)$pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
  try { $stats['platforms'] = (int)$pdo->query('SELECT COUNT(*) FROM platforms')->fetchColumn(); } catch(Throwable $e){}
} catch(Throwable $e){}

// ── AWAG feedback ──────────────────────────────────────
$fb_dir = '/home/mkomigbo/public_html/awag/feedback/';
$fb_pending = 0; $fb_total = 0; $fb_recent = [];
if (is_dir($fb_dir)) {
  foreach (glob($fb_dir.'*.json') as $file) {
    if (strpos(basename($file),'rate_')===0) continue;
    $entries = json_decode(file_get_contents($file), true);
    if (!is_array($entries)) continue;
    foreach ($entries as $e) {
      $fb_total++;
      if (($e['status']??'pending')==='pending') $fb_pending++;
      if (count($fb_recent)<3) $fb_recent[] = $e;
    }
  }
}
?>
<section class="staff-pagehead">
  <h1 class="staff-pagehead__title">Staff Dashboard</h1>
  <p class="staff-pagehead__desc">Welcome back<?= $staffEmail!==''?', '.h($staffEmail):'' ?>. Role: <strong><?= h($staffRole) ?></strong></p>
</section>

<!-- Stats row -->
<section class="staff-section">
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;">
    <?php foreach([
      ['Contributors',$stats['contributors'],'/staff/contributors/','#2b6cb0'],
      ['Subjects',$stats['subjects'],'/staff/subjects/','#276749'],
      ['Platforms',$stats['platforms'],'/staff/platforms/','#805ad5'],
      ['Pages',$stats['pages'],'/staff/subjects/pgs/','#b7791f'],
    ] as [$label,$n,$href,$color]): ?>
    <a href="<?= h($href) ?>" style="display:block;text-decoration:none;padding:16px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;border-top:4px solid <?= h($color) ?>;transition:box-shadow .12s" onmouseover="this.style.boxShadow='0 6px 20px rgba(0,0,0,.10)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:1.8rem;font-weight:900;color:<?= h($color) ?>"><?= $n ?></div>
      <div style="font-size:.8rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-top:3px"><?= h($label) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Quick links -->
<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <h2 style="margin:0 0 12px;font-size:1rem;font-weight:900;">Quick Links</h2>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php foreach([
          ['/staff/contributors/','Contributors','#2b6cb0'],
          ['/staff/subjects/','Subjects','#276749'],
          ['/staff/platforms/','Platforms','#805ad5'],
          ['/staff/contributions/','Contributions','#b7791f'],
          ['/staff/tools/','Tools','#374151'],
          ['/staff/audit/','Audit Log','#374151'],
          ['/awag/admin.php','AWAG Feedback','#c85e28'],
          ['/awag/','AWAG →','#0a3a4a'],
        ] as [$href,$label,$color]): ?>
        <a href="<?= h($href) ?>" style="padding:8px 16px;border-radius:10px;text-decoration:none;font-weight:700;font-size:.86rem;background:<?= h($color) ?>;color:#fff;opacity:.92" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.92'"><?= h($label) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- AWAG Feedback panel -->
<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
        <h2 style="margin:0;font-size:1rem;font-weight:900;">🌍 AWAG Community Feedback</h2>
        <a href="/awag/admin.php" style="padding:7px 14px;border-radius:9px;background:#c85e28;color:#fff;text-decoration:none;font-weight:700;font-size:.84rem;">Open Admin Panel →</a>
      </div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
        <div style="padding:10px 18px;border:1px solid #e5e7eb;border-radius:12px;background:#fff8f5;text-align:center">
          <div style="font-size:1.5rem;font-weight:900;color:#c85e28"><?= $fb_pending ?></div>
          <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Pending</div>
        </div>
        <div style="padding:10px 18px;border:1px solid #e5e7eb;border-radius:12px;background:#f9fafb;text-align:center">
          <div style="font-size:1.5rem;font-weight:900;color:#374151"><?= $fb_total ?></div>
          <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Total</div>
        </div>
      </div>
      <?php if($fb_recent): ?>
      <div style="font-size:.8rem;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Recent submissions</div>
      <?php foreach($fb_recent as $e): ?>
      <div style="padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;margin-bottom:8px;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:4px;">
          <span style="padding:2px 8px;border-radius:999px;font-size:.72rem;font-weight:700;background:rgba(200,94,40,.1);color:#c85e28;border:1px solid rgba(200,94,40,.2)"><?= h($e['type']??'general') ?></span>
          <?php if(!empty($e['skin'])): ?><span style="padding:2px 8px;border-radius:999px;font-size:.72rem;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb"><?= h($e['skin']) ?></span><?php endif; ?>
          <span style="font-size:.72rem;color:#9ca3af"><?= h(substr($e['timestamp']??'',0,10)) ?></span>
        </div>
        <div style="font-size:.84rem;color:#374151"><?= h(substr($e['correct']??$e['notes']??$e['community']??'—',0,120)) ?></div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div style="color:#9ca3af;font-size:.88rem">No feedback submissions yet. Share AWAG with your community to start receiving corrections.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Session info (compact) -->
<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <h2 style="margin:0 0 10px;font-size:.9rem;font-weight:900;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em">Session</h2>
      <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:.88rem;color:#374151">
        <span><strong>ID:</strong> <?= $staffUserId!==null?h((string)$staffUserId):'—' ?></span>
        <span><strong>Email:</strong> <?= $staffEmail!==''?h($staffEmail):'—' ?></span>
        <span><strong>Role:</strong> <?= h($staffRole) ?></span>
        <span><strong>Session:</strong> <?= session_status()===PHP_SESSION_ACTIVE?'Active':'Inactive' ?></span>
      </div>
    </div>
  </div>
</section>
<?php
if ($staff_footer && is_file($staff_footer)) require $staff_footer;
else echo '</main></body></html>';
