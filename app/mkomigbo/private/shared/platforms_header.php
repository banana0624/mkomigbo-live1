<?php
declare(strict_types=1);

if (!isset($active_nav) || !is_string($active_nav) || $active_nav === '') {
  $active_nav = 'platforms';
}

if (!isset($page_title) || !is_string($page_title) || $page_title === '') {
  $page_title = 'Platforms — Mkomi Igbo';
}

mk_require_shared('public_header.php');