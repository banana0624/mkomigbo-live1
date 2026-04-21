<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

require_once PRIVATE_PATH . '/functions/auth.php';

if (!function_exists('mk_staff_current_id') || mk_staff_current_id() <= 0) {
    header('Location: ' . url_for('/staff/login.php'));
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

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
if (!function_exists('pf__public_root')) {
  function pf__public_root(): string {
    if (defined('PUBLIC_PATH') && is_string(PUBLIC_PATH) && PUBLIC_PATH !== '') {
      return rtrim(PUBLIC_PATH, "/\\");
    }
    $guess = realpath(dirname(__DIR__));
    return is_string($guess) && $guess !== ''
      ? rtrim($guess, "/\\")
      : rtrim(dirname(__DIR__), "/\\");
  }
}
if (!function_exists('pf__public_fs')) {
  function pf__public_fs(string $web_path): string {
    $root = pf__public_root();
    $rel  = ltrim($web_path, "/\\");
    return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
  }
}
if (!function_exists('pf__file_exists_web')) {
  function pf__file_exists_web(string $web_path): bool {
    return is_file(pf__public_fs($web_path));
  }
}
if (!function_exists('pf__dir_has_index')) {
  function pf__dir_has_index(string $web_dir): bool {
    $web_dir = trim($web_dir, "/\\") . '/';
    $dirFs = pf__public_fs($web_dir);
    if (!is_dir($dirFs)) return false;
    return is_file(pf__public_fs($web_dir . 'index.php'));
  }
}
if (!function_exists('pf__route_if_exists')) {
  function pf__route_if_exists(string $web_path): ?string {
    $web_path = '/' . ltrim($web_path, '/');
    $endsWithSlash = substr($web_path, -1) === '/';
    if ($endsWithSlash) {
      return pf__dir_has_index($web_path) ? url_for($web_path) : null;
    }
    return pf__file_exists_web($web_path) ? url_for($web_path) : null;
  }
}
if (!function_exists('mk_time_ago')) {
  function mk_time_ago(int $ts): string {
    if ($ts <= 0) return '—';
    $d = time() - $ts;
    if ($d < 0) $d = 0;
    if ($d < 60) return $d . 's ago';
    $m = (int)floor($d / 60);
    if ($m < 60) return $m . 'm ago';
    $h = (int)floor($m / 60);
    if ($h < 48) return $h . 'h ago';
    return (int)floor($h / 24) . 'd ago';
  }
}

$recent_tools = [];
if (isset($_SESSION['staff_tools_recent']) && is_array($_SESSION['staff_tools_recent'])) {
  foreach ($_SESSION['staff_tools_recent'] as $rt) {
    if (!is_array($rt)) continue;
    $k  = isset($rt['key'])   ? trim((string)$rt['key'])   : '';
    $t  = isset($rt['title']) ? trim((string)$rt['title']) : '';
    $u  = isset($rt['url'])   ? trim((string)$rt['url'])   : '';
    $ts = isset($rt['ts'])    ? (int)$rt['ts']             : 0;
    if ($k === '' || $u === '') continue;
    if ($t === '') $t = $k;

    $recent_tools[] = [
      'key'   => $k,
      'title' => $t,
      'url'   => $u,
      'ts'    => $ts,
    ];
  }
  usort($recent_tools, static fn(array $a, array $b): int => ($b['ts'] <=> $a['ts']));
  $recent_tools = array_slice($recent_tools, 0, 6);
}

$href_subjects = url_for('/staff/subjects/');
$href_pages    = url_for('/staff/subjects/pgs/');
$href_tools    = url_for('/staff/tools/');
$href_logout   = url_for('/staff/logout.php');
$href_account  = pf__route_if_exists('/staff/account/') ?? url_for('/staff/account/');
$href_password = url_for('/staff/account/password.php');
$href_audit    = url_for('/staff/audit/');
$href_platforms = pf__route_if_exists('/staff/platforms/') ?? url_for('/staff/platforms/');
$href_contrib   = pf__route_if_exists('/staff/contributors/') ?? url_for('/staff/contributors/');

$page_title = 'Staff Dashboard • Mkomi Igbo';
$page_desc  = 'Manage content, contributors, platforms, diagnostics, and account security.';
$nav_active = 'staff';
$active_nav = 'staff';

$cards = [
  ['title' => 'Subjects',      'desc' => 'Create, arrange, and manage subject areas.',              'href' => $href_subjects, 'tag' => 'Content'],
  ['title' => 'Pages',         'desc' => 'Edit, publish, and organize learning pages.',             'href' => $href_pages,    'tag' => 'Publishing'],
  ['title' => 'Contributors',  'desc' => 'Manage contributor profiles and credits.',                'href' => $href_contrib,  'tag' => 'People'],
  ['title' => 'Platforms',     'desc' => 'Maintain platform sections and feature areas.',           'href' => $href_platforms,'tag' => 'Structure'],
  ['title' => 'Tools',         'desc' => 'Open diagnostics and internal operational tools.',        'href' => $href_tools,    'tag' => 'Internal'],
  ['title' => 'Account',       'desc' => 'Review your account options and security settings.',      'href' => $href_account,  'tag' => 'Profile'],
];

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
  <h1 class="staff-pagehead__title">Staff dashboard</h1>
  <p class="staff-pagehead__desc">
    This workspace gives you a clean operational view of publishing, page management, contributors, tools, and account security.
  </p>
</section>

<section class="staff-section">
  <div class="staff-card">
    <div class="staff-card__body">
      <div class="staff-actions" style="justify-content:space-between;align-items:flex-start;">
        <div>
          <div style="font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#667085;">Quick actions</div>
          <h2 style="margin:6px 0 0;font-size:1.2rem;">Go where you work most</h2>
        </div>
        <div class="staff-actions">
          <a class="staff-chip" href="<?= h($href_subjects) ?>">Subjects</a>
          <a class="staff-chip" href="<?= h($href_pages) ?>">Pages</a>
          <a class="staff-chip" href="<?= h($href_tools) ?>">Tools</a>
          <a class="staff-chip" href="<?= h($href_audit) ?>">Audit</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="staff-section">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;">
    <?php foreach ($cards as $card): ?>
      <article class="staff-card">
        <div class="staff-card__body" style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
            <h3 style="margin:0;font-size:1.06rem;line-height:1.2;"><?= h($card['title']) ?></h3>
            <span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:rgba(17,24,39,.05);border:1px solid rgba(17,24,39,.10);font-size:.76rem;font-weight:800;color:#667085;"><?= h($card['tag']) ?></span>
          </div>
          <p style="margin:0;color:#667085;line-height:1.6;"><?= h($card['desc']) ?></p>
          <div style="margin-top:auto;padding-top:4px;">
            <a class="staff-chip" href="<?= h($card['href']) ?>">Open</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="staff-section" style="display:grid;grid-template-columns:1.2fr .8fr;gap:14px;">
  <div class="staff-card">
    <div class="staff-card__body">
      <h2 style="margin:0 0 10px;font-size:1.15rem;">Operational overview</h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
        <div class="staff-note">
          <div style="font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;">Publishing</div>
          <div style="margin-top:6px;color:#667085;">Manage page publishing, visibility, and attachments from the pages workspace.</div>
        </div>
        <div class="staff-note">
          <div style="font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;">Security</div>
          <div style="margin-top:6px;color:#667085;">Review audit activity and keep account access and passwords current.</div>
        </div>
        <div class="staff-note">
          <div style="font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;">Diagnostics</div>
          <div style="margin-top:6px;color:#667085;">Use tools for health checks, diagnostics, and controlled maintenance tasks.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="staff-card staff-card--soft">
    <div class="staff-card__body">
      <h2 style="margin:0 0 10px;font-size:1.15rem;">Recent tools</h2>
      <?php if ($recent_tools): ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <?php foreach ($recent_tools as $tool): ?>
            <a href="<?= h((string)$tool['url']) ?>" style="display:flex;justify-content:space-between;gap:10px;align-items:center;text-decoration:none;padding:10px 12px;border:1px solid rgba(17,24,39,.10);border-radius:12px;background:#fff;color:#111827;">
              <span style="font-weight:700;"><?= h((string)$tool['title']) ?></span>
              <span style="font-size:.82rem;color:#667085;"><?= h(mk_time_ago((int)$tool['ts'])) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="staff-note">No recent tools recorded yet.</div>
      <?php endif; ?>

      <div class="staff-actions" style="margin-top:12px;">
        <a class="staff-chip" href="<?= h($href_password) ?>">Change password</a>
        <a class="staff-chip" href="<?= h($href_logout) ?>">Logout</a>
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