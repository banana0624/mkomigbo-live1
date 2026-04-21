<?php
declare(strict_types=1);

/**
 * Create subject_aliases table with correct FK (schema-driven).
 * Also seeds: united-kingdom -> uk
 */

require_once __DIR__ . '/../../assets/initialize.php';

if (!function_exists('db')) {
  fwrite(STDERR, "db() not available.\n");
  exit(1);
}

$pdo = db();
if (!$pdo instanceof PDO) {
  fwrite(STDERR, "DB connection not available.\n");
  exit(1);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "== Inspect subjects schema ==\n";

$subjects = $pdo->query("SHOW TABLE STATUS LIKE 'subjects'")->fetch(PDO::FETCH_ASSOC) ?: [];
$engine = (string)($subjects['Engine'] ?? '');
echo "subjects.Engine = {$engine}\n";

$idCol = $pdo->query("SHOW FULL COLUMNS FROM subjects LIKE 'id'")->fetch(PDO::FETCH_ASSOC) ?: [];
$type  = strtolower((string)($idCol['Type'] ?? ''));
$coll  = (string)($idCol['Collation'] ?? '');

if ($type === '') {
  fwrite(STDERR, "Could not read subjects.id column type.\n");
  exit(1);
}

echo "subjects.id.Type = {$type}\n";
if ($coll !== '') echo "subjects.id.Collation = {$coll}\n";

/**
 * Build subject_id column definition to match subjects.id
 * Example subjects.id types you might have:
 * - int(11) unsigned
 * - bigint(20) unsigned
 * - int(10)
 */
$unsigned = (strpos($type, 'unsigned') !== false);
$baseType = trim(str_replace('unsigned', '', $type));
$baseType = preg_replace('/\s+/', ' ', $baseType) ?: $baseType;

$subjectIdDef = strtoupper($baseType) . ($unsigned ? ' UNSIGNED' : '');

echo "Will use subject_id = {$subjectIdDef}\n";

echo "\n== Create subject_aliases (idempotent) ==\n";

$pdo->exec("
CREATE TABLE IF NOT EXISTS subject_aliases (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  subject_id {$subjectIdDef} NOT NULL,
  alias_slug VARCHAR(190) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_alias_slug (alias_slug),
  KEY idx_subject_id (subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "Table created.\n";

/* Try to add FK if possible (non-fatal if it fails) */
echo "\n== Add FK (best effort) ==\n";
try {
  // if fk already exists, this will fail; ignore
  $pdo->exec("
    ALTER TABLE subject_aliases
    ADD CONSTRAINT fk_subject_aliases_subject
      FOREIGN KEY (subject_id) REFERENCES subjects(id)
      ON DELETE CASCADE
  ");
  echo "FK added.\n";
} catch (Throwable $e) {
  echo "FK not added (ok). Reason: " . $e->getMessage() . "\n";
}

/* Seed alias united-kingdom -> uk */
echo "\n== Seed alias united-kingdom -> uk ==\n";

$st = $pdo->prepare("SELECT id FROM subjects WHERE slug = 'uk' LIMIT 1");
$st->execute();
$sid = (int)($st->fetchColumn() ?: 0);

if ($sid <= 0) {
  fwrite(STDERR, "Subject slug 'uk' not found in subjects.\n");
  exit(1);
}

$ins = $pdo->prepare("INSERT IGNORE INTO subject_aliases (subject_id, alias_slug) VALUES (?, ?)");
$ins->execute([$sid, 'united-kingdom']);

echo "Seeded.\n";

echo "\nDone.\n";
