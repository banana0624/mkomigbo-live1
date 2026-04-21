<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/shared/contributors_header.php
 *
 * Contributors public header wrapper (single, predictable bundle).
 *
 * Expected optional vars before include:
 * - $page_title (string)
 * - $page_desc  (string)
 * - $extra_css  (array of href strings)
 * - $extra_js   (array of src strings)
 * - $nav_active / $active_nav
 *
 * Delegates rendering to:
 * - /app/mkomigbo/private/shared/public_header.php
 */

if (!isset($nav_active) || !is_string($nav_active) || trim($nav_active) === '') {
  $nav_active = 'contributors';
}
if (!isset($active_nav) || !is_string($active_nav) || trim($active_nav) === '') {
  $active_nav = $nav_active;
}

if (!isset($page_title) || !is_string($page_title) || trim($page_title) === '') {
  $page_title = 'Contributors — Mkomi Igbo';
}
if (!isset($page_desc) || !is_string($page_desc) || trim($page_desc) === '') {
  $page_desc = 'Authors, editors, researchers, and collaborators helping to build and refine Mkomi Igbo.';
}

/**
 * Default Contributors CSS bundle.
 * - ui.css          : global tokens + nav + components
 * - public.css      : public page typography/layout base
 * You can add page-specific css via $extra_css (e.g. article.css, grid css, etc.)
 */
$defaults = [
  (function_exists('url_for') ? url_for('/lib/css/ui.css')     : '/lib/css/ui.css'),
  (function_exists('url_for') ? url_for('/lib/css/public.css') : '/lib/css/public.css'),
];

if (!isset($extra_css) || !is_array($extra_css)) {
  $extra_css = [];
}

/* Merge defaults first, then any page-specific css (no duplicates) */
$merged = array_merge($defaults, $extra_css);
$extra_css = [];
foreach ($merged as $href) {
  $href = (string)$href;
  if ($href === '') { continue; }
  if (!in_array($href, $extra_css, true)) {
    $extra_css[] = $href;
  }
}

/* Delegate to canonical public header */
$public_header = __DIR__ . '/public_header.php';

mk_require_shared('public_header.php');