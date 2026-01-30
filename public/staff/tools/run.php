<?php
declare(strict_types=1);

/**
 * /public/staff/tools/run.php
 *
 * Safe allowlisted runner for private tools.
 *
 * Security & Safety:
 * - Requires staff login
 * - Loads registry: APP_ROOT/private/functions/staff_tools_registry.php
 * - Tool must exist in registry allowlist
 * - Enforces min_role (admin/owner)
 * - Enforces realpath boundary (APP_ROOT/private/tools)
 * - PHP-only tools (.php) only
 * - MUTATING tools require POST + CSRF (dry-run and apply)
 *
 * Output:
 * - render=pre  : escaped output
 * - render=html : sandboxed iframe using srcdoc
 * - render=auto : html if output looks like a document, else pre
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('url_for')) {
  function url_for(string $path): string { return '/' . ltrim($path, '/'); }
}

function mk_is_html_document(string $s): bool {
  return (bool)preg_match('~<!doctype\s+html|<html\b|<head\b|<body\b~i', $s);
}

function mk_force_html_headers(): void {
  if (headers_sent()) return;
  @header_remove('Content-Disposition');
  @header_remove('Content-Transfer-Encoding');

  header('Content-Type: text/html; charset=UTF-8');
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: same-origin');
  header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
}

function mk_method(): string {
  return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

/* ---------------------------------------------------------
   CSRF (self-contained; uses session)
--------------------------------------------------------- */
function mk_csrf_token(): string {
  if (empty($_SESSION['mk_csrf']) || !is_string($_SESSION['mk_csrf']) || strlen((string)$_SESSION['mk_csrf']) < 32) {
    try {
      $_SESSION['mk_csrf'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
      $_SESSION['mk_csrf'] = bin2hex((string)microtime(true) . (string)mt_rand());
    }
  }
  return (string)$_SESSION['mk_csrf'];
}

function mk_csrf_validate(): bool {
  $token = '';
  if (isset($_POST['csrf']) && is_string($_POST['csrf'])) $token = $_POST['csrf'];
  if ($token === '' && isset($_POST['_token']) && is_string($_POST['_token'])) $token = $_POST['_token'];
  if ($token === '') return false;
  return hash_equals((string)mk_csrf_token(), (string)$token);
}

/* ---------------------------------------------------------
   Auth gate (before output)
--------------------------------------------------------- */
if (function_exists('mk_require_staff_login')) {
  mk_require_staff_login();
} else {
  header('Location: ' . url_for('/staff/login.php'), true, 302);
  exit;
}

mk_force_html_headers();

/* ---------------------------------------------------------
   RBAC (admin/owner)
--------------------------------------------------------- */
function mk_staff_id(): int {
  if (isset($_SESSION['staff_user_id']) && is_numeric($_SESSION['staff_user_id'])) return (int)$_SESSION['staff_user_id'];
  if (isset($_SESSION['staff_user']['id']) && is_numeric($_SESSION['staff_user']['id'])) return (int)$_SESSION['staff_user']['id'];
  if (isset($_SESSION['staff']['id']) && is_numeric($_SESSION['staff']['id'])) return (int)$_SESSION['staff']['id'];
  if (isset($_SESSION['staff_id']) && is_numeric($_SESSION['staff_id'])) return (int)$_SESSION['staff_id'];
  return 0;
}

function mk_staff_has_role(string $slug): bool {
  $slug = strtolower(trim($slug));
  if ($slug === '') return false;

  $uid = mk_staff_id();
  if ($uid <= 0) return false;
  if (!function_exists('db')) return false;

  try {
    $pdo = db();
    if (!$pdo instanceof PDO) return false;

    $st = $pdo->prepare(
      "SELECT 1
         FROM staff_user_roles sur
         JOIN roles r ON r.id = sur.role_id
        WHERE sur.staff_user_id = :uid
          AND LOWER(r.slug) = :slug
        LIMIT 1"
    );
    $st->execute([':uid' => $uid, ':slug' => $slug]);
    return (bool)$st->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

function mk_require_role(string $minRole): void {
  $minRole = strtolower(trim($minRole));
  if ($minRole === 'owner') {
    if (mk_staff_has_role('owner')) return;
    http_response_code(403);
    echo "<pre>Forbidden: owner required.</pre>";
    exit;
  }
  if (mk_staff_has_role('admin') || mk_staff_has_role('owner')) return;
  http_response_code(403);
  echo "<pre>Forbidden: admin required.</pre>";
  exit;
}

/* ---------------------------------------------------------
   Load registry
--------------------------------------------------------- */
$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
$toolsRoot = ($appRoot !== '') ? ($appRoot . '/private/tools') : '';
$realToolsRoot = ($toolsRoot !== '' && is_dir($toolsRoot)) ? realpath($toolsRoot) : false;

$registryFile = ($appRoot !== '') ? ($appRoot . '/private/functions/staff_tools_registry.php') : '';

if (!$realToolsRoot) {
  http_response_code(500);
  echo "<pre>Tools root not found.</pre>";
  exit;
}
if (!is_file($registryFile)) {
  http_response_code(500);
  echo "<pre>Tools registry missing:\n" . h($registryFile) . "\n</pre>";
  exit;
}

require_once $registryFile;

$regList = function_exists('mk_staff_tools_registry') ? mk_staff_tools_registry() : [];
if (!is_array($regList) || !$regList) {
  http_response_code(500);
  echo "<pre>Tools registry empty or invalid.</pre>";
  exit;
}

/**
 * Normalize registry to keyed map:
 * - supports either [ [entry], [entry] ] OR [ 'key' => entry, ... ]
 */
$tools = [];
if (array_keys($regList) === range(0, count($regList) - 1)) {
  foreach ($regList as $entry) {
    if (!is_array($entry)) continue;
    $k = isset($entry['key']) ? trim((string)$entry['key']) : '';
    if ($k === '') continue;
    $tools[$k] = $entry;
  }
} else {
  foreach ($regList as $k => $entry) {
    if (!is_array($entry)) continue;
    $key = trim((string)$k);
    if ($key === '') $key = isset($entry['key']) ? trim((string)$entry['key']) : '';
    if ($key === '') continue;
    $tools[$key] = $entry;
  }
}

if (!$tools) {
  http_response_code(500);
  echo "<pre>Tools registry normalization failed (no usable entries).</pre>";
  exit;
}

/* ---------------------------------------------------------
   Select tool
--------------------------------------------------------- */
$toolKey = '';
if (isset($_GET['tool'])) $toolKey = trim((string)$_GET['tool']);
if ($toolKey === '' && isset($_POST['tool'])) $toolKey = trim((string)$_POST['tool']);

if ($toolKey === '' || !isset($tools[$toolKey]) || !is_array($tools[$toolKey])) {
  http_response_code(404);
  echo "<pre>Tool not found.</pre>";
  exit;
}

$meta = $tools[$toolKey];

$title    = isset($meta['title']) ? (string)$meta['title'] : $toolKey;
$minRole  = isset($meta['min_role']) ? strtolower(trim((string)$meta['min_role'])) : 'admin';
$relPath  = isset($meta['rel_path']) ? trim((string)$meta['rel_path']) : '';
$isMutate = !empty($meta['mutating']); // defaults false

/* remember last tool (safe chars only) */
if (preg_match('~^[a-z0-9][a-z0-9\/\-_]{0,128}$~i', $toolKey)) {
  $_SESSION['mk_last_tool'] = $toolKey;
}

/* enforce min_role */
mk_require_role($minRole);

/* ---------------------------------------------------------
   Boundary checks (realpath + php-only)
--------------------------------------------------------- */
if ($relPath === '') {
  http_response_code(500);
  echo "<pre>Tool rel_path missing in registry.</pre>";
  exit;
}

$relPath = ltrim($relPath, "/\\");
$ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
if ($ext !== 'php') {
  http_response_code(403);
  echo "<pre>Forbidden: only PHP tools allowed.</pre>";
  exit;
}

$realToolsRootNorm = rtrim((string)$realToolsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$target = $realToolsRootNorm . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);
$realPath = realpath($target);

if (!$realPath || !is_file($realPath)) {
  http_response_code(404);
  echo "<pre>Tool file missing:\n" . h($relPath) . "\n</pre>";
  exit;
}

$realPathNorm = rtrim((string)$realPath, DIRECTORY_SEPARATOR);
if (strpos($realPathNorm, rtrim($realToolsRootNorm, DIRECTORY_SEPARATOR)) !== 0) {
  http_response_code(403);
  echo "<pre>Forbidden: tool path outside allowed directory.</pre>";
  exit;
}

/* ---------------------------------------------------------
   Render selection
--------------------------------------------------------- */
$render = 'pre';
if (isset($_GET['render'])) $render = strtolower(trim((string)$_GET['render']));
if (isset($_POST['render'])) $render = strtolower(trim((string)$_POST['render']));
if (!in_array($render, ['pre','html','auto'], true)) $render = 'pre';

/* ---------------------------------------------------------
   Mutating safety gate + APPLY propagation
--------------------------------------------------------- */
$method = mk_method();

/**
 * APPLY flag propagation:
 * If URL has ?apply=1 but POST doesn't include apply, bridge it.
 * (Some UIs POST without preserving querystring; our form preserves it anyway.)
 */
$apply_qs = (string)($_GET['apply'] ?? '');
if ($method === 'POST' && $apply_qs === '1' && empty($_POST['apply'])) {
  $_POST['apply'] = '1';
}

/**
 * "Run requested" if:
 * - POST run=1 (dry-run), OR
 * - POST apply=1 (apply)
 */
$applyRequested = ($method === 'POST' && (string)($_POST['apply'] ?? '') === '1');
$runRequested   = ($method === 'POST' && (
  ((string)($_POST['run'] ?? '') === '1') || $applyRequested
));

if ($isMutate) {
  // Mutating tools: require POST + CSRF whenever executing (dry-run or apply).
  if ($runRequested) {
    if (!mk_csrf_validate()) {
      http_response_code(403);
      echo "<pre>Forbidden: CSRF check failed.</pre>";
      exit;
    }
  }
} else {
  // Read-only tools:
  // - prefer POST to run
  // - allow GET run only if explicitly requested (compat)
  if ($method === 'GET' && (string)($_GET['run'] ?? '') === '1') {
    $runRequested = true;
  }
}

/* ---------------------------------------------------------
   Staff chrome
--------------------------------------------------------- */
$page_title = 'Run Tool • ' . $title;
$page_desc  = 'Execution output is captured below.';
$nav_active = 'tools';
$active_nav = 'tools';

if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title' => $page_title,
      'page_desc'  => $page_desc,
      'nav_active' => $nav_active,
      'active_nav' => $active_nav,
    ]);
  } catch (Throwable $e) {}
}

$toolsIndexUrl = url_for('/staff/tools/');
$dashboardUrl  = url_for('/staff/');
$selfBase      = url_for('/staff/tools/run.php?tool=' . rawurlencode($toolKey));

if (function_exists('mk_require_shared')) {
  mk_require_shared('staff_header.php');
} else {
  echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>";
  echo "<title>" . h($page_title) . "</title></head><body class='staff tools'><main class='site-main' id='main'>";
}

echo '<section class="mk-hero" style="margin-top:14px;">';
echo '  <div class="mk-hero__bar" aria-hidden="true"></div>';
echo '  <div class="mk-hero__inner">';
echo '    <h1 class="mk-hero__title">' . h($title) . '</h1>';
echo '    <p class="mk-hero__subtitle">' . h($page_desc) . '</p>';
echo '    <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">';
echo '      <a class="btn" href="' . h($toolsIndexUrl) . '">Tools Index</a>';
echo '      <a class="btn" href="' . h($dashboardUrl) . '">Dashboard</a>';
echo '    </div>';
echo '  </div>';
echo '</section>';

echo '<section class="mk-card" style="padding:14px; margin-top:14px;">';
echo '  <div class="mk-muted" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;">';
echo '    <div>Tool key: <strong>' . h($toolKey) . '</strong></div>';
echo '    <div style="display:flex; gap:8px; flex-wrap:wrap;">';
echo '      <a class="btn btn--small" href="' . h($selfBase . '&render=pre') . '">Render PRE</a>';
echo '      <a class="btn btn--small" href="' . h($selfBase . '&render=html') . '">Render HTML</a>';
echo '      <a class="btn btn--small" href="' . h($selfBase . '&render=auto') . '">Render Auto</a>';
echo '    </div>';
echo '  </div>';

echo '  <div class="mk-muted" style="margin-top:10px;">';
echo '    <div><strong>Policy:</strong> PHP-only, allowlisted, bounded to /private/tools, role-gated.</div>';
if ($isMutate) {
  echo '    <div><strong>Mutating tool:</strong> dry-run with Run; Apply will modify data (POST + CSRF).</div>';
} else {
  echo '    <div><strong>Read-only tool:</strong> safe to run.</div>';
}
echo '  </div>';

/* ---------------------------------------------------------
   Run form (POST)
--------------------------------------------------------- */
$csrf = mk_csrf_token();

/**
 * Preserve querystring on POST (tool=..., render=..., apply=1, etc.)
 * This helps tools that still read $_GET['apply'].
 */
$self = (string)($_SERVER['REQUEST_URI'] ?? '/staff/tools/run.php');
$self = str_replace(["\r","\n"], '', $self);

echo '<form method="post" action="' . h($self) . '" style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">';
echo '  <input type="hidden" name="tool" value="' . h($toolKey) . '">';
echo '  <input type="hidden" name="render" value="' . h($render) . '">';
echo '  <input type="hidden" name="csrf" value="' . h($csrf) . '">';

/**
 * If the current URL is already ?apply=1, keep it in POST too.
 * (belt+suspenders; also means "Run Tool" while on apply URL still applies)
 */
if (isset($_GET['apply']) && (string)$_GET['apply'] === '1') {
  echo '  <input type="hidden" name="apply" value="1">';
}

echo '  <button class="btn" type="submit" name="run" value="1">Run Tool</button>';

if ($isMutate) {
  echo '  <button class="btn btn--danger" type="submit" name="apply" value="1" onclick="return confirm(\'Apply changes now? This tool can modify data.\');">Apply</button>';
}

echo '  <a class="btn btn--small" href="' . h($selfBase) . '">Reset</a>';
echo '</form>';

/* ---------------------------------------------------------
   Execute tool (capture output)
--------------------------------------------------------- */
$out = '';
$err = null;
$warnings = [];

if ($runRequested) {

  $prevDisplay = ini_get('display_errors');
  @ini_set('display_errors', '0');

  $prevHandler = set_error_handler(static function ($severity, $message, $file, $line) use (&$warnings) {
    if (!(error_reporting() & $severity)) return false;
    $warnings[] = trim((string)$message) . " in " . (string)$file . ":" . (string)$line;
    return true;
  });

  $shutdownErr = null;
  register_shutdown_function(static function () use (&$shutdownErr) {
    $e = error_get_last();
    if (!$e) return;
    $type = $e['type'] ?? 0;
    if (in_array($type, [1, 4, 16, 64, 256], true)) {
      $shutdownErr = ($e['message'] ?? 'Fatal error') . " in " . ($e['file'] ?? '?') . ":" . ($e['line'] ?? 0);
    }
  });

  try {
    if (!isset($_SERVER['argv']) || !is_array($_SERVER['argv'])) $_SERVER['argv'] = [];
    if (!isset($_SERVER['argc'])) $_SERVER['argc'] = 0;

    ob_start();
    require $realPath;
    $out = (string)ob_get_clean();
  } catch (Throwable $e) {
    if (ob_get_level() > 0) { @ob_end_clean(); }
    $err = $e->getMessage();
  } finally {
    restore_error_handler();
    @ini_set('display_errors', (string)$prevDisplay);
  }

  if ($shutdownErr !== null && $shutdownErr !== '') {
    $err = $shutdownErr;
  }
}

mk_force_html_headers();

/* ---------------------------------------------------------
   Output rendering
--------------------------------------------------------- */
if (!$runRequested) {
  echo '<div class="notice info" style="margin-top:12px;">Click <strong>Run Tool</strong> to execute. Output will appear here.</div>';
} else {

  if ($err !== null) {
    echo '<div class="notice error" style="margin-top:12px;"><strong>Tool error:</strong> ' . h($err) . '</div>';
  } else {
    $out = ($out !== '') ? $out : "(No output)\n";

    $looks_doc = mk_is_html_document($out);
    $mode = $render;

    if ($mode === 'auto') {
      $mode = $looks_doc ? 'html' : 'pre';
    }

    if (!empty($warnings)) {
      echo '<div class="notice warn" style="margin-top:12px;">';
      echo '<strong>Warnings captured:</strong>';
      echo '<pre style="margin-top:8px; white-space:pre-wrap;">' . h(implode("\n", array_slice($warnings, 0, 120))) . '</pre>';
      echo '</div>';
    }

    if ($mode === 'html') {
      echo '<div style="margin-top:12px;">';
      echo '<div class="mk-muted" style="margin-bottom:10px;">Rendering mode: <strong>HTML (sandboxed)</strong></div>';
      echo '<iframe sandbox="allow-same-origin allow-forms allow-pointer-lock allow-popups-to-escape-sandbox allow-popups allow-modals allow-downloads" '
        . 'style="width:100%; min-height:70vh; border:1px solid rgba(0,0,0,.12); border-radius:12px; background:#fff;" '
        . 'srcdoc="' . h($out) . '"></iframe>';
      echo '</div>';
    } else {
      echo '<div class="mk-muted" style="margin-top:12px;">Rendering mode: <strong>PRE (escaped)</strong></div>';
      echo '<pre style="margin-top:10px; white-space:pre-wrap; word-break:break-word; padding:12px; border:1px solid rgba(0,0,0,.10); border-radius:12px; background:#fff;">'
        . h($out)
        . '</pre>';

      if ($looks_doc) {
        $selfHtml = $selfBase . '&render=html';
        echo '<div class="mk-muted" style="margin-top:10px;">';
        echo 'This output looks like a full HTML document. ';
        echo '<a class="btn" style="margin-left:8px;" href="' . h($selfHtml) . '">Render as HTML</a>';
        echo '</div>';
      }
    }
  }
}

echo '</section>';

/* Footer */
if (function_exists('mk_require_shared')) {
  mk_require_shared('staff_footer.php');
  exit;
}
echo "</main></body></html>";
