<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

if (function_exists('mk_staff_logout')) {
  mk_staff_logout();
} else {
  mk_staff_session_start();

  $_SESSION = [];

  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
      session_name(),
      '',
      time() - 42000,
      $p['path'] ?? '/',
      $p['domain'] ?? '',
      (bool)($p['secure'] ?? false),
      (bool)($p['httponly'] ?? true)
    );
  }

  if (session_status() === PHP_SESSION_ACTIVE) {
    @session_destroy();
  }
}

header('Location: /staff/login.php?logged_out=1', true, 302);
exit;