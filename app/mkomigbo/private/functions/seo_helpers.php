<?php
declare(strict_types=1);

/**
 * /private/functions/seo_helpers.php
 * Safe loader + wrappers for registry SEO runtime/templates.
 *
 * Goals:
 * - Load /private/registry/seo_runtime.php only when needed
 * - Prevent redeclare fatals by loading once
 * - Provide simple helpers for headers/pages
 */
 
 if (!function_exists('mk_require_subjects_registry')) {
  function mk_require_subjects_registry(): void
  {
    if (!defined('PRIVATE_PATH') || !is_string(PRIVATE_PATH) || PRIVATE_PATH === '') return;

    $fn = rtrim(PRIVATE_PATH, "/\\") . '/registry/subjects_register.php';
    if (is_file($fn)) require_once $fn;
  }
}


if (defined('MK_SEO_HELPERS_LOADED')) {
  return;
}
define('MK_SEO_HELPERS_LOADED', true);

if (!function_exists('mk_require_subjects_registry')) {
  function mk_require_subjects_registry(): void
  {
    // subjects_register.php lives under PRIVATE_PATH/registry
    if (!defined('PRIVATE_PATH') || !is_string(PRIVATE_PATH) || PRIVATE_PATH === '') return;

    $fn = rtrim((string)PRIVATE_PATH, "/\\") . '/registry/subjects_register.php';
    if (is_file($fn)) require_once $fn;
  }
}


/** Load SEO runtime (registry-based) exactly once */
if (!function_exists('mk_require_seo_runtime')) {
  function mk_require_seo_runtime(): void
  {
    // runtime file has its own guard too (MK_SEO_RUNTIME_LOADED)
    if (defined('MK_SEO_RUNTIME_LOADED')) return;

    if (!defined('PRIVATE_PATH')) return;

    $rt = rtrim((string)PRIVATE_PATH, "/\\") . '/registry/seo_runtime.php';
    if (!is_file($rt)) return;

    require_once $rt;
  }
}

if (!function_exists('mk_require_subjects_registry')) {
  function mk_require_subjects_registry(): void
  {
    if (!defined('PRIVATE_PATH')) return;
    $fn = rtrim((string)PRIVATE_PATH, "/\\") . '/registry/subjects_register.php';
    if (is_file($fn)) require_once $fn;
  }
}

/**
 * Build SEO array for a subject, using:
 * - DB subject fields if present
 * - registry fallback fields if missing
 */

function mk_seo_for_subject_slug(string $slug, array $dbSubjectRow = []): array
{
  mk_require_seo_runtime();
  mk_require_subjects_registry();

  $fallback = function_exists('mk_subject_meta_fallback')
    ? mk_subject_meta_fallback($slug)
    : ['meta_description' => '', 'meta_keywords' => '', 'icon' => '', 'name' => ''];

  // NAME: DB -> registry -> slug
  $name = trim((string)($dbSubjectRow['name'] ?? ''));
  if ($name === '') $name = trim((string)($fallback['name'] ?? ''));
  if ($name === '') $name = $slug;

  $subject = [
    'name'             => $name,
    'meta_description' => (string)($dbSubjectRow['meta_description'] ?? $fallback['meta_description'] ?? ''),
    'meta_keywords'    => (string)($dbSubjectRow['meta_keywords'] ?? $fallback['meta_keywords'] ?? ''),
    'icon'             => (string)($dbSubjectRow['icon'] ?? $fallback['icon'] ?? ''),
    'slug'             => $slug,
  ];

  if (function_exists('seo_for_subject')) {
    return seo_for_subject($subject);
  }

  return [
    'title'       => $subject['name'] . ' • Mkomigbo',
    'description' => (string)$subject['meta_description'],
    'keywords'    => (string)$subject['meta_keywords'],
  ];
}


/** Render <title> + meta tags (simple, safe) */
if (!function_exists('mk_seo_echo_tags')) {
  function mk_seo_echo_tags(array $seo): void
  {
    if (!function_exists('h')) {
      // If your global h() exists, this won't be used. This is just safety.
      $h = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    } else {
      $h = static fn(string $v): string => h($v);
    }

    $title = (string)($seo['title'] ?? '');
    $desc  = (string)($seo['description'] ?? '');
    $keys  = (string)($seo['keywords'] ?? '');

    if ($title !== '') {
      echo "<title>" . $h($title) . "</title>\n";
    }
    if ($desc !== '') {
      echo '<meta name="description" content="' . $h($desc) . '">' . "\n";
    }
    if ($keys !== '') {
      echo '<meta name="keywords" content="' . $h($keys) . '">' . "\n";
    }
  }
}
