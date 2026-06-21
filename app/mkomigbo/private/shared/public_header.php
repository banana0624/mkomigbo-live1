<?php
declare(strict_types=1);

if (defined('MK_PUBLIC_HEADER_INCLUDED')) { return; }
define('MK_PUBLIC_HEADER_INCLUDED', true);

if (!function_exists('mk_require_shared')) {
    function mk_require_shared(string $file): void {
        $path = PRIVATE_PATH . '/shared/' . $file;
        if (!file_exists($path)) {
            throw new RuntimeException("Missing shared file: {$file} at {$path}");
        }
        require_once $path;
    }
}

if (!function_exists('h')) {
    function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

$mk_page_title = isset($page_title) && is_string($page_title) && trim($page_title) !== ''
    ? trim($page_title) . ' – Mkomigbo'
    : 'Mkomigbo – Igbo Knowledge Platform';

$mk_extra_css = isset($extra_css) && is_array($extra_css) ? $extra_css : [];

$mk_nav_active = isset($nav_active) ? (string)$nav_active : '';

$mk_nav = [
    'subjects'     => ['/subjects/',     'Subjects'],
    'platforms'    => ['/platforms/',    'Platforms'],
    'contributors' => ['/contributors/', 'Contributors'],
    'calendar'     => ['/igbo-calendar/','Calendar'],
];

$GLOBALS['mk__main_open'] = true;
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($mk_page_title) ?></title>
<?php if (isset($page_desc) && is_string($page_desc) && trim($page_desc) !== ''): ?>
<meta name="description" content="<?= h(trim($page_desc)) ?>">
<?php endif; ?>
<link rel="stylesheet" href="/assets/css/ui.css">
<link rel="stylesheet" href="/assets/css/public.css">
<?php foreach ($mk_extra_css as $css): ?>
<?php if (is_string($css) && trim($css) !== ''): ?>
<link rel="stylesheet" href="<?= h(str_replace('/lib/css/', '/assets/css/', trim($css))) ?>">
<?php endif; ?>
<?php endforeach; ?>
</head>
<body>
<header class="site-header">
  <div class="container" style="display:flex;align-items:center;justify-content:space-between;min-height:64px;gap:14px;">
    <a href="/" class="brand" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;color:inherit;">
      <img src="/assets/images/logos/mk-logo.png" alt="Mkomigbo" width="34" height="34" style="border-radius:9px;border:1px solid var(--border);">
      <span class="brand__text">
        <span class="brand__title">Mkomigbo</span>
        <span class="brand__sub">Knowledge Platform</span>
      </span>
    </a>
    <nav style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">
      <?php foreach ($mk_nav as $key => [$href, $label]): ?>
      <a href="<?= h($href) ?>"
         style="padding:7px 12px;border-radius:10px;text-decoration:none;font-weight:600;font-size:.9rem;<?= $mk_nav_active === $key ? 'background:rgba(13,110,253,.10);color:var(--brand);' : 'color:var(--muted);' ?>">
        <?= h($label) ?>
      </a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
<div class="mk-main container" style="padding-top:20px;padding-bottom:48px;">
