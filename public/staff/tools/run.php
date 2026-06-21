<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

/**
 * /public/staff/tools/run.php
 *
 * Safe allowlisted runner for private tools.
 *
 * Contract:
 * - Requires staff login
 * - Loads registry: APP_ROOT/private/functions/staff_tools_registry.php
 * - Tool must exist in registry allowlist
 * - Enforces min_role (admin/owner) via mk_require_role() if present
 * - Enforces realpath boundary (APP_ROOT/private/tools)
 * - PHP-only tools (.php)
 * - MUTATING tools require POST + CSRF
 *
 * Output:
 * - render=pre  : escaped pre block
 * - render=html : iframe sandbox srcdoc (safe-ish)
 * - render=auto : html if output looks like a document, else pre
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

/* ---------------------------------------------------------
   Session (canonical)
--------------------------------------------------------- */
if (function_exists('mk__session_start')) {
  mk__session_start();
} else {
}

/* ---------------------------------------------------------
   Ensure auth helpers loaded (belt+suspenders)
--------------------------------------------------------- */
if (!function_exists('mk_require_staff_login') || !function_exists('auth_login')) {
  $auth = null;
  if (defined('PRIVATE_PATH') && is_string(PRIVATE_PATH) && PRIVATE_PATH !== '') {
    $auth = rtrim(PRIVATE_PATH, "/\\") . '/functions/auth.php';
  } elseif (defined('APP_ROOT') && is_string(APP_ROOT) && APP_ROOT !== '') {
    $auth = rtrim(APP_ROOT, "/\\") . '/private/functions/auth.php';
  }
  if ($auth && is_file($auth)) require_once $auth;
}

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */
if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('url_for')) {
  function url_for(string $path): string { return '/' . ltrim($path, '/'); }
}

function mk_method(): string {
  return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function mk_is_html_document(string $s): bool {
  return (bool)preg_match('~<!doctype\s+html|<html\b|<head\b|<body\b~i', $s);
}

/* CSRF (prefer canonical staff_csrf_* if present) */
function mk_tools_csrf_token(): string {
  if (function_exists('staff_csrf_token')) return (string)staff_csrf_token();
  if (function_exists('csrf_token')) return (string)csrf_token();

  if (empty($_SESSION['mk_csrf']) || !is_string($_SESSION['mk_csrf']) || strlen((string)$_SESSION['mk_csrf']) < 32) {
    $_SESSION['mk_csrf'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['mk_csrf'];
}
function mk_tools_csrf_valid(?string $posted): bool {
  $posted = is_string($posted) ? trim($posted) : '';
  if ($posted === '') return false;

  if (function_exists('staff_csrf_verify')) return (bool)staff_csrf_verify($posted);
  if (function_exists('csrf_token_is_valid')) return (bool)csrf_token_is_valid($posted);

  $sess = (string)mk_tools_csrf_token();
  return ($sess !== '' && hash_equals($sess, $posted));
}

/* ---------------------------------------------------------
   Auth gate (FIRST, before any output)
--------------------------------------------------------- */
$wantReturn = '/staff/tools/';
if (function_exists('mk_require_staff_login')) {
  try { mk_require_staff_login($wantReturn); } catch (Throwable $e) { auth_require_role('staff'); }
} else {
  header('Location: /staff/login.php?return=' . rawurlencode($wantReturn), true, 302);
  exit;
}

/* ---------------------------------------------------------
   Buffer + fatal capture (prevents LiteSpeed generic "internal error")
--------------------------------------------------------- */
$render = strtolower((string)($_GET['render'] ?? 'auto'));
if (!in_array($render, ['auto','pre','html'], true)) $render = 'auto';

$toolKey = (string)($_GET['tool'] ?? '');
$toolKey = str_replace(["\r","\n"], '', trim($toolKey));

/**
 * We buffer everything (including staff chrome) and on fatal we dump a controlled page.
 * This avoids the generic "An internal error occurred. Reference: ...."
 */
$__mk_started_buffer = false;
$__mk_sent_fatal = false;

register_shutdown_function(static function () use (&$__mk_started_buffer, &$__mk_sent_fatal, $toolKey, $render): void {
  if ($__mk_sent_fatal) return;

  $err = error_get_last();
  if (!$err) return;

  $type = (int)($err['type'] ?? 0);
  $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
  if (!in_array($type, $fatalTypes, true)) return;

  $msg  = (string)($err['message'] ?? 'Fatal error');
  $file = (string)($err['file'] ?? '');
  $line = (int)($err['line'] ?? 0);

  // wipe buffers (if any)
  while (ob_get_level() > 0) { @ob_end_clean(); }

  http_response_code(500);
  header('Content-Type: text/html; charset=UTF-8');
  header('X-Content-Type-Options: nosniff');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');

  echo "<!doctype html><html lang='en'><head><meta charset='utf-8'>";
  echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>Tool Fatal</title>";
  echo "<style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#0b1220;color:#e5e7eb}
    .wrap{max-width:980px;margin:40px auto;padding:0 16px}
    .card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:18px}
    h1{margin:0 0 8px;font-size:20px}
    .muted{opacity:.85}
    pre{white-space:pre-wrap;background:rgba(0,0,0,.35);padding:14px;border-radius:12px;border:1px solid rgba(255,255,255,.10);overflow:auto}
    code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
    a{color:#93c5fd}
  </style></head><body><div class='wrap'><div class='card'>";
  echo "<h1>Fatal error while running tool</h1>";
  echo "<div class='muted'>tool=<code>" . htmlspecialchars($toolKey, ENT_QUOTES, 'UTF-8') . "</code> • render=<code>" . htmlspecialchars($render, ENT_QUOTES, 'UTF-8') . "</code></div>";
  echo "<pre><strong>Message:</strong> " . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "\n"
     . "<strong>File:</strong> " . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "\n"
     . "<strong>Line:</strong> " . (int)$line . "</pre>";
  echo "<div class='muted'>If this repeats, run the tool via CLI include to see notices/warnings before the fatal.</div>";
  echo "</div></div></body></html>";

  $__mk_sent_fatal = true;
});

ob_start();
$__mk_started_buffer = true;

/* ---------------------------------------------------------
   Locate tools root + registry
--------------------------------------------------------- */
$appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
$toolsRoot = ($appRoot !== '') ? ($appRoot . '/private/tools') : '';
$realToolsRoot = ($toolsRoot !== '' && is_dir($toolsRoot)) ? realpath($toolsRoot) : false;

$registryFile = ($appRoot !== '') ? ($appRoot . '/private/functions/staff_tools_registry.php') : '';

if (!$realToolsRoot) {
  http_response_code(500);
  echo "<pre>Tools root not found.\n" . h($toolsRoot) . "</pre>";
  echo ob_get_clean();
  exit;
}
if (!is_file($registryFile)) {
  http_response_code(500);
  echo "<pre>Tools registry missing:\n" . h($registryFile) . "\n</pre>";
  echo ob_get_clean();
  exit;
}

require_once $registryFile;
if (!function_exists('mk_staff_tools_registry')) {
  http_response_code(500);
  echo "<pre>mk_staff_tools_registry() missing in registry file.</pre>";
  echo ob_get_clean();
  exit;
}

$toolsRaw = mk_staff_tools_registry();

/* Normalize registry to map keyed by tool key */
$tools = [];
if (is_array($toolsRaw)) {
  $isList = (array_keys($toolsRaw) === range(0, count($toolsRaw) - 1));
  if ($isList) {
    foreach ($toolsRaw as $e) {
      if (!is_array($e)) continue;
      $k = isset($e['key']) ? trim((string)$e['key']) : '';
      if ($k === '') continue;
      $tools[$k] = $e;
    }
  } else {
    foreach ($toolsRaw as $k => $e) {
      if (!is_array($e)) continue;
      $kk = trim((string)$k);
      if ($kk === '') $kk = isset($e['key']) ? trim((string)$e['key']) : '';
      if ($kk === '') continue;
      $tools[$kk] = $e;
    }
  }
}

if ($toolKey === '' || !isset($tools[$toolKey]) || !is_array($tools[$toolKey])) {
  http_response_code(404);
  echo "<pre>Tool not registered: " . h($toolKey) . "</pre>";
  echo ob_get_clean();
  exit;
}

$meta = $tools[$toolKey];
$minRole  = isset($meta['min_role']) ? strtolower(trim((string)$meta['min_role'])) : 'admin';
$mutating = !empty($meta['mutating']);

/* Role enforcement if available */
if (function_exists('mk_require_role')) {
  // Many deployments treat 'owner' as above admin.
  $need = ($minRole === 'owner') ? ['owner'] : ['admin','owner'];
  mk_require_role($need);
}

/* Resolve tool path (your registry uses abs_path / rel_path) */
$abs = isset($meta['abs_path']) ? trim((string)$meta['abs_path']) : '';
$rel = isset($meta['rel_path']) ? trim((string)$meta['rel_path']) : '';

$toolPath = '';
if ($abs !== '' && is_file($abs)) {
  $toolPath = $abs;
} elseif ($rel !== '') {
  $cand = rtrim($realToolsRoot, "/\\") . '/' . ltrim(str_replace('\\','/',$rel), '/');
  if (is_file($cand)) $toolPath = $cand;
}

/* Final validation */
if ($toolPath === '') {
  http_response_code(500);
  echo "<pre>Registry entry has no valid tool file for: " . h($toolKey) . "\n"
     . "Expected abs_path or rel_path to exist.\n"
     . "abs_path=" . h($abs) . "\n"
     . "rel_path=" . h($rel) . "\n"
     . "</pre>";
  echo ob_get_clean();
  exit;
}

$realTool = realpath($toolPath);
if (!$realTool) {
  http_response_code(500);
  echo "<pre>Tool file not resolvable:\n" . h($toolPath) . "</pre>";
  echo ob_get_clean();
  exit;
}

$rootPrefix = rtrim((string)$realToolsRoot, "/\\") . DIRECTORY_SEPARATOR;
if (strpos($realTool, $rootPrefix) !== 0) {
  http_response_code(403);
  echo "<pre>Tool blocked (outside tools root).\nTool: " . h($realTool) . "\nRoot: " . h((string)$realToolsRoot) . "</pre>";
  echo ob_get_clean();
  exit;
}

if (strtolower(pathinfo($realTool, PATHINFO_EXTENSION)) !== 'php') {
  http_response_code(403);
  echo "<pre>Tool blocked (only .php allowed):\n" . h($realTool) . "</pre>";
  echo ob_get_clean();
  exit;
}

/* Mutating enforcement */
if ($mutating) {
  if (mk_method() !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo "<pre>This tool is mutating and requires POST.</pre>";
    echo ob_get_clean();
    exit;
  }
  $tok = $_POST['csrf'] ?? ($_POST['csrf_token'] ?? '');
  if (!mk_tools_csrf_valid(is_string($tok) ? $tok : '')) {
    http_response_code(400);
    echo "<pre>CSRF failed.</pre>";
    echo ob_get_clean();
    exit;
  }
}

/* ---------------------------------------------------------
   Execute tool (capture output)
--------------------------------------------------------- */
$toolOut = '';
$toolHadOutput = false;

$__oldGet = $_GET;
$_GET['tool'] = $toolKey;
$_GET['render'] = $render;

try {
  ob_start();
  include $realTool;
  $toolOut = (string)ob_get_clean();
  $toolHadOutput = ($toolOut !== '');
} catch (Throwable $e) {
  while (ob_get_level() > 0) { @ob_end_clean(); }
  http_response_code(500);
  echo "<pre>Tool exception:\n" . h($e->getMessage()) . "\n\n" . h($realTool) . "</pre>";
  echo ob_get_clean();
  $_GET = $__oldGet;
  exit;
}

$_GET = $__oldGet;

/* Decide render if auto */
$finalRender = $render;
if ($render === 'auto') {
  $finalRender = mk_is_html_document($toolOut) ? 'html' : 'pre';
}

/* ---------------------------------------------------------
   Staff chrome + output
--------------------------------------------------------- */
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('tools_header.php'); } catch (Throwable $e) {}
} else {
  // minimal fallback header
  header('Content-Type: text/html; charset=UTF-8');
  echo "<!doctype html><meta charset='utf-8'><title>Tool</title><body><main style='padding:18px;font-family:system-ui'>";
}

echo "<section class='mk-card' style='padding:14px; margin-top:14px;'>";
echo "<div style='display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;'>";
echo "<div>";
echo "<div style='font-weight:900;'>Tool</div>";
echo "<div class='mk-muted' style='margin-top:2px;'><code>" . h($toolKey) . "</code></div>";
echo "</div>";
echo "<div class='mk-muted' style='font-size:.92rem;'>File: <code>" . h($realTool) . "</code></div>";
echo "</div>";
echo "</section>";

if ($finalRender === 'pre') {
  $safe = htmlspecialchars($toolOut, ENT_QUOTES, 'UTF-8');
  echo "<section class='mk-card' style='padding:14px; margin-top:14px;'>";
  echo "<pre style='margin:0;white-space:pre-wrap;overflow:auto;'>" . $safe . "</pre>";
  echo "</section>";
} else {
  // html: sandboxed iframe using srcdoc
  $srcdoc = $toolOut;
  if (!mk_is_html_document($srcdoc)) {
    $srcdoc = "<!doctype html><meta charset='utf-8'><title>Output</title>"
      . "<pre style='white-space:pre-wrap;font-family:ui-monospace,Menlo,Consolas,monospace;'>"
      . htmlspecialchars($toolOut, ENT_QUOTES, 'UTF-8')
      . "</pre>";
  }

  // prevent </script> breakouts in srcdoc context (basic hardening)
  $srcdoc = str_replace('</script', '<\/script', $srcdoc);

  echo "<section class='mk-card' style='padding:14px; margin-top:14px;'>";
  echo "<iframe sandbox='allow-same-origin' style='width:100%;min-height:520px;border:1px solid rgba(0,0,0,.12);border-radius:12px;background:#fff' srcdoc='"
    . htmlspecialchars($srcdoc, ENT_QUOTES, 'UTF-8')
    . "'></iframe>";
  echo "</section>";
}

/* Footer */
if (function_exists('mk_require_shared')) {
  mk_require_shared('staff_footer.php');
  echo ob_get_clean();
  exit;
}

echo "</main></body>";
echo ob_get_clean();
exit;
