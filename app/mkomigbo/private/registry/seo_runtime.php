<?php
declare(strict_types=1);

/**
 * /private/registry/seo_runtime.php
 * Guarded SEO runtime helpers.
 */

if (defined('MK_SEO_RUNTIME_FILE_LOADED')) {
  return;
}
define('MK_SEO_RUNTIME_FILE_LOADED', true);

if (!function_exists('seo_templates')) {
  function seo_templates(): array {
    $path = __DIR__ . '/seo_templates.php';
    if (is_file($path)) {
      $tpl = include $path;
      return is_array($tpl) ? $tpl : [];
    }
    return [
      'site' => [
        'title'       => 'Mkomigbo • Igbo Heritage Resource Center',
        'description' => 'Explore 19 subjects covering Igbo history, culture, language, people, and more.',
        'keywords'    => 'igbo, culture, history, language, people, biafra, africa',
        'site_name'   => 'Mkomigbo',
        'separator'   => ' • ',
      ],
    ];
  }
}

if (!function_exists('seo_base_vars')) {
  function seo_base_vars(): array {
    $site = seo_templates()['site'] ?? [];
    return [
      'site_name'        => (string)($site['site_name'] ?? 'Mkomigbo'),
      'site_title'       => (string)($site['title'] ?? 'Mkomigbo'),
      'site_description' => (string)($site['description'] ?? ''),
      'site_keywords'    => (string)($site['keywords'] ?? ''),
      'separator'        => (string)($site['separator'] ?? ' • '),
    ];
  }
}

if (!function_exists('seo_apply_template')) {
  function seo_apply_template(string $tpl, array $vars): string {
    $out = preg_replace_callback('/\{([^}]+)\}/', function ($m) use ($vars) {
      $keys = explode('|', $m[1]);
      foreach ($keys as $k) {
        $k = trim($k);
        if ($k === 'separator' && isset($vars['separator'])) return (string)$vars['separator'];
        if (array_key_exists($k, $vars) && $vars[$k] !== '' && $vars[$k] !== null) return (string)$vars[$k];
      }
      return '';
    }, $tpl);

    return is_string($out) ? $out : $tpl;
  }
}

if (!function_exists('seo_render')) {
  function seo_render(string $type, array $vars): array {
    if (function_exists('seo_build')) {
      return seo_build($type, $vars + seo_base_vars());
    }

    $tpls = seo_templates();
    $site = seo_base_vars();
    $t = $tpls[$type] ?? ($tpls['site'] ?? []);

    if (!isset($vars['separator'])) $vars['separator'] = $site['separator'];

    return [
      'title'       => seo_apply_template((string)($t['title'] ?? $site['site_title']), $vars + $site),
      'description' => seo_apply_template((string)($t['description'] ?? $site['site_description']), $vars + $site),
      'keywords'    => seo_apply_template((string)($t['keywords'] ?? $site['site_keywords']), $vars + $site),
    ];
  }
}

if (!function_exists('seo_for_subject')) {
  function seo_for_subject(array $subject): array {
    $vars = [
      'subject_name'             => (string)($subject['name'] ?? ''),
      'subject_meta_description' => (string)($subject['meta_description'] ?? ''),
      'subject_meta_keywords'    => (string)($subject['meta_keywords'] ?? ''),
    ];
    return seo_render('subject', $vars);
  }
}

if (!function_exists('seo_for_page')) {
  function seo_for_page(array $subject, string $pageTitle, ?string $desc = null, ?string $keys = null): array {
    $vars = [
      'page_title'               => $pageTitle,
      'page_meta_description'    => (string)($desc ?? ''),
      'page_meta_keywords'       => (string)($keys ?? ''),
      'subject_meta_description' => (string)($subject['meta_description'] ?? ''),
      'subject_meta_keywords'    => (string)($subject['meta_keywords'] ?? ''),
    ];
    return seo_render('page', $vars);
  }
}
