<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/functions/seo_helpers.php
 *
 * Public SEO helpers:
 * - mk_seo_for_subject_slug($slug, $opts)
 * - mk_seo_for_subject_page($subjectSlug, $pageSlug, $pageTitle, $bodyHtml, $opts)
 * - mk_seo_for_subject_page_slug($subjectSlug, $pageSlug, $ctx)
 * - mk_subject_meta_fallback($subjectSlug)
 *
 * Policy:
 * - ONE source of truth for titles in helpers.
 * - For subject pages:
 *     - Title is helper-composed (templates cannot override).
 *     - Description is helper-composed (templates cannot override).
 *     - Templates may optionally influence keywords + og_type only.
 *
 * Best-practice excerpting:
 * - mk_seo_excerpt() returns '' for placeholder bodies (Coming soon, Under construction, etc.)
 * - Caller then falls back to subject-aware summary (not “Read …”)
 *
 * IMPORTANT:
 * - Guarded against double include
 * - Never redeclares functions
 */

if (defined('MK_SEO_HELPERS_LOADED')) {
  return;
}
define('MK_SEO_HELPERS_LOADED', true);

/* ---------------------------------------------------------
   Load SEO runtime (templates + renderer)
--------------------------------------------------------- */
$__appRoot = defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '';
$__runtime = ($__appRoot !== '') ? ($__appRoot . '/private/registry/seo_runtime.php') : '';
if ($__runtime !== '' && is_file($__runtime)) {
  require_once $__runtime;
}

/* ---------------------------------------------------------
   Load subjects registry (optional)
--------------------------------------------------------- */
$__subreg = ($__appRoot !== '') ? ($__appRoot . '/private/registry/subjects_register.php') : '';
if ($__subreg !== '' && is_file($__subreg)) {
  require_once $__subreg;
}

/* ---------------------------------------------------------
   Small helpers
--------------------------------------------------------- */
if (!function_exists('mk_seo_brand')) {
  function mk_seo_brand(): string
  {
    $b = defined('MK_BRAND_NAME') ? (string)MK_BRAND_NAME : 'Mkomigbo';
    $b = trim($b);
    return $b !== '' ? $b : 'Mkomigbo';
  }
}

if (!function_exists('mk_seo_nice')) {
  function mk_seo_nice(string $slug): string
  {
    $slug = trim($slug);
    if ($slug === '') return '';
    $s = str_replace(['-', '_'], ' ', $slug);
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    $s = trim($s);
    return $s !== '' ? ucfirst($s) : '';
  }
}

if (!function_exists('mk_seo_is_placeholder_text')) {
  /**
   * Detect “placeholder” page bodies that should NOT be used as meta descriptions.
   */
  function mk_seo_is_placeholder_text(string $txt): bool
  {
    $t = trim($txt);
    if ($t === '') return true;

    // Normalize (lowercase + collapse spaces)
    if (function_exists('mb_strtolower')) {
      $t = mb_strtolower($t, 'UTF-8');
    } else {
      $t = strtolower($t);
    }
    $t = preg_replace('/\s+/u', ' ', $t) ?? $t;
    $t = trim($t);

    // Too short = low-value snippet
    $len = (function_exists('mb_strlen')) ? mb_strlen($t, 'UTF-8') : strlen($t);
    if ($len < 40) return true;

    $patterns = [
      '/\bcoming\s+soon\b/u',
      '/\bunder\s+construction\b/u',
      '/\bwork\s+in\s+progress\b/u',
      '/\bwip\b/u',
      '/\bnot\s+available\s+yet\b/u',
      '/\bcontent\s+coming\s+soon\b/u',
      '/\bstay\s+tuned\b/u',
      '/\bwe(\'|’)?ll\s+be\s+back\b/u',
      '/\bpage\s+is\s+being\s+updated\b/u',
      '/\bmore\s+to\s+come\b/u',
      '/\bin\s+progress\b/u',
      '/\bdraft\b/u',
      '/\bplaceholder\b/u',
      '/\bto\s+be\s+written\b/u',
      '/\btbd\b/u',
      '/\btba\b/u',
    ];
    foreach ($patterns as $rx) {
      if (preg_match($rx, $t)) return true;
    }

    // Repeated-token heuristic (very low signal)
    $tokens = preg_split('/\s+/u', $t) ?: [];
    if (count($tokens) >= 8) {
      $freq = [];
      foreach ($tokens as $w) {
        $w = preg_replace('/[^\p{L}\p{N}]+/u', '', $w) ?? $w;
        if ($w === '') continue;
        $freq[$w] = ($freq[$w] ?? 0) + 1;
      }
      arsort($freq);
      $top = (int)($freq ? reset($freq) : 0);
      if ($top >= 5) return true;
    }

    return false;
  }
}

if (!function_exists('mk_seo_excerpt')) {
  /**
   * Extract a clean excerpt.
   * Returns '' if content is empty OR placeholder.
   */
  function mk_seo_excerpt(string $html, int $limit = 220): string
  {
    $txt = trim(strip_tags($html));
    $txt = preg_replace('/\s+/u', ' ', $txt) ?? $txt;
    $txt = trim($txt);

    if ($txt === '') return '';
    if (mk_seo_is_placeholder_text($txt)) return '';

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
      return (mb_strlen($txt, 'UTF-8') > $limit)
        ? (rtrim(mb_substr($txt, 0, $limit, 'UTF-8')) . '…')
        : $txt;
    }
    return (strlen($txt) > $limit)
      ? (rtrim(substr($txt, 0, $limit)) . '…')
      : $txt;
  }
}

/* ---------------------------------------------------------
   Subject fallback
--------------------------------------------------------- */
if (!function_exists('mk_subject_meta_fallback')) {
  function mk_subject_meta_fallback(string $subjectSlug): array
  {
    $brand = mk_seo_brand();
    $subjectSlug = strtolower(trim($subjectSlug));
    $nice = $subjectSlug !== '' ? mk_seo_nice($subjectSlug) : 'Subject';

    return [
      'title'       => $nice . ' • ' . $brand,
      'description' => 'Pages related to ' . $nice . '.',
      'keywords'    => $subjectSlug !== ''
        ? ($subjectSlug . ', heritage, igbo, ' . strtolower($brand))
        : ('heritage, igbo, ' . strtolower($brand)),
      'og_type'     => 'website',
    ];
  }
}

/* ---------------------------------------------------------
   SEO: subject landing
--------------------------------------------------------- */
if (!function_exists('mk_seo_for_subject_slug')) {
  function mk_seo_for_subject_slug(string $slug, array $opts = []): array
  {
    $brand = mk_seo_brand();
    $slug = strtolower(trim($slug));
    if ($slug === '') return mk_subject_meta_fallback('');

    // Registry subject lookup (optional)
    $subject = null;
    if (function_exists('subject_by_slug_registry')) {
      try {
        $r = subject_by_slug_registry($slug);
        if (is_array($r)) $subject = $r;
      } catch (Throwable $e) {}
    }

    $name = '';
    $desc = '';
    $keys = '';

    if (is_array($subject)) {
      $name = (string)($subject['name'] ?? $subject['menu_name'] ?? $subject['title'] ?? '');
      $desc = (string)($subject['meta_description'] ?? $subject['description'] ?? $subject['short_desc'] ?? '');
      $keys = (string)($subject['meta_keywords'] ?? $subject['keywords'] ?? '');
    }

    $niceName = trim($name) !== '' ? trim($name) : mk_seo_nice($slug);
    if ($niceName === '') $niceName = mk_seo_nice($slug);

    $desc = trim($desc);
    $keys = trim($keys);

    // Runtime templates (subject) may override subject landing meta.
    if (function_exists('seo_render')) {
      $vars = [
        'subject_name'             => $niceName,
        'subject_meta_description' => $desc,
        'subject_meta_keywords'    => $keys,
        'brand'                    => $brand,
      ];
      $out = seo_render('subject', $vars);

      if (is_array($out)) {
        $t = (isset($out['title']) && is_string($out['title']) && trim($out['title']) !== '')
          ? trim($out['title'])
          : ($niceName . ' • ' . $brand);

        if (isset($out['description']) && is_string($out['description']) && trim($out['description']) !== '') {
          $desc = trim($out['description']);
        }
        if (isset($out['keywords']) && is_string($out['keywords']) && trim($out['keywords']) !== '') {
          $keys = trim($out['keywords']);
        }
        $ogType = (isset($out['og_type']) && is_string($out['og_type']) && trim($out['og_type']) !== '')
          ? trim($out['og_type'])
          : 'website';

        if ($desc === '') $desc = 'Pages related to ' . $niceName . '.';
        if ($keys === '') $keys = $slug . ', heritage, igbo, ' . strtolower($brand);

        return [
          'title'       => $t,
          'description' => $desc,
          'keywords'    => $keys,
          'og_type'     => $ogType,
        ];
      }
    }

    // Fallback
    if ($desc === '') $desc = 'Pages related to ' . $niceName . '.';
    if ($keys === '') $keys = $slug . ', heritage, igbo, ' . strtolower($brand);

    return [
      'title'       => $niceName . ' • ' . $brand,
      'description' => $desc,
      'keywords'    => $keys,
      'og_type'     => 'website',
    ];
  }
}

/* ---------------------------------------------------------
   SEO: subject page
   - Title locked (helper-owned)
   - Description locked (helper-owned)
   - Templates may influence keywords + og_type only
--------------------------------------------------------- */
if (!function_exists('mk_seo_for_subject_page')) {
  function mk_seo_for_subject_page(string $subjectSlug, string $pageSlug, string $pageTitle, string $bodyHtml = '', array $opts = []): array
  {
    $brand = mk_seo_brand();

    $subjectSlug = strtolower(trim($subjectSlug));
    $pageSlug    = strtolower(trim($pageSlug));
    $pageTitle   = trim($pageTitle);

    $subjectSeo  = mk_seo_for_subject_slug($subjectSlug, $opts);

    // Clean subject name
    $subjectName = '';
    if (isset($opts['subject_name']) && is_string($opts['subject_name'])) $subjectName = trim($opts['subject_name']);
    if ($subjectName === '') $subjectName = mk_seo_nice($subjectSlug);
    if ($subjectName === '') $subjectName = ($subjectSlug !== '' ? $subjectSlug : 'Subject');

    $pt = ($pageTitle !== '' ? $pageTitle : mk_seo_nice($pageSlug));
    if ($pt === '') $pt = ($pageSlug !== '' ? $pageSlug : 'Page');

    // Title (locked)
    $title = $pt . ' • ' . $subjectName . ' • ' . $brand;

    // Description (locked)
    $desc = '';
    if (isset($opts['lede']) && is_string($opts['lede'])) $desc = trim($opts['lede']);
    if ($desc === '' && isset($opts['description']) && is_string($opts['description'])) $desc = trim($opts['description']);

    if ($desc === '') {
      $desc = mk_seo_excerpt($bodyHtml, 220); // returns '' if placeholder
    }
    if ($desc === '') {
      // Subject-aware fallback (not “Read …”)
      $desc = 'Learn about ' . $pt . ' in ' . $subjectName . ' on ' . $brand . '.';
    }

    // Keywords (default)
    $keywords = '';
    $baseKeys = trim((string)($subjectSeo['keywords'] ?? ''));
    if ($baseKeys !== '') $keywords = $baseKeys;
    $keywords = trim($keywords);
    if ($keywords === '') $keywords = $subjectSlug . ', ' . $pageSlug . ', igbo, ' . strtolower($brand);

    // og_type default
    $ogType = (isset($opts['og_type']) && is_string($opts['og_type']) && trim($opts['og_type']) !== '')
      ? trim($opts['og_type'])
      : 'article';

    // Runtime templates: keywords + og_type only (NO title, NO description)
    if (function_exists('seo_render')) {
      $vars = [
        'page_title'               => $pt,
        'page_meta_description'    => $desc,
        'page_meta_keywords'       => $keywords,
        'subject_name'             => $subjectName,
        'subject_meta_description' => (string)($subjectSeo['description'] ?? ''),
        'subject_meta_keywords'    => (string)($subjectSeo['keywords'] ?? ''),
        'brand'                    => $brand,
      ];
      $out = seo_render('page', $vars);

      if (is_array($out)) {
        if (isset($out['keywords']) && is_string($out['keywords']) && trim($out['keywords']) !== '') {
          $keywords = trim($out['keywords']);
        }
        if (isset($out['og_type']) && is_string($out['og_type']) && trim($out['og_type']) !== '') {
          $ogType = trim($out['og_type']);
        }
      }
    }

    return [
      'title'       => $title,
      'description' => $desc,
      'keywords'    => $keywords,
      'og_type'     => $ogType !== '' ? $ogType : 'article',
    ];
  }
}

/* ---------------------------------------------------------
   Compatibility wrapper used by /public/subjects/page.php
--------------------------------------------------------- */
if (!function_exists('mk_seo_for_subject_page_slug')) {
  function mk_seo_for_subject_page_slug(string $subjectSlug, string $pageSlug, array $ctx = []): array
  {
    $subjectSlug = strtolower(trim($subjectSlug));
    $pageSlug    = strtolower(trim($pageSlug));

    $subject = (isset($ctx['subject']) && is_array($ctx['subject'])) ? $ctx['subject'] : [];
    $page    = (isset($ctx['page']) && is_array($ctx['page'])) ? $ctx['page'] : [];

    $subjectName = '';
    if (isset($subject['name']) && is_string($subject['name'])) $subjectName = trim($subject['name']);
    if ($subjectName === '' && isset($subject['menu_name']) && is_string($subject['menu_name'])) $subjectName = trim($subject['menu_name']);
    if ($subjectName === '' && isset($subject['title']) && is_string($subject['title'])) $subjectName = trim($subject['title']);
    if ($subjectName === '') $subjectName = mk_seo_nice($subjectSlug);
    if ($subjectName === '') $subjectName = ($subjectSlug !== '' ? $subjectSlug : 'Subject');

    $pageTitle = '';
    if (isset($page['title']) && is_string($page['title'])) $pageTitle = trim($page['title']);
    if ($pageTitle === '' && isset($page['menu_name']) && is_string($page['menu_name'])) $pageTitle = trim($page['menu_name']);
    if ($pageTitle === '' && isset($page['slug']) && is_string($page['slug'])) $pageTitle = mk_seo_nice((string)$page['slug']);
    if ($pageTitle === '') $pageTitle = mk_seo_nice($pageSlug);
    if ($pageTitle === '') $pageTitle = ($pageSlug !== '' ? $pageSlug : 'Page');

    $bodyHtml = '';
    if (isset($page['body_html']) && is_string($page['body_html'])) $bodyHtml = (string)$page['body_html'];
    elseif (isset($page['body']) && is_string($page['body'])) $bodyHtml = (string)$page['body'];
    elseif (isset($page['content']) && is_string($page['content'])) $bodyHtml = (string)$page['content'];

    $opts = [
      'subject_name' => $subjectName,
    ];

    if (isset($ctx['lede']) && is_string($ctx['lede']) && trim($ctx['lede']) !== '') {
      $opts['lede'] = trim($ctx['lede']);
    }
    if (isset($ctx['description']) && is_string($ctx['description']) && trim($ctx['description']) !== '') {
      $opts['description'] = trim($ctx['description']);
    }
    if (isset($ctx['og_type']) && is_string($ctx['og_type']) && trim($ctx['og_type']) !== '') {
      $opts['og_type'] = trim($ctx['og_type']);
    }

    return mk_seo_for_subject_page($subjectSlug, $pageSlug, $pageTitle, $bodyHtml, $opts);
  }
}
