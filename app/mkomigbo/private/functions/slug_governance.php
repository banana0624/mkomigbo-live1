<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/functions/slug_governance.php
 *
 * Canonical subject slug governance:
 * - Canonical: subjects.slug
 * - Aliases: subject_aliases.alias_slug -> subjects.slug
 *
 * Goals:
 * - One source of truth for "uk vs united-kingdom vs ..." drift
 * - Safe in production (never fatal if DB/table missing OR caller passes null)
 * - Fast (request-local caches)
 *
 * Expected integration points:
 * - /public/subjects/view.php (router): call mk_subject_apply_subject_alias_redirect()
 * - Optional: /public/subjects/subject.php and /public/subjects/page.php (defense-in-depth)
 */

if (!function_exists('mk_is_slug')) {
  function mk_is_slug(string $s): bool {
    $s = strtolower(trim($s));
    return $s !== '' && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
  }
}

if (!function_exists('mk_subject_governance_pdo')) {
  function mk_subject_governance_pdo(): ?PDO {
    try {
      if (!function_exists('db')) return null;
      $pdo = db();
      return ($pdo instanceof PDO) ? $pdo : null;
    } catch (Throwable $e) {
      return null;
    }
  }
}

if (!function_exists('mk_db_table_exists')) {
  function mk_db_table_exists(PDO $pdo, string $table): bool {
    static $cache = [];
    $key = strtolower($table);
    if (array_key_exists($key, $cache)) return (bool)$cache[$key];

    try {
      $st = $pdo->prepare("
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
      ");
      $st->execute([$table]);
      $cache[$key] = (bool)$st->fetchColumn();
      return (bool)$cache[$key];
    } catch (Throwable $e) {
      $cache[$key] = false;
      return false;
    }
  }
}

if (!function_exists('mk_subject_alias_target')) {
  /**
   * If $aliasSlug is an alias, return canonical subject slug. Otherwise ''.
   */
  function mk_subject_alias_target(string $aliasSlug): string {
    static $cache = []; // alias => canonical|''

    $aliasSlug = strtolower(trim($aliasSlug));
    if (!mk_is_slug($aliasSlug)) return '';

    if (array_key_exists($aliasSlug, $cache)) return (string)$cache[$aliasSlug];

    $pdo = mk_subject_governance_pdo();
    if (!$pdo) return $cache[$aliasSlug] = '';

    try {
      if (!mk_db_table_exists($pdo, 'subject_aliases') || !mk_db_table_exists($pdo, 'subjects')) {
        return $cache[$aliasSlug] = '';
      }

      $st = $pdo->prepare("
        SELECT s.slug
        FROM subject_aliases sa
        JOIN subjects s ON s.id = sa.subject_id
        WHERE sa.alias_slug = ?
        LIMIT 1
      ");
      $st->execute([$aliasSlug]);
      $canon = (string)($st->fetchColumn() ?: '');
      $canon = strtolower(trim($canon));

      if (!mk_is_slug($canon)) $canon = '';
      return $cache[$aliasSlug] = $canon;

    } catch (Throwable $e) {
      return $cache[$aliasSlug] = '';
    }
  }
}

if (!function_exists('mk_subject_exists_canonical')) {
  /**
   * True if slug exists in subjects.slug.
   */
  function mk_subject_exists_canonical(string $subjectSlug): bool {
    static $cache = []; // slug => bool

    $subjectSlug = strtolower(trim($subjectSlug));
    if (!mk_is_slug($subjectSlug)) return false;

    if (array_key_exists($subjectSlug, $cache)) return (bool)$cache[$subjectSlug];

    $pdo = mk_subject_governance_pdo();
    if (!$pdo) return $cache[$subjectSlug] = false;

    try {
      if (!mk_db_table_exists($pdo, 'subjects')) return $cache[$subjectSlug] = false;

      $st = $pdo->prepare("SELECT 1 FROM subjects WHERE slug = ? LIMIT 1");
      $st->execute([$subjectSlug]);
      return $cache[$subjectSlug] = (bool)$st->fetchColumn();

    } catch (Throwable $e) {
      return $cache[$subjectSlug] = false;
    }
  }
}

if (!function_exists('mk_subject_canonical_slug')) {
  /**
   * Returns canonical subject slug:
   * - if subject slug exists in subjects: return it
   * - else if slug is an alias: return canonical slug
   * - else return original slug (unknown)
   */
  function mk_subject_canonical_slug(?string $subjectSlug): string {
    $subjectSlug = strtolower(trim((string)($subjectSlug ?? '')));
    if (!mk_is_slug($subjectSlug)) return $subjectSlug;

    if (mk_subject_exists_canonical($subjectSlug)) return $subjectSlug;

    $canon = mk_subject_alias_target($subjectSlug);
    return ($canon !== '') ? $canon : $subjectSlug;
  }
}

if (!function_exists('mk_subject_apply_subject_alias_redirect')) {
  /**
   * Redirect to canonical subject if current is an alias.
   *
   * $pageSlug optional: if present, keeps it:
   *   /subjects/{alias}/{page}/  -> /subjects/{canon}/{page}/
   *
   * Best used inside /public/subjects/view.php AFTER parsing slugs and BEFORE routing.
   */
  function mk_subject_apply_subject_alias_redirect(
    ?string $subjectSlug,
    ?string $pageSlug = '',
    int $code = 301,
    string $basePath = '/subjects/'
  ): void {
    $subjectSlug = strtolower(trim((string)($subjectSlug ?? '')));
    $pageSlug    = strtolower(trim((string)($pageSlug ?? '')));

    if (!mk_is_slug($subjectSlug)) return;
    if ($pageSlug !== '' && !mk_is_slug($pageSlug)) return;

    // Only redirect when slug is NOT canonical but IS an alias
    if (mk_subject_exists_canonical($subjectSlug)) return;

    $canon = mk_subject_alias_target($subjectSlug);
    if ($canon === '' || $canon === $subjectSlug) return;

    $dest = rtrim($basePath, '/') . '/' . rawurlencode($canon) . '/';
    if ($pageSlug !== '') $dest .= rawurlencode($pageSlug) . '/';

    // Preserve url_for() if available
    if (function_exists('url_for')) {
      try { $dest = (string)url_for($dest); } catch (Throwable $e) {}
    }

    $dest = str_replace(["\r", "\n"], '', $dest);
    header('Location: ' . $dest, true, $code);
    exit;
  }
}

if (!function_exists('mk_subject_redirect_if_alias')) {
  /**
   * Back-compat shim (some controllers call this older helper).
   *
   * Keeps optional $tail (e.g. media/download segments) if provided.
   */
  function mk_subject_redirect_if_alias(
    ?string $subjectSlug,
    ?string $pageSlug = '',
    ?string $tail = '',
    int $code = 301,
    string $basePath = '/subjects/'
  ): void {
    $subjectSlug = strtolower(trim((string)($subjectSlug ?? '')));
    $pageSlug    = strtolower(trim((string)($pageSlug ?? '')));
    $tail        = trim((string)($tail ?? ''), '/');

    if (!mk_is_slug($subjectSlug)) return;
    if ($pageSlug !== '' && !mk_is_slug($pageSlug)) return;

    if (mk_subject_exists_canonical($subjectSlug)) return;

    $canon = mk_subject_alias_target($subjectSlug);
    if ($canon === '' || $canon === $subjectSlug) return;

    $dest = rtrim($basePath, '/') . '/' . rawurlencode($canon) . '/';
    if ($pageSlug !== '') $dest .= rawurlencode($pageSlug) . '/';
    if ($tail !== '') $dest .= $tail . '/';

    if (function_exists('url_for')) {
      try { $dest = (string)url_for($dest); } catch (Throwable $e) {}
    }

    $dest = str_replace(["\r", "\n"], '', $dest);
    header('Location: ' . $dest, true, $code);
    exit;
  }
}
