<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

/**
 * /public/staff/tools/health.php
 * Staff Tools: Health check dashboard (web-safe).
 *
 * Purpose:
 * - Confirm bootstrap, headers, CSS, and key private tool files exist.
 * - Confirm DB connectivity (no secrets).
 * - Provide a clean PASS/FAIL report.
 *
 * Security:
 * - Requires staff login.
 * - Does NOT execute private tools.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
$u = static function(string $path): string {
  return function_exists('url_for') ? (string)url_for($path) : $path;
};

/* ---------------------------------------------------------
   Auth gate (best-effort across your stack)
--------------------------------------------------------- */
if (function_exists('mk_require_staff_login')) {
  auth_require_role('staff');
} elseif (function_exists('mk_require_login')) {
  mk_require_login();
} elseif (function_exists('require_login')) {
  require_login();
} else {
  header('Location: ' . $u('/staff/login.php'), true, 302);
  exit;
}

/* Optional permission gate */
if (function_exists('mk_require_staff_permission')) {
  try { mk_require_staff_permission('tools.view'); }
  catch (Throwable $e) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden: tools.view required.";
    exit;
  }
}

/* ---------------------------------------------------------
   Page vars
--------------------------------------------------------- */
$page_title = 'Tools Health • Staff';
$page_desc  = 'System checks for tools, bootstrap, and critical assets.';
$nav_active = 'staff';
$active_nav = 'staff';

$GLOBALS['page_title'] = $page_title;
$GLOBALS['page_desc']  = $page_desc;
$GLOBALS['nav_active'] = $nav_active;
$GLOBALS['active_nav'] = $active_nav;

/* ---------------------------------------------------------
   Load staff chrome (prefer tools_header -> staff_header)
--------------------------------------------------------- */
$header_loaded = 'none';
$shared = defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, '/') . '/shared' : '';

$tools_header = $shared !== '' ? ($shared . '/tools_header.php') : '';
$staff_header = $shared !== '' ? ($shared . '/staff_header.php') : '';

if ($tools_header !== '' && is_file($tools_header)) {
  require $tools_header;
  $header_loaded = 'tools_header.php';
} elseif (function_exists('mk_require_shared')) {
  try { mk_require_shared('tools_header.php'); $header_loaded = 'mk_require_shared(tools_header.php)'; } catch (Throwable $e) {}
}

if ($header_loaded === 'none') {
  if ($staff_header !== '' && is_file($staff_header)) {
    require $staff_header;
    $header_loaded = 'staff_header.php';
  } elseif (function_exists('mk_require_shared')) {
    try { mk_require_shared('staff_header.php'); $header_loaded = 'mk_require_shared(staff_header.php)'; } catch (Throwable $e) {}
  }
}

if ($header_loaded === 'none') {
  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>" . h($page_title) . "</title>";
  // last-resort CSS (so it is never “unstyled”)
  echo "<link rel='stylesheet' href='/lib/css/ui.css'>";
  echo "<link rel='stylesheet' href='/lib/css/staff.css'>";
  echo "</head><body><main class='container' style='padding:24px 0;'>";
}

/* ---------------------------------------------------------
   Helpers for report rendering
--------------------------------------------------------- */
$badge = static function(string $label, string $tone): string {
  $map = [
    'pass' => 'background:rgba(34,197,94,.14);border:1px solid rgba(34,197,94,.30);color:#14532d;',
    'warn' => 'background:rgba(245,158,11,.14);border:1px solid rgba(245,158,11,.32);color:#7c2d12;',
    'fail' => 'background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.30);color:#7f1d1d;',
    'info' => 'background:rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.12);color:rgba(0,0,0,.70);',
  ];
  $css = $map[$tone] ?? $map['info'];
  return '<span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-weight:900;font-size:.78rem;letter-spacing:.04em;' . $css . '">' . h($label) . '</span>';
};

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, '/') : '';
$priv    = defined('PRIVATE_PATH') ? rtrim((string)PRIVATE_PATH, '/') : '';

/* ---------------------------------------------------------
   Collect checks
--------------------------------------------------------- */
$checks = [];

/* Core constants */
$checks[] = ['label' => 'APP_ROOT defined', 'ok' => ($appRoot !== ''), 'detail' => $appRoot ?: '(missing)'];
$checks[] = ['label' => 'PRIVATE_PATH defined', 'ok' => ($priv !== ''), 'detail' => $priv ?: '(missing)'];
$checks[] = ['label' => 'DOCUMENT_ROOT', 'ok' => ($docRoot !== ''), 'detail' => $docRoot ?: '(missing)'];

/* Shared headers */
$checks[] = ['label' => 'Shared public_header.php', 'ok' => ($priv !== '' && is_file($priv . '/shared/public_header.php')), 'detail' => ($priv !== '' ? ($priv . '/shared/public_header.php') : '(PRIVATE_PATH missing)')];
$checks[] = ['label' => 'Shared staff_header.php', 'ok' => ($priv !== '' && is_file($priv . '/shared/staff_header.php')), 'detail' => ($priv !== '' ? ($priv . '/shared/staff_header.php') : '(PRIVATE_PATH missing)')];
$checks[] = ['label' => 'Shared tools_header.php', 'ok' => ($priv !== '' && is_file($priv . '/shared/tools_header.php')), 'detail' => ($priv !== '' ? ($priv . '/shared/tools_header.php') : '(PRIVATE_PATH missing)')];

/* CSS critical (filesystem) */
$checks[] = ['label' => 'CSS file exists: /lib/css/ui.css', 'ok' => ($docRoot !== '' && is_file($docRoot . '/lib/css/ui.css')), 'detail' => $docRoot . '/lib/css/ui.css'];
$checks[] = ['label' => 'CSS file exists: /lib/css/staff.css', 'ok' => ($docRoot !== '' && is_file($docRoot . '/lib/css/staff.css')), 'detail' => $docRoot . '/lib/css/staff.css'];

/* Staff tools web endpoints */
$checks[] = ['label' => 'Tools endpoint: /staff/tools/index.php', 'ok' => is_file(__DIR__ . '/index.php'), 'detail' => __DIR__ . '/index.php'];
$checks[] = ['label' => 'Tools endpoint: /staff/tools/run.php', 'ok' => is_file(__DIR__ . '/run.php'), 'detail' => __DIR__ . '/run.php'];
$checks[] = ['label' => 'Tools endpoint: /staff/tools/health.php', 'ok' => true, 'detail' => 'this page'];

/* Private tools existence */
$private_tools = ['project_audit.php','diag.php','subjects_diag.php','audit_staff_account.php'];
foreach ($private_tools as $f) {
  $path = ($appRoot !== '' ? ($appRoot . '/private/tools/' . $f) : '');
  $checks[] = ['label' => 'Private tool: ' . $f, 'ok' => ($path !== '' && is_file($path)), 'detail' => ($path !== '' ? $path : '(APP_ROOT missing)')];
}

/* DB check (safe) */
$db_ok = false;
$db_detail = '';
try {
  if (!function_exists('db')) throw new RuntimeException('db() helper not found.');
  $pdo = db();
  if (!$pdo instanceof PDO) throw new RuntimeException('db() did not return PDO.');
  $st = $pdo->query('SELECT 1');
  $db_ok = (bool)$st;
  $db_detail = $db_ok ? 'PDO OK' : 'PDO query failed';
} catch (Throwable $e) {
  $db_ok = false;
  $db_detail = $e->getMessage();
}
$checks[] = ['label' => 'Database connectivity', 'ok' => $db_ok, 'detail' => $db_detail];

/* Summary */
$pass = 0; $fail = 0;
foreach ($checks as $c) { if (!empty($c['ok'])) $pass++; else $fail++; }

/* Visible debug hints (human check) */
$css_hint_ui    = '<link rel="stylesheet" href="/lib/css/ui.css';
$css_hint_staff = '<link rel="stylesheet" href="/lib/css/staff.css';
$buf = ob_get_contents();
$seen_ui = ($buf !== false && strpos($buf, $css_hint_ui) !== false);
$seen_staff = ($buf !== false && strpos($buf, $css_hint_staff) !== false);

?>
<section class="mk-hero" style="margin-top:14px;">
  <div class="mk-hero__bar" aria-hidden="true"></div>
  <div class="mk-hero__inner">
    <h1 class="mk-hero__title">Tools Health</h1>
    <p class="mk-hero__subtitle">Quick PASS/FAIL checks for your stack and toolchain.</p>

    <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
      <?= $badge("PASS: {$pass}", 'pass') ?>
      <?= $badge("FAIL: {$fail}", $fail ? 'fail' : 'pass') ?>
      <?= $badge('UTC: ' . gmdate('Y-m-d H:i:s'), 'info') ?>
    </div>

    <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
      <?= $badge('Header: ' . $header_loaded, 'info') ?>
      <?= $badge('ui.css tag: ' . ($seen_ui ? 'YES' : 'NO'), $seen_ui ? 'pass' : 'fail') ?>
      <?= $badge('staff.css tag: ' . ($seen_staff ? 'YES' : 'NO'), $seen_staff ? 'pass' : 'warn') ?>
    </div>
  </div>
</section>

<section style="margin-top:16px;">
  <div class="mk-card" style="padding:14px;">
    <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center;">
      <div class="mk-muted" style="font-size:.92rem;">
        If borders look wrong on Tools pages, check: staff.css tag should be YES.
      </div>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a class="btn" href="<?= h($u('/staff/tools/')) ?>">← Back to Tools</a>
        <a class="btn" href="<?= h($u('/staff/tools/health.php')) ?>">Refresh</a>
      </div>
    </div>

    <div style="margin-top:12px; overflow-x:auto;">
      <table class="table" style="width:100%;">
        <thead>
          <tr>
            <th style="width:160px;">Status</th>
            <th>Check</th>
            <th>Detail</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($checks as $c): ?>
            <?php
              $ok = !empty($c['ok']);
              $status = $ok ? $badge('PASS', 'pass') : $badge('FAIL', 'fail');
              $label = (string)($c['label'] ?? '');
              $detail = (string)($c['detail'] ?? '');
            ?>
            <tr>
              <td><?= $status ?></td>
              <td style="font-weight:800;"><?= h($label) ?></td>
              <td><code><?= h($detail) ?></code></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</section>

<?php
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('staff_footer.php'); return; } catch (Throwable $e) {}
  try { mk_require_shared('public_footer.php'); return; } catch (Throwable $e2) {}
}
echo "</main></body></html>";
