<?php
declare(strict_types=1);

/**
 * /private/registry/subjects_register.php
 *
 * Static seed/registry for Subjects (fallback/reference only).
 * Used for:
 * - SEO fallbacks (when DB is unavailable / before DB is ready)
 * - Routing fallbacks (subject slug → id/name/meta)
 * - Public index fallback (guarantee 19 subjects exist even with empty DB)
 *
 * SAFETY:
 * - MUST NOT collide with DB/bootstrap helpers.
 * - Keep functions guarded with function_exists().
 */

if (defined('MK_REGISTRY_SUBJECTS_LOADED')) {
  return;
}
define('MK_REGISTRY_SUBJECTS_LOADED', true);

/**
 * Registry data (keyed by numeric id).
 *
 * @var array<int,array<string,mixed>>
 */
$SUBJECTS_REGISTRY = [
  1  => ['id'=>1,  'name'=>'History',      'slug'=>'history',      'nav_order'=>1,  'description'=>'Pages related to history.',         'meta_keywords'=>'history, past, heritage, records',        'icon'=>'/lib/images/subjects/history.svg',      'status'=>'active'],
  2  => ['id'=>2,  'name'=>'Slavery',      'slug'=>'slavery',      'nav_order'=>2,  'description'=>'Pages related to slavery.',         'meta_keywords'=>'slavery, trade, bondage, history',        'icon'=>'/lib/images/subjects/slavery.svg',      'status'=>'active'],
  3  => ['id'=>3,  'name'=>'People',       'slug'=>'people',       'nav_order'=>3,  'description'=>'Pages related to people.',          'meta_keywords'=>'people, community, individuals',          'icon'=>'/lib/images/subjects/people.svg',       'status'=>'active'],
  4  => ['id'=>4,  'name'=>'Persons',      'slug'=>'persons',      'nav_order'=>4,  'description'=>'Pages related to persons.',         'meta_keywords'=>'persons, individuals, biographies',       'icon'=>'/lib/images/subjects/persons.svg',      'status'=>'active'],
  5  => ['id'=>5,  'name'=>'Culture',      'slug'=>'culture',      'nav_order'=>5,  'description'=>'Pages related to culture.',         'meta_keywords'=>'culture, lifestyle, arts, heritage',      'icon'=>'/lib/images/subjects/culture.svg',      'status'=>'active'],
  6  => ['id'=>6,  'name'=>'Religion',     'slug'=>'religion',     'nav_order'=>6,  'description'=>'Pages related to religion.',        'meta_keywords'=>'religion, faith, worship, belief',        'icon'=>'/lib/images/subjects/religion.svg',     'status'=>'active'],
  7  => ['id'=>7,  'name'=>'Spirituality', 'slug'=>'spirituality', 'nav_order'=>7,  'description'=>'Pages related to spirituality.',    'meta_keywords'=>'spirituality, meditation, faith, soul',   'icon'=>'/lib/images/subjects/spirituality.svg', 'status'=>'active'],
  8  => ['id'=>8,  'name'=>'Tradition',    'slug'=>'tradition',    'nav_order'=>8,  'description'=>'Pages related to tradition.',       'meta_keywords'=>'tradition, customs, practices, heritage', 'icon'=>'/lib/images/subjects/tradition.svg',    'status'=>'active'],
  9  => ['id'=>9,  'name'=>'Language1',    'slug'=>'language1',    'nav_order'=>9,  'description'=>'Pages related to first language.',  'meta_keywords'=>'language, communication, dialect',        'icon'=>'/lib/images/subjects/language1.svg',    'status'=>'active'],
  10 => ['id'=>10, 'name'=>'Language2',    'slug'=>'language2',    'nav_order'=>10, 'description'=>'Pages related to second language.', 'meta_keywords'=>'language, communication, dialect',        'icon'=>'/lib/images/subjects/language2.svg',    'status'=>'active'],
  11 => ['id'=>11, 'name'=>'Struggles',    'slug'=>'struggles',    'nav_order'=>11, 'description'=>'Pages related to struggles.',       'meta_keywords'=>'struggles, resistance, survival',         'icon'=>'/lib/images/subjects/struggles.svg',    'status'=>'active'],
  12 => ['id'=>12, 'name'=>'Biafra',       'slug'=>'biafra',       'nav_order'=>12, 'description'=>'Pages related to Biafra.',          'meta_keywords'=>'biafra, war, independence, nigeria',      'icon'=>'/lib/images/subjects/biafra.svg',       'status'=>'active'],
  13 => ['id'=>13, 'name'=>'Nigeria',      'slug'=>'nigeria',      'nav_order'=>13, 'description'=>'Pages related to Nigeria.',         'meta_keywords'=>'nigeria, nation, politics, history',      'icon'=>'/lib/images/subjects/nigeria.svg',      'status'=>'active'],
  14 => ['id'=>14, 'name'=>'Resistance',   'slug'=>'resistance',   'nav_order'=>14, 'description'=>'Pages related to resistance.',      'meta_keywords'=>'resistance, movement, activism',          'icon'=>'/lib/images/subjects/ipob.svg',         'status'=>'active'],
  15 => ['id'=>15, 'name'=>'Africa',       'slug'=>'africa',       'nav_order'=>15, 'description'=>'Pages related to Africa.',          'meta_keywords'=>'africa, continent, heritage, nations',    'icon'=>'/lib/images/subjects/africa.svg',       'status'=>'active'],
  16 => ['id'=>16, 'name'=>'UK',           'slug'=>'uk',           'nav_order'=>16, 'description'=>'Pages related to the UK.',          'meta_keywords'=>'uk, britain, england, london',            'icon'=>'/lib/images/subjects/uk.svg',           'status'=>'active'],
  17 => ['id'=>17, 'name'=>'Europe',       'slug'=>'europe',       'nav_order'=>17, 'description'=>'Pages related to Europe.',          'meta_keywords'=>'europe, continent, nations, history',     'icon'=>'/lib/images/subjects/europe.svg',       'status'=>'active'],
  18 => ['id'=>18, 'name'=>'Arabs',        'slug'=>'arabs',        'nav_order'=>18, 'description'=>'Pages related to Arabs.',           'meta_keywords'=>'arabs, middle east, culture, history',    'icon'=>'/lib/images/subjects/arabs.svg',        'status'=>'active'],
  19 => ['id'=>19, 'name'=>'About',        'slug'=>'about',        'nav_order'=>19, 'description'=>'About this website.',               'meta_keywords'=>'about, information, project, overview',   'icon'=>'/lib/images/subjects/about.svg',        'status'=>'active'],
];

/* --------------------------------------------------------------------------
 * Internal: normalize rows defensively
 * -------------------------------------------------------------------------- */
if (!function_exists('mk__subjects_registry_normalize')) {
  /**
   * @param array<int,array<string,mixed>> $rows
   * @return array<int,array<string,mixed>>
   */
  function mk__subjects_registry_normalize(array $rows): array {
    $out = [];

    foreach ($rows as $k => $row) {
      if (!is_array($row)) continue;

      $id   = isset($row['id']) ? (int)$row['id'] : (int)$k;
      $slug = isset($row['slug']) ? trim((string)$row['slug']) : '';
      $name = isset($row['name']) ? trim((string)$row['name']) : '';

      if ($id <= 0 || $slug === '' || $name === '') continue;

      $out[$id] = [
        'id'            => $id,
        'slug'          => $slug,
        'name'          => $name,
        'nav_order'     => isset($row['nav_order']) ? (int)$row['nav_order'] : PHP_INT_MAX,
        'description'   => isset($row['description']) ? (string)$row['description'] : (string)($row['meta_description'] ?? ''),
        'meta_keywords' => isset($row['meta_keywords']) ? (string)$row['meta_keywords'] : '',
        'icon'          => isset($row['icon']) ? (string)$row['icon'] : '',
        'status'        => isset($row['status']) ? (string)$row['status'] : 'active',
      ];
    }

    return $out;
  }
}

/**
 * Internal: optional validator (no output; safe to ignore)
 */
if (!function_exists('mk__subjects_registry_assert_valid')) {
  function mk__subjects_registry_assert_valid(array $rows): void {
    $seenOrder = [];
    foreach ($rows as $r) {
      if (!is_array($r)) continue;
      $id = (int)($r['id'] ?? 0);
      $o  = (int)($r['nav_order'] ?? 0);
      if ($id < 1 || $o < 1) continue;

      if (isset($seenOrder[$o])) {
        // keep silent in production by default
        // error_log("Duplicate subjects nav_order={$o} for ids {$seenOrder[$o]} and {$id}");
      } else {
        $seenOrder[$o] = $id;
      }
    }
  }
}

/* --------------------------------------------------------------------------
 * Public accessors
 * -------------------------------------------------------------------------- */
if (!function_exists('subjects_all_registry')) {
  function subjects_all_registry(): array {
    global $SUBJECTS_REGISTRY;
    $rows = is_array($SUBJECTS_REGISTRY ?? null) ? $SUBJECTS_REGISTRY : [];
    $norm = mk__subjects_registry_normalize($rows);

    // optional silent validator (no runtime impact)
    mk__subjects_registry_assert_valid($norm);

    return $norm;
  }
}

if (!function_exists('subject_by_id_registry')) {
  function subject_by_id_registry(int $id): ?array {
    $all = subjects_all_registry();
    return $all[$id] ?? null;
  }
}

if (!function_exists('subject_by_slug_registry')) {
  function subject_by_slug_registry(string $slug): ?array {
    $slug = trim($slug);
    if ($slug === '') return null;

    foreach (subjects_all_registry() as $row) {
      if (($row['slug'] ?? '') === $slug) return $row;
    }
    return null;
  }
}

if (!function_exists('subjects_sorted_registry')) {
  function subjects_sorted_registry(): array {
    $all = subjects_all_registry();

    uasort($all, static function ($a, $b): int {
      $na = is_array($a) ? (int)($a['nav_order'] ?? PHP_INT_MAX) : PHP_INT_MAX;
      $nb = is_array($b) ? (int)($b['nav_order'] ?? PHP_INT_MAX) : PHP_INT_MAX;

      if ($na !== $nb) return $na <=> $nb;

      // Stable tie-breaker: ID (NOT name). Guarantees “no change” ordering.
      $ia = is_array($a) ? (int)($a['id'] ?? PHP_INT_MAX) : PHP_INT_MAX;
      $ib = is_array($b) ? (int)($b['id'] ?? PHP_INT_MAX) : PHP_INT_MAX;
      return $ia <=> $ib;
    });

    return $all;
  }
}

/* --------------------------------------------------------------------------
 * SEO fallback helper (used by seo_helpers.php)
 * -------------------------------------------------------------------------- */
if (!function_exists('mk_subject_meta_fallback')) {
  /**
   * Return meta fallbacks for a subject slug using registry.
   *
   * @return array{meta_description:string, meta_keywords:string, icon:string, name?:string}
   */
  function mk_subject_meta_fallback(string $slug): array
  {
    $slug = trim($slug);
    if ($slug === '') {
      return ['meta_description' => '', 'meta_keywords' => '', 'icon' => ''];
    }

    // Ensure registry file is loaded (this file is the registry, so usually already true)
    if (!function_exists('subject_by_slug_registry')) {
      return ['meta_description' => '', 'meta_keywords' => '', 'icon' => ''];
    }

    $row = subject_by_slug_registry($slug);
    if (!is_array($row)) {
      return ['meta_description' => '', 'meta_keywords' => '', 'icon' => ''];
    }

    // Your registry uses 'description' (not 'meta_description')
    $desc = trim((string)($row['description'] ?? ($row['meta_description'] ?? '')));
    $keys = trim((string)($row['meta_keywords'] ?? ''));
    $icon = trim((string)($row['icon'] ?? ''));
    $name = trim((string)($row['name'] ?? ''));

    return [
      'meta_description' => $desc,
      'meta_keywords'    => $keys,
      'icon'             => $icon,
      'name'             => $name,
    ];
  }
}

/**
 * Merge DB subject row (partial) into registry row (fallback base).
 * DB wins for: name, description, nav_order (if non-null).
 * If DB indicates non-public (status/is_public/visible), caller should filter it out before merge.
 */
if (!function_exists('subject_merge_registry_with_db')) {
  function subject_merge_registry_with_db(array $registryRow, array $dbRow): array {
    $out = $registryRow;

    if (isset($dbRow['id']) && (int)$dbRow['id'] > 0) {
      $out['id'] = (int)$dbRow['id'];
    }

    if (isset($dbRow['slug']) && trim((string)$dbRow['slug']) !== '') {
      $out['slug'] = trim((string)$dbRow['slug']);
    }

    $dbName = isset($dbRow['name']) ? trim((string)$dbRow['name']) : '';
    if ($dbName !== '') $out['name'] = $dbName;

    $dbDesc = isset($dbRow['description']) ? trim((string)$dbRow['description']) : '';
    if ($dbDesc !== '') $out['description'] = $dbDesc;

    if (array_key_exists('nav_order', $dbRow) && $dbRow['nav_order'] !== null && $dbRow['nav_order'] !== '') {
      $out['nav_order'] = (int)$dbRow['nav_order'];
    }

    return $out;
  }
}

/**
 * URL helper (staff-aware by default).
 * - staff=true  => /staff/subjects/{slug}/
 * - staff=false => /subjects/{slug}/
 */
if (!function_exists('subject_url_registry')) {
  function subject_url_registry($subject, bool $staff = true): string {
    $slug = is_array($subject) ? (string)($subject['slug'] ?? '') : (string)$subject;
    $slug = trim($slug);

    $base = $staff ? '/staff/subjects/' : '/subjects/';
    $path = $base . $slug . '/';

    if (function_exists('url_for')) return (string)url_for($path);
    return $path;
  }
}
