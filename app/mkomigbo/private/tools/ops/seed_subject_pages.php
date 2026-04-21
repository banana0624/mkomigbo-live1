<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/tools/ops/seed_subject_pages.php
 *
 * Seeds missing pages for subjects (overview/intro/etc.) in a schema-tolerant way.
 *
 * Usage:
 *   php seed_subject_pages.php
 *   php seed_subject_pages.php --dry-run
 *   php seed_subject_pages.php --subject=history
 *   php seed_subject_pages.php --only=overview,intro
 */

require_once __DIR__ . '/../../assets/initialize.php';

$pdo = db();
if (!$pdo instanceof PDO) {
  fwrite(STDERR, "DB not available.\n");
  exit(1);
}

$argv = $_SERVER['argv'] ?? [];
$dryRun = in_array('--dry-run', $argv, true);

$subjectFilter = '';
$onlyCsv = '';

foreach ($argv as $a) {
  if (strpos($a, '--subject=') === 0) $subjectFilter = trim(substr($a, 10));
  if (strpos($a, '--only=') === 0)    $onlyCsv = trim(substr($a, 7));
}

$only = [];
if ($onlyCsv !== '') {
  foreach (explode(',', $onlyCsv) as $x) {
    $x = strtolower(trim($x));
    if ($x !== '') $only[$x] = true;
  }
}

/** column exists? */
$has = static function(string $table, string $col) use ($pdo): bool {
  static $cache = [];
  $k = strtolower($table.'.'.$col);
  if (array_key_exists($k, $cache)) return (bool)$cache[$k];

  $st = $pdo->prepare("
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = ?
      AND COLUMN_NAME = ?
    LIMIT 1
  ");
  $st->execute([$table, $col]);
  $cache[$k] = (bool)$st->fetchColumn();
  return (bool)$cache[$k];
};

/** choose pages FK col */
$fk = $has('pages','subject_id') ? 'subject_id' : ($has('pages','subjects_id') ? 'subjects_id' : '');
if ($fk === '') {
  fwrite(STDERR, "pages table missing subject_id/subjects_id.\n");
  exit(1);
}

/** choose content col (optional seed text) */
$contentCol = '';
foreach (['body_html','body','content'] as $c) {
  if ($has('pages', $c)) { $contentCol = $c; break; }
}

/** title cols */
$hasTitle    = $has('pages','title');
$hasMenuName = $has('pages','menu_name');

/** flags */
$hasIsPublic = $has('pages','is_public');
$hasVisible  = $has('pages','visible');

/** ordering */
$hasNavOrder = $has('pages','nav_order');

/**
 * Template slugs you want everywhere.
 * Adjust freely later.
 */
$seed = [
  ['slug' => 'overview', 'title' => 'Overview', 'order' => 1,  'body' => 'Coming soon.'],
  ['slug' => 'intro',    'title' => 'Intro',    'order' => 2,  'body' => 'Coming soon.'],
];

if (!empty($only)) {
  $seed = array_values(array_filter($seed, static function($row) use ($only) {
    return isset($only[(string)$row['slug']]);
  }));
}

/** load subjects */
$sqlSubjects = "SELECT id, slug FROM subjects";
$args = [];
if ($subjectFilter !== '') {
  $sqlSubjects .= " WHERE slug = ?";
  $args[] = strtolower($subjectFilter);
}
$sqlSubjects .= " ORDER BY id ASC";

$stS = $pdo->prepare($sqlSubjects);
$stS->execute($args);
$subjects = $stS->fetchAll(PDO::FETCH_ASSOC) ?: [];

if (!$subjects) {
  echo "No subjects matched.\n";
  exit(0);
}

$report = [
  'subjects' => 0,
  'created'  => 0,
  'skipped'  => 0,
];

foreach ($subjects as $sub) {
  $sid = (int)($sub['id'] ?? 0);
  $sslug = (string)($sub['slug'] ?? '');
  if ($sid <= 0 || $sslug === '') continue;

  $report['subjects']++;

  /** existing page slugs for this subject */
  $stE = $pdo->prepare("SELECT slug FROM pages WHERE {$fk} = ?");
  $stE->execute([$sid]);
  $existing = [];
  foreach (($stE->fetchAll(PDO::FETCH_COLUMN, 0) ?: []) as $s) {
    $s = strtolower(trim((string)$s));
    if ($s !== '') $existing[$s] = true;
  }

  foreach ($seed as $row) {
    $pslug  = (string)$row['slug'];
    $ptitle = (string)$row['title'];
    $porder = (int)$row['order'];
    $pbody  = (string)$row['body'];

    if (isset($existing[$pslug])) {
      $report['skipped']++;
      echo "[SKIP] {$sslug}/{$pslug} exists\n";
      continue;
    }

    $cols = ['slug', $fk];
    $vals = [':slug', ':sid'];
    $p = [':slug' => $pslug, ':sid' => $sid];

    if ($hasTitle) {
      $cols[] = 'title'; $vals[] = ':title'; $p[':title'] = $ptitle;
    }
    if ($hasMenuName) {
      $cols[] = 'menu_name'; $vals[] = ':menu_name'; $p[':menu_name'] = $ptitle;
    }
    if ($hasNavOrder) {
      $cols[] = 'nav_order'; $vals[] = ':nav_order'; $p[':nav_order'] = $porder;
    }
    if ($hasIsPublic) {
      $cols[] = 'is_public'; $vals[] = ':is_public'; $p[':is_public'] = 1;
    }
    if ($hasVisible) {
      $cols[] = 'visible'; $vals[] = ':visible'; $p[':visible'] = 1;
    }
    if ($contentCol !== '') {
      $cols[] = $contentCol; $vals[] = ':body'; $p[':body'] = $pbody;
    }

    $sqlI = "INSERT INTO pages (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
    if ($dryRun) {
      echo "[DRY]  {$sslug}/{$pslug} would insert\n";
      $report['created']++;
      continue;
    }

    $stI = $pdo->prepare($sqlI);
    $stI->execute($p);
    $newId = (string)$pdo->lastInsertId();

    echo "[OK]   {$sslug}/{$pslug} inserted id={$newId}\n";
    $report['created']++;
  }
}

echo "\nDone.\n";
echo "Subjects: {$report['subjects']}\n";
echo "Created:  {$report['created']}\n";
echo "Skipped:  {$report['skipped']}\n";
