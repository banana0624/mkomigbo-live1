<?php
declare(strict_types=1);

if (!function_exists('h')) {
  function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
  }
}

if (!empty($GLOBALS['__mk_staff_header_printed'])) {
  return;
}
$GLOBALS['__mk_staff_header_printed'] = true;

if (function_exists('mk_staff_session_start')) {
  mk_staff_session_start();
} elseif (function_exists('mk__session_start')) {
  mk__session_start();
}

/* ---------------------------
   SAFE URL + ASSET RESOLVER
--------------------------- */

$to_url = static function (string $path): string {
  $path = trim($path);
  if ($path === '') return '';
  if ($path[0] !== '/') $path = '/' . $path;
  return $path;
};

$asset = static function (string $path) use ($to_url): string {
  $url = $to_url($path);

  $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
  $fs = $docRoot . $url;

  if (!is_file($fs)) {
    // 🔥 FALLBACK to /assets
    $alt = '/assets' . $url;
    if (is_file($docRoot . $alt)) {
      $url = $alt;
      $fs  = $docRoot . $alt;
    }
  }

  if (is_file($fs)) {
    $v = @filemtime($fs);
    if ($v) {
      return $url . '?v=' . $v;
    }
  }

  return $url;
};

/* ---------------------------
   REQUIRED CSS
--------------------------- */

$css_urls = [
  $asset('/lib/css/ui.css'),
  $asset('/lib/css/staff.css'),
];

/* ---------------------------
   USER STATE
--------------------------- */

$staff_logged_in = isset($_SESSION['staff_user_id']);
$staff_email = (string)($_SESSION['staff_email'] ?? '');

/* ---------------------------
   URLs
--------------------------- */

$u_dashboard = $to_url('/staff/');
$u_logout    = $to_url('/staff/logout.php');
$u_login     = $to_url('/staff/login.php');
$u_account   = $to_url('/staff/account/');
$logo_url    = $asset('/lib/images/mk-logo.svg');

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= h($page_title ?? 'Staff') ?></title>

<?php foreach ($css_urls as $href): ?>
<link rel="stylesheet" href="<?= h($href) ?>">
<?php endforeach; ?>

</head>

<body class="staff">

<header class="staff-topbar">
  <div class="staff-topbar__inner">

    <a class="staff-brand" href="<?= h($u_dashboard) ?>">
      <span class="staff-brand__mark">
        <!-- ✅ FIXED IMAGE -->
        <img src="<?= h($logo_url) ?>" alt="Mkomi Igbo Logo" width="26" height="26">
      </span>
      <span>
        <strong>Mkomi Igbo</strong><br>
        Staff
      </span>
    </a>

    <div>
      <?php if ($staff_logged_in): ?>
        <?= h($staff_email) ?>
        <a href="<?= h($u_account) ?>">Account</a>
        <a href="<?= h($u_logout) ?>">Logout</a>
      <?php else: ?>
        <a href="<?= h($u_login) ?>">Login</a>
      <?php endif; ?>
    </div>

  </div>
</header>

<main class="staff-main">
<?php $GLOBALS['mk__main_open'] = true; ?>