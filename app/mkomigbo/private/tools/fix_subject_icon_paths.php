<?php
declare(strict_types=1);

/**
 * /private/tools/fix_subject_icon_paths.php
 * CLI-safe: updates subjects.icon_path to /lib/images/subjects/{slug}.svg
 * - Prints real DB errors (no silent “internal error” mystery)
 */

@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
  require __DIR__ . '/../assets/initialize.php';

  if (!function_exists('db')) {
    throw new RuntimeException('db() not available (initialize stack incomplete).');
  }

  $pdo = db();
  if (!$pdo instanceof PDO) {
    throw new RuntimeException('DB connection not available.');
  }

  $rows = $pdo->query("SELECT id, slug FROM subjects WHERE slug IS NOT NULL AND slug <> ''")
              ->fetchAll(PDO::FETCH_ASSOC);

  // IMPORTANT FIX: WHERE id = ?
  $upd = $pdo->prepare("UPDATE subjects SET icon_path = ? WHERE id = ? LIMIT 1");

  $n = 0;
  foreach ($rows as $r) {
    $id   = (int)($r['id'] ?? 0);
    $slug = strtolower(trim((string)($r['slug'] ?? '')));
    if ($id <= 0 || $slug === '') continue;

    $rel = '/lib/images/subjects/' . $slug . '.svg';
    $upd->execute([$rel, $id]);
    $n++;
  }

  echo "Updated icon_path for {$n} subjects\n";
  exit(0);

} catch (Throwable $e) {
  // Print the real reason (DB, schema, permissions, etc.)
  fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
  fwrite(STDERR, $e->getTraceAsString() . "\n");
  exit(1);
}
