<?php
declare(strict_types=1);

/**
 * /private/functions/staff_tools_guard.php
 * Single source of truth guard for /staff/tools/*
 *
 * Policy:
 * - Not logged in  -> redirect to /staff/login.php?return=<current>
 * - Logged in, not admin -> 403 Forbidden
 * - Logged in admin -> allow
 */

function mk_staff_tools_require_admin(): void
{
  if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

  $path = $_SERVER['REQUEST_URI'] ?? '/staff/tools/';
  $return = rawurlencode(parse_url($path, PHP_URL_PATH) ?: '/staff/tools/');

  // Require auth helpers if present
  if (!function_exists('mk_require_staff_login')) {
    $auth = null;
    if (defined('PRIVATE_PATH')) $auth = PRIVATE_PATH . '/functions/auth.php';
    elseif (defined('APP_ROOT')) $auth = rtrim((string)APP_ROOT, '/') . '/private/functions/auth.php';
    if ($auth && is_file($auth)) { require_once $auth; }
  }

  // If your project uses mk_require_staff_login(), use it
  if (function_exists('mk_require_staff_login')) {
    // mk_require_staff_login should redirect if not logged in.
    mk_require_staff_login('/staff/login.php?return=' . $return);
  } else {
    // fallback: assume session key
    if (empty($_SESSION['staff_user_id'])) {
      header('Location: /staff/login.php?return=' . $return, true, 302);
      exit;
    }
  }

  // Admin check (support multiple possible conventions)
  $is_admin = false;

  // Convention A: boolean in session
  if (!empty($_SESSION['is_admin'])) $is_admin = true;

  // Convention B: role string in session
  if (!$is_admin && !empty($_SESSION['staff_role']) && strtolower((string)$_SESSION['staff_role']) === 'admin') {
    $is_admin = true;
  }

  // Convention C: helper function exists
  if (!$is_admin && function_exists('mk_staff_is_admin')) {
    $is_admin = (bool) mk_staff_is_admin();
  }

  if (!$is_admin) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden.";
    exit;
  }
}
