<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/shared/tools_header.php
 * Staff Tools header wrapper.
 *
 * Guarantees:
 * - staff login gate (best-effort via your auth helpers)
 * - tools pages always load staff chrome + staff.css
 * - consistent view vars (mk_view_set + globals)
 * - AUTOMATIC tools subnav when active_nav = tools
 *
 * This file should NOT output <base> tags. It delegates to staff_header.php.
 */

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

/* Enforce session */
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* If view store already has values, allow it to seed defaults */
if (function_exists('mk_view_get')) {
  try {
    if (!isset($page_title) || !is_string($page_title) || trim($page_title) === '') {
      $tmp = mk_view_get('page_title'); if (is_string($tmp) && trim($tmp) !== '') $page_title = $tmp;
    }
    if (!isset($page_desc) || !is_string($page_desc) || trim($page_desc) === '') {
      $tmp = mk_view_get('page_desc'); if (is_string($tmp) && trim($tmp) !== '') $page_desc = $tmp;
    }
    if (!isset($nav_active) || !is_string($nav_active) || trim($nav_active) === '') {
      $tmp = mk_view_get('nav_active'); if (is_string($tmp) && trim($tmp) !== '') $nav_active = $tmp;
    }
    if (!isset($active_nav) || !is_string($active_nav) || trim($active_nav) === '') {
      $tmp = mk_view_get('active_nav'); if (is_string($tmp) && trim($tmp) !== '') $active_nav = $tmp;
    }
  } catch (Throwable $e) {}
}

/* Page vars */
$page_title = (isset($page_title) && is_string($page_title) && trim($page_title) !== '') ? trim($page_title) : 'Tools • Staff';
$page_desc  = (isset($page_desc)  && is_string($page_desc)  && trim($page_desc)  !== '') ? trim($page_desc)  : 'Diagnostics and maintenance utilities.';
$nav_active = (isset($nav_active) && is_string($nav_active) && trim($nav_active) !== '') ? trim($nav_active) : 'tools';
$active_nav = (isset($active_nav) && is_string($active_nav) && trim($active_nav) !== '') ? trim($active_nav) : $nav_active;

/* Enforce staff login */
if (function_exists('mk_require_staff_login')) {
  mk_require_staff_login();
} elseif (function_exists('mk_require_login')) {
  mk_require_login();
} elseif (function_exists('require_login')) {
  require_login();
} else {
  $login = function_exists('url_for') ? (string)url_for('/staff/login.php') : '/staff/login.php';
  header('Location: ' . $login, true, 302);
  exit;
}

/* Optional RBAC permission gate */
if (function_exists('mk_require_staff_permission')) {
  try {
    mk_require_staff_permission('tools.view');
  } catch (Throwable $e) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden: tools.view required.";
    exit;
  }
}

/* Normalize extra_css and ensure staff.css is always present */
$extra_css_in = $extra_css ?? [];
if (is_string($extra_css_in)) $extra_css_in = [$extra_css_in];
if (!is_array($extra_css_in)) $extra_css_in = [];

$norm = static function($p): ?string {
  if (!is_string($p)) return null;
  $p = trim($p);
  if ($p === '') return null;
  if (preg_match('~^https?://~i', $p)) return $p;
  if ($p[0] !== '/') $p = '/' . $p;
  return $p;
};

$final_css = [];
$seen = [];

/* Ensure both ui.css and staff.css are always present */
$must = [
  '/lib/css/ui.css',
  '/lib/css/staff.css',
];

foreach (array_merge($must, $extra_css_in) as $x) {
  $x = $norm($x);
  if (!$x) continue;
  $k = strtolower($x);
  if (isset($seen[$k])) continue;
  $seen[$k] = true;
  $final_css[] = $x;
}

$extra_css = $final_css;

/* Ensure body class indicates tools context */
if (!isset($GLOBALS['mk_body_class']) || !is_string($GLOBALS['mk_body_class']) || trim($GLOBALS['mk_body_class']) === '') {
  $GLOBALS['mk_body_class'] = 'staff tools';
} else {
  $bc = trim((string)$GLOBALS['mk_body_class']);
  if (stripos($bc, 'staff') === false) $bc .= ' staff';
  if (stripos($bc, 'tools') === false) $bc .= ' tools';
  $GLOBALS['mk_body_class'] = trim($bc);
}

/* ---------------------------------------------------------
   AUTO Tools subnav (only when in tools context)
   Uses staff_header.php contract: $staff_subnav array
--------------------------------------------------------- */
$is_tools_context = (strtolower($active_nav) === 'tools' || strtolower($nav_active) === 'tools');

if ($is_tools_context) {

  $u = static function(string $path): string {
    $path = '/' . ltrim($path, '/');
    if (function_exists('url_for')) return (string)url_for($path);
    return $path;
  };

  // Determine current tool (prefer query param, fallback to last-run tool stored in session)
  $currentTool = '';
  if (isset($_GET['tool']) && is_string($_GET['tool'])) {
    $currentTool = trim($_GET['tool']);
  }
  if ($currentTool === '' && isset($_SESSION['mk_last_tool']) && is_string($_SESSION['mk_last_tool'])) {
    $currentTool = trim($_SESSION['mk_last_tool']);
  }
  // Extra hardening (only allow registry-like keys)
  if ($currentTool !== '' && !preg_match('~^[a-z0-9_]{1,64}$~', $currentTool)) {
    $currentTool = '';
  }

  // Determine whether scan exists (best-effort; no hard dependency)
  $scanKey = 'scan';
  try {
    $reg = (defined('APP_ROOT') ? rtrim((string)APP_ROOT, '/\\') : '') . '/private/functions/staff_tools_registry.php';
    if ($reg !== '' && is_file($reg)) {
      require_once $reg;
      if (function_exists('mk_staff_tools_registry')) {
        $t = mk_staff_tools_registry();
        if (is_array($t)) {
          if (isset($t['scan'])) $scanKey = 'scan';
          elseif (isset($t['project_audit'])) $scanKey = 'project_audit';
        }
      }
    }
  } catch (Throwable $e) {
    // ignore
  }

  $toolsIndex = $u('/staff/tools/');
  $dashboard  = $u('/staff/');
  $scanHref   = $u('/staff/tools/run.php?tool=' . rawurlencode($scanKey) . '&render=pre');

  // If currently on run.php, keep a "Run" entry highlighted.
  $isRunner = (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/staff/tools/run.php') !== false);

  $staff_subnav = (isset($staff_subnav) && is_array($staff_subnav)) ? $staff_subnav : [];

  // Only add these defaults if caller didn't already provide a tools subnav.
  if (!$staff_subnav) {
    $runHref = ($currentTool !== '')
      ? $u('/staff/tools/run.php?tool=' . rawurlencode($currentTool) . '&render=pre')
      : $toolsIndex;

    $staff_subnav = [
      [
        'label'  => 'Tools Index',
        'href'   => $toolsIndex,
        'active' => !$isRunner,
      ],
      [
        'label'  => 'Run',
        'href'   => $runHref,     // <-- now always points to last-run tool when available
        'active' => $isRunner,
      ],
      [
        'label'  => 'Scan',
        'href'   => $scanHref,
        'active' => ($isRunner && $currentTool !== '' && ($currentTool === 'scan' || $currentTool === 'project_audit')),
      ],
      [
        'label'  => 'Dashboard',
        'href'   => $dashboard,
        'active' => false,
      ],
    ];
  }

  // Ensure it is visible to staff_header.php and also via view store.
  $GLOBALS['staff_subnav'] = $staff_subnav;
  if (function_exists('mk_view_set')) {
    try { mk_view_set(['staff_subnav' => $staff_subnav]); } catch (Throwable $e) {}
  }
}

/* Prefer mk_view_set when available */
if (function_exists('mk_view_set')) {
  try {
    mk_view_set([
      'page_title'   => $page_title,
      'page_desc'    => $page_desc,
      'nav_active'   => $nav_active,
      'active_nav'   => $active_nav,
      'extra_css'    => $extra_css,
      'staff_subnav' => (isset($staff_subnav) && is_array($staff_subnav)) ? $staff_subnav : null,
    ]);
  } catch (Throwable $e) {}
}

/* Globals fallback */
$GLOBALS['page_title'] = (string)$page_title;
$GLOBALS['page_desc']  = (string)$page_desc;
$GLOBALS['nav_active'] = (string)$nav_active;
$GLOBALS['active_nav'] = (string)$active_nav;
$GLOBALS['extra_css']  = $extra_css;
if (isset($staff_subnav) && is_array($staff_subnav)) {
  $GLOBALS['staff_subnav'] = $staff_subnav;
}

/* Delegate to staff header (preferred) */
if (function_exists('mk_require_shared')) {
  try { mk_require_shared('staff_header.php'); return; } catch (Throwable $e) {}
}

/* Hard fallback */
header('Content-Type: text/html; charset=UTF-8');
echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>";
echo "<title>" . h($page_title) . "</title>";
foreach ($extra_css as $href) {
  echo "<link rel='stylesheet' href='" . h($href) . "'>";
}
echo "</head><body class='staff tools'><main class='site-main' id='main'>";
$GLOBALS['mk__main_open'] = true;
