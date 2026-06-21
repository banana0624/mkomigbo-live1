<?php
declare(strict_types=1);

/**
 * /private/functions/helpers.php
 *
 * Shared small helpers used across Mkomigbo.
 *
 * Rules:
 * - No output, no redirects, no header() calls.
 * - Safe to include multiple times (idempotent).
 * - Keep dependencies minimal; functions should be pure where possible.
 */

if (defined('MK_HELPERS_LOADED')) {
  return;
}
define('MK_HELPERS_LOADED', true);

/* ---------------------------------------------------------
 * Environment / feature flags
 * --------------------------------------------------------- */

/**
 * Returns true if MK_DEBUG is enabled (constant or env).
 */
if (!function_exists('mk_is_debug')) {
  function mk_is_debug(): bool {
    if (defined('MK_DEBUG')) return (bool)MK_DEBUG;
    $v = getenv('MK_DEBUG');
    if ($v === false) return false;
    $v = strtolower(trim((string)$v));
    return in_array($v, ['1', 'true', 'yes', 'on'], true);
  }
}

/* ---------------------------------------------------------
 * String helpers
 * --------------------------------------------------------- */

/**
 * Safe trim that normalizes whitespace.
 */
if (!function_exists('mk_trim')) {
  function mk_trim(string $s): string {
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return $s;
  }
}

/**
 * Canonical slugify (single source of truth).
 *
 * BACKWARD COMPATIBILITY:
 * - Old helpers.php style: mk_slugify($text, $fallback)
 * - Old slug.php style:    mk_slugify($text, $delimiter)
 *
 * This implementation supports BOTH safely by interpreting the 2nd argument:
 * - If $arg2 looks like a delimiter (e.g. '-', '_', '.', '~'), it is treated as delimiter.
 * - Otherwise it is treated as fallback.
 *
 * Recommended new usage (optional):
 * - mk_slugify($text)                      -> delimiter '-', fallback 'item'
 * - mk_slugify($text, '-')                 -> delimiter '-', fallback 'item'
 * - mk_slugify($text, 'contributor')       -> delimiter '-', fallback 'contributor'
 *
 * Notes:
 * - Keeps Unicode letters/numbers (\p{L}\p{N})
 * - Converts runs of non-letter/number into the delimiter
 * - Lowercases (mb_strtolower when available)
 * - Enforces length bound (190) to match your slug patterns
 */
if (!function_exists('mk_slugify')) {
  function mk_slugify(string $text, string $arg2 = 'item', string $fallback = 'item'): string
  {
    $text = mk_trim($text);
    if ($text === '') {
      // If caller passed fallback in arg2 (helpers.php old style), prefer it.
      // If caller passed delimiter in arg2 (slug.php old style), fall back to $fallback.
      $maybeDelimiter = $arg2;
      $looksDelimiter = (bool)preg_match('/^[^a-z0-9]{1,3}$/i', $maybeDelimiter);
      return $looksDelimiter ? $fallback : $arg2;
    }

    // Determine delimiter vs fallback based on arg2
    $delimiter = '-';
    $fb = 'item';

    $looksDelimiter = (bool)preg_match('/^[^a-z0-9]{1,3}$/i', $arg2); // '-', '_', '.', etc.
    if ($looksDelimiter) {
      $delimiter = $arg2;
      $fb = $fallback;
    } else {
      $delimiter = '-';
      $fb = $arg2; // old helpers.php style
    }

    if ($delimiter === '') $delimiter = '-';
    if ($fb === '') $fb = 'item';

    // Lowercase safely
    if (function_exists('mb_strtolower')) {
      $text = mb_strtolower($text, 'UTF-8');
    } else {
      $text = strtolower($text);
    }

    // Replace runs of non-letter/number with delimiter (Unicode-safe)
    $text = preg_replace('/[^\p{L}\p{N}]+/u', $delimiter, $text) ?? $text;

    // Trim delimiters and whitespace
    $text = trim($text, $delimiter . " \t\n\r\0\x0B");

    // Collapse duplicate delimiters
    $q = preg_quote($delimiter, '/');
    $text = preg_replace('/' . $q . '+/u', $delimiter, $text) ?? $text;

    // Length bound consistent with your routing constraints
    if (strlen($text) > 190) {
      $text = substr($text, 0, 190);
      $text = rtrim($text, $delimiter);
    }

    return $text !== '' ? $text : $fb;
  }
}

/**
 * Tight HTML stripping helper (for building search excerpts or safe summaries).
 */
if (!function_exists('mk_plaintext')) {
  function mk_plaintext(string $s): string {
    $s = strip_tags($s);
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return mk_trim($s);
  }
}

/* ---------------------------------------------------------
 * Schema-tolerant helpers
 * --------------------------------------------------------- */

/**
 * Returns true if a table exists in the current DB.
 */
if (!function_exists('mk_table_exists')) {
  function mk_table_exists(PDO $db, string $table): bool {
    $table = trim($table);
    if ($table === '') return false;

    $stmt = $db->prepare("
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = :t
      LIMIT 1
    ");
    $stmt->execute([':t' => $table]);
    return (bool)$stmt->fetchColumn();
  }
}

/**
 * Returns true if a column exists on a table in the current DB.
 */
if (!function_exists('mk_column_exists')) {
  function mk_column_exists(PDO $db, string $table, string $column): bool {
    $table = trim($table);
    $column = trim($column);
    if ($table === '' || $column === '') return false;

    $stmt = $db->prepare("
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = :t
        AND COLUMN_NAME = :c
      LIMIT 1
    ");
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (bool)$stmt->fetchColumn();
  }
}

/* ---------------------------------------------------------
 * Contributor slug helpers (your requested logic, hardened)
 * --------------------------------------------------------- */

/**
 * Checks whether a contributor slug exists.
 */
if (!function_exists('mk_contributor_slug_exists')) {
  function mk_contributor_slug_exists(PDO $db, string $slug, ?int $excludeId = null): bool {
    $slug = trim($slug);
    if ($slug === '') return false;

    if (!mk_table_exists($db, 'contributors') || !mk_column_exists($db, 'contributors', 'slug')) {
      return false;
    }

    if ($excludeId !== null && $excludeId > 0) {
      $stmt = $db->prepare("SELECT 1 FROM contributors WHERE slug = :slug AND id <> :id LIMIT 1");
      $stmt->execute([':slug' => $slug, ':id' => $excludeId]);
    } else {
      $stmt = $db->prepare("SELECT 1 FROM contributors WHERE slug = :slug LIMIT 1");
      $stmt->execute([':slug' => $slug]);
    }

    return (bool)$stmt->fetchColumn();
  }
}

/**
 * Generates a unique contributor slug.
 */
if (!function_exists('mk_unique_contributor_slug')) {
  function mk_unique_contributor_slug(PDO $db, string $baseSlug, ?int $excludeId = null): string {
    $baseSlug = mk_slugify($baseSlug, 'contributor');

    $slug = $baseSlug;
    $i = 2;

    while (mk_contributor_slug_exists($db, $slug, $excludeId)) {
      $slug = $baseSlug . '-' . $i;
      $i++;

      if ($i > 9999) {
        try {
          $slug = $baseSlug . '-' . bin2hex(random_bytes(3));
        } catch (Throwable $e) {
          $slug = $baseSlug . '-' . (string)mt_rand(100000, 999999);
        }
        break;
      }
    }

    return $slug;
  }
}

/**
 * Convenience: derive a contributor slug from a display name and make it unique.
 */
if (!function_exists('mk_contributor_slug_from_name')) {
  function mk_contributor_slug_from_name(PDO $db, string $displayName, ?int $excludeId = null): string {
    $base = mk_slugify($displayName, 'contributor');
    return mk_unique_contributor_slug($db, $base, $excludeId);
  }
}

/* ---------------------------------------------------------
 * Small request helpers (safe, no side effects)
 * --------------------------------------------------------- */

if (!function_exists('mk_is_post')) {
  function mk_is_post(): bool {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
  }
}

if (!function_exists('mk_is_get')) {
  function mk_is_get(): bool {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
  }
}

/**
 * Safely fetch an int from GET/POST arrays.
 */
if (!function_exists('mk_int_param')) {
  function mk_int_param(array $src, string $key, ?int $default = null): ?int {
    if (!array_key_exists($key, $src)) return $default;
    $v = filter_var($src[$key], FILTER_VALIDATE_INT);
    return ($v !== false) ? (int)$v : $default;
  }
}

/**
 * Safely fetch a trimmed string from GET/POST arrays.
 */
if (!function_exists('mk_str_param')) {
  function mk_str_param(array $src, string $key, string $default = ''): string {
    if (!array_key_exists($key, $src)) return $default;
    $v = (string)$src[$key];
    $v = mk_trim($v);
    return $v !== '' ? $v : $default;
  }
}

/* ---------------------------------------------------------
 * Optional: HTML sanitizer for contributor bios
 * --------------------------------------------------------- */

if (!function_exists('mk_sanitize_bio_html')) {
  function mk_sanitize_bio_html(string $html): string {
    $html = trim($html);
    if ($html === '') return '';

    $html = preg_replace('~<(script|style|iframe|object|embed|link|meta)\b[^>]*>.*?</\1>~is', '', $html) ?? $html;
    $html = preg_replace('~<(script|style|iframe|object|embed|link|meta)\b[^>]*/?>~is', '', $html) ?? $html;

    $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><blockquote><code><pre><a><h3><h4><h5><h6>';
    $html = strip_tags($html, $allowed);

    $html = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*\1/i', 'href="#"', $html) ?? $html;

    return trim($html);
  }
}

/* COMPAT: shared loader fallback */
function mk_require_shared(string $file): void
{
    if (!defined('APP_ROOT')) {
        throw new RuntimeException('APP_ROOT not defined');
    }

    $candidates = [

        APP_ROOT . '/app/mkomigbo/private/shared/' . ltrim($file, '/\\'),

        APP_ROOT . '/app/mkomigbo/private/functions/' . ltrim($file, '/\\'),

    ];

    foreach ($candidates as $path) {

        if (is_file($path)) {
            require_once $path;
            return;
        }
    }

    throw new RuntimeException(
        'Missing shared file: ' . $file
    );
}