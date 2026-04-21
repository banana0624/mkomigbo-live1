<?php
declare(strict_types=1);

/**
 * /private/registry/subjects_runtime.php
 *
 * Canonical Subjects runtime registry (single source of truth).
 *
 * GOAL:
 * - Prevent “arbitrary” subjects from appearing.
 * - Ensure every caller gets the same 19 subjects, in the same order, with stable IDs.
 *
 * SOURCES (in priority order):
 *  1) /private/registry/subjects_register.php (preferred canonical registry)
 *     - supports either:
 *       a) returning an array of rows
 *       b) defining a function that returns rows
 *  2) A “local catalog” fallback ONLY if you explicitly define it below (empty by default)
 *  3) DB overlay (optional): enrich name/description/icon, but NEVER changes canonical order
 *
 * Row shape expected (best-effort):
 *  [
 *    'id' => 1..19,
 *    'slug' => 'history',
 *    'name' => 'History',
 *    'description' => '...',
 *    'icon' or 'icon_path' => '/lib/images/subjects/history.svg',
 *  ]
 */

if (defined('MK_SUBJECTS_RUNTIME_LOADED')) { return; }
define('MK_SUBJECTS_RUNTIME_LOADED', true);

/* ---------------------------------------------------------
   Safe helpers
--------------------------------------------------------- */
if (!function_exists('mk_is_slug')) {
  function mk_is_slug(string $s): bool {
    $s = strtolower(trim($s));
    return $s !== '' && (bool)preg_match('/^[a-z0-9][a-z0-9_-]{0,190}$/', $s);
  }
}

if (!function_exists('mk_subject_ui_code')) {
  function mk_subject_ui_code(int $id): string {
    if ($id <= 0) return '';
    return 'S' . str_pad((string)$id, 2, '0', STR_PAD_LEFT);
  }
}

/* ---------------------------------------------------------
   Locate canonical registry file
--------------------------------------------------------- */
if (!function_exists('mk_subjects_registry_file')) {
  function mk_subjects_registry_file(): string {
    $candidates = [];

    // Most correct in your project layout:
    if (defined('APP_ROOT')) {
      $candidates[] = rtrim((string)APP_ROOT, "/\\") . '/private/registry/subjects_register.php';
      $candidates[] = rtrim((string)APP_ROOT, "/\\") . '/private/registry/subjects_registry.php';
      $candidates[] = rtrim((string)APP_ROOT, "/\\") . '/private/registry/subjects.php';
    }
    if (defined('PRIVATE_PATH')) {
      $candidates[] = rtrim((string)PRIVATE_PATH, "/\\") . '/registry/subjects_register.php';
      $candidates[] = rtrim((string)PRIVATE_PATH, "/\\") . '/registry/subjects_registry.php';
      $candidates[] = rtrim((string)PRIVATE_PATH, "/\\") . '/registry/subjects.php';
    }

    foreach ($candidates as $f) {
      if ($f !== '' && is_file($f) && is_readable($f)) return $f;
    }
    return '';
  }
}

/* ---------------------------------------------------------
   Canonical catalog fallback (INTENTIONALLY EMPTY)
   If the canonical registry file is missing, you can fill this.
   But by default we refuse to invent subjects.
--------------------------------------------------------- */
if (!function_exists('mk_subjects_catalog_fallback')) {
  /**
   * @return array<int,array<string,mixed>> rows with id/slug/name...
   */
  function mk_subjects_catalog_fallback(): array {
    return []; // <-- do NOT invent arbitrary subjects
  }
}

/* ---------------------------------------------------------
   Load raw registry rows from canonical registry
--------------------------------------------------------- */
if (!function_exists('mk_subjects_registry_raw')) {
  /**
   * @return array<int,array<string,mixed>>
   */
  function mk_subjects_registry_raw(): array {
    // If another registry function already exists, prefer it.
    foreach ([
      'mk_subjects_registry_sorted',
      'mk_subjects_registry',
      'subjects_sorted',
      'subjects_registry_sorted',
      'subjects_registry',
      'subjects_all',
    ] as $fn) {
      if (function_exists($fn)) {
        try {
          $rows = $fn();
          if (is_array($rows)) return array_values($rows);
        } catch (Throwable $e) {}
      }
    }

    $file = mk_subjects_registry_file();
    if ($file !== '') {
      try {
        $ret = require $file;

        // common patterns: return [ ... ]
        if (is_array($ret)) return array_values($ret);

        // or file defines functions (try again)
        foreach ([
          'mk_subjects_registry_sorted',
          'mk_subjects_registry',
          'subjects_sorted',
          'subjects_registry_sorted',
          'subjects_registry',
          'subjects_all',
        ] as $fn) {
          if (function_exists($fn)) {
            try {
              $rows = $fn();
              if (is_array($rows)) return array_values($rows);
            } catch (Throwable $e) {}
          }
        }
      } catch (Throwable $e) {
        // fall through
      }
    }

    // Last resort: explicit local fallback (empty unless you fill it)
    return mk_subjects_catalog_fallback();
  }
}

/* ---------------------------------------------------------
   Normalize + validate registry rows
--------------------------------------------------------- */
if (!function_exists('mk_subjects_registry_normalize')) {
  /**
   * @param array<int,mixed> $rows
   * @return array<int,array<string,mixed>>
   */
  function mk_subjects_registry_normalize(array $rows): array {
    $out = [];
    foreach ($rows as $r) {
      if (!is_array($r)) continue;

      $id   = (int)($r['id'] ?? $r['registry_id'] ?? 0);
      $slug = strtolower(trim((string)($r['slug'] ?? '')));
      if ($id <= 0) continue;
      if (!mk_is_slug($slug)) continue;

      $name = trim((string)($r['name'] ?? $r['menu_name'] ?? $r['subject_name'] ?? ''));
      if ($name === '') $name = $slug;

      $desc = '';
      foreach (['description','meta_description','short_desc','content'] as $k) {
        if (isset($r[$k]) && is_string($r[$k]) && trim($r[$k]) !== '') { $desc = trim($r[$k]); break; }
      }

      $icon = '';
      foreach (['icon','icon_path','logo','logo_path'] as $k) {
        if (isset($r[$k]) && is_string($r[$k]) && trim($r[$k]) !== '') { $icon = trim($r[$k]); break; }
      }

      $out[] = [
        'id'          => $id,
        'slug'        => $slug,
        'name'        => $name,
        'description' => $desc,
        'icon'        => $icon,
      ];
    }

    // Enforce canonical order by id ascending, and keep only unique IDs
    usort($out, static fn($a, $b) => ((int)$a['id'] <=> (int)$b['id']));
    $seen = [];
    $final = [];
    foreach ($out as $row) {
      $id = (int)$row['id'];
      if ($id <= 0 || isset($seen[$id])) continue;
      $seen[$id] = true;
      $final[] = $row;
    }

    return $final;
  }
}

/* ---------------------------------------------------------
   Optional DB overlay (enrich only; never reorders / never adds arbitrary)
--------------------------------------------------------- */
if (!function_exists('mk_subjects_db_overlay')) {
  /**
   * @param array<int,array<string,mixed>> $registry
   * @return array<int,array<string,mixed>>
   */
  function mk_subjects_db_overlay(array $registry): array {
    if (!$registry) return $registry;

    $pdo = null;
    try { if (function_exists('db')) { $pdo = db(); } } catch (Throwable $e) {}
    if (!$pdo instanceof PDO) return $registry;

    // Build slug->index map
    $idx = [];
    $slugs = [];
    foreach ($registry as $i => $r) {
      $s = (string)($r['slug'] ?? '');
      if ($s !== '' && mk_is_slug($s)) { $idx[$s] = $i; $slugs[] = $s; }
    }
    if (!$slugs) return $registry;

    // Schema-tolerant column picks
    $nameCols = ['name','menu_name','subject_name'];
    $descCols = ['meta_description','short_desc','description','content'];
    $iconCols = ['icon_path'];

    $pickCol = static function(PDO $pdo, string $table, array $cands): ?string {
      foreach ($cands as $c) {
        try {
          $st = $pdo->prepare("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1
          ");
          $st->execute([$table, $c]);
          if ((bool)$st->fetchColumn()) return $c;
        } catch (Throwable $e) {}
      }
      return null;
    };

    $nameCol = $pickCol($pdo, 'subjects', $nameCols);
    $descCol = $pickCol($pdo, 'subjects', $descCols);
    $iconCol = $pickCol($pdo, 'subjects', $iconCols);

    // Fetch by slug set
    $ph = implode(',', array_fill(0, count($slugs), '?'));
    $cols = ['slug'];
    if ($nameCol) $cols[] = "{$nameCol} AS name";
    if ($descCol) $cols[] = "{$descCol} AS description";
    if ($iconCol) $cols[] = "{$iconCol} AS icon_path";

    try {
      $st = $pdo->prepare("SELECT " . implode(', ', $cols) . " FROM subjects WHERE slug IN ({$ph})");
      $st->execute($slugs);
      $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

      foreach ($rows as $r) {
        $slug = strtolower(trim((string)($r['slug'] ?? '')));
        if (!isset($idx[$slug])) continue;

        $i = (int)$idx[$slug];

        $nm = trim((string)($r['name'] ?? ''));
        if ($nm !== '') $registry[$i]['name'] = $nm;

        $ds = trim((string)($r['description'] ?? ''));
        if ($ds !== '') $registry[$i]['description'] = $ds;

        $ic = trim((string)($r['icon_path'] ?? ''));
        if ($ic !== '') $registry[$i]['icon'] = $ic;
      }
    } catch (Throwable $e) {
      // ignore overlay failures
    }

    return $registry;
  }
}

/* ---------------------------------------------------------
   Public API: sorted registry (THE function other code should call)
--------------------------------------------------------- */
if (!function_exists('mk_subjects_registry_sorted')) {
  /**
   * @return array<int,array<string,mixed>>
   */
  function mk_subjects_registry_sorted(bool $with_db_overlay = true): array {
    static $cache = null;
    static $cache_overlay = null;

    if ($with_db_overlay && is_array($cache_overlay)) return $cache_overlay;
    if (!$with_db_overlay && is_array($cache)) return $cache;

    $raw = mk_subjects_registry_raw();
    $norm = mk_subjects_registry_normalize(is_array($raw) ? $raw : []);

    // Safety: refuse to output “random” sets
    // If your canonical registry is missing, this will be empty until you add it.
    if ($with_db_overlay) {
      $cache_overlay = mk_subjects_db_overlay($norm);
      return $cache_overlay;
    }

    $cache = $norm;
    return $cache;
  }
}

if (!function_exists('mk_subjects_registry_by_slug')) {
  /** @return array<string,mixed>|null */
  function mk_subjects_registry_by_slug(string $slug): ?array {
    $slug = strtolower(trim($slug));
    if (!mk_is_slug($slug)) return null;
    foreach (mk_subjects_registry_sorted(false) as $r) {
      if (strcasecmp((string)($r['slug'] ?? ''), $slug) === 0) return $r;
    }
    return null;
  }
}

if (!function_exists('mk_subjects_registry_by_id')) {
  /** @return array<string,mixed>|null */
  function mk_subjects_registry_by_id(int $id): ?array {
    if ($id <= 0) return null;
    foreach (mk_subjects_registry_sorted(false) as $r) {
      if ((int)($r['id'] ?? 0) === $id) return $r;
    }
    return null;
  }
}

/* ---------------------------------------------------------
   Logo helper (web path) with sane fallbacks
--------------------------------------------------------- */
if (!function_exists('mk_subject_logo_webpath')) {
  function mk_subject_logo_webpath(string $slug): string {
    $slug = strtolower(trim($slug));
    if (!mk_is_slug($slug)) return '/lib/images/subjects/_subject.svg';

    $r = mk_subjects_registry_by_slug($slug);
    $icon = is_array($r) ? trim((string)($r['icon'] ?? '')) : '';
    if ($icon !== '') {
      $web = '/' . ltrim($icon, '/');
      return $web;
    }

    // Conventional locations
    $folders = ['/lib/images/subjects', '/lib/images/logo'];
    $exts = ['.svg','.png','.jpg','.jpeg','.webp'];
    $public = defined('PUBLIC_PATH') ? rtrim((string)PUBLIC_PATH, "/\\") : '';

    foreach ($folders as $dirWeb) {
      foreach ($exts as $ext) {
        $web = $dirWeb . '/' . $slug . $ext;
        if ($public !== '') {
          $fs = $public . str_replace('/', DIRECTORY_SEPARATOR, $web);
          if (is_file($fs)) return $web;
        } else {
          // if PUBLIC_PATH not defined, still return the conventional path
          return $web;
        }
      }
    }

    return '/lib/images/subjects/_subject.svg';
  }
}
