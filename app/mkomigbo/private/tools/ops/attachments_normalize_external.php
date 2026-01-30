<?php
declare(strict_types=1);

/**
 * /private/tools/ops/attachments_normalize_external.php
 *
 * Owner-only MUTATING cleanup tool.
 *
 * Purpose:
 * - Normalizes legacy external attachments in page_files:
 *   - external_url normalized (e.g. strip www., enforce https, etc.)
 *   - external_host normalized to match validator
 *
 * Safety:
 * - DRY-RUN by default.
 * - Applies only when apply=1 (GET or POST).
 * - Never deletes rows.
 * - Never fetches remote content (no SSRF).
 *
 * UX:
 * - Prints an APPLY URL (optional; runner provides Apply button now).
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!defined('APP_ROOT')) { echo "APP_ROOT missing.\n"; exit; }
if (!function_exists('db')) { echo "db() missing.\n"; exit; }

$pdo = db();
if (!$pdo instanceof PDO) { echo "DB not available.\n"; exit; }

/* Load external validator (authoritative normalization) */
if (!defined('PRIVATE_PATH') || !is_string(PRIVATE_PATH) || PRIVATE_PATH === '') {
  echo "PRIVATE_PATH missing.\n";
  exit;
}

$fn = rtrim(PRIVATE_PATH, "/\\") . '/functions/page_attachments_external.php';
if (!is_file($fn)) { echo "Missing: {$fn}\n"; exit; }
require_once $fn;

if (!function_exists('mk_pagefile__validate_external_url')) {
  echo "mk_pagefile__validate_external_url() missing.\n";
  exit;
}

/* -----------------------------
   Apply gate
----------------------------- */
$applyGet  = (string)($_GET['apply'] ?? '');
$applyPost = (string)($_POST['apply'] ?? '');
$doApply   = ($applyGet === '1' || $applyPost === '1');

/* Optional APPLY URL (still useful for copy/paste / debugging) */
$toolKey  = 'ops/attachments/normalize-external';
$applyUrl = '/staff/tools/run.php?tool=' . rawurlencode($toolKey) . '&apply=1';
$applyUrl = str_replace(["\r","\n"], '', $applyUrl);

/* -----------------------------
   Capability check
----------------------------- */
try {
  $st = $pdo->query("
    SELECT 1
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME='page_files'
    LIMIT 1
  ");
  if (!(bool)$st->fetchColumn()) {
    echo "page_files table missing.\n";
    exit;
  }
} catch (Throwable $e) {
  echo "Cannot inspect schema.\n";
  exit;
}

/* Detect required columns */
$cols = [];
try {
  $stc = $pdo->query("SHOW COLUMNS FROM page_files");
  foreach (($stc ? $stc->fetchAll(PDO::FETCH_ASSOC) : []) as $r) {
    $f = (string)($r['Field'] ?? '');
    if ($f !== '') $cols[$f] = true;
  }
} catch (Throwable $e) {
  echo "Cannot read page_files columns.\n";
  exit;
}

$need = ['id','page_id','is_external','external_url','external_host'];
foreach ($need as $c) {
  if (empty($cols[$c])) {
    echo "Missing required column: {$c}\n";
    exit;
  }
}
$hasName = !empty($cols['original_name']);

echo "Normalize External Attachments (Cleanup)\n";
echo "Started: " . gmdate('c') . "\n";
echo "Mode: " . ($doApply ? "APPLY (mutating)" : "DRY-RUN (no changes)") . "\n";
echo "------------------------------------------------------------\n";

if (!$doApply) {
  echo "APPLY (optional): {$applyUrl}\n";
  echo "------------------------------------------------------------\n";
}

/* Pull candidates (external rows with a URL) */
$sql = "
  SELECT id, page_id, is_external, external_url, external_host" . ($hasName ? ", original_name" : "") . "
  FROM page_files
  WHERE is_external = 1
    AND external_url IS NOT NULL
    AND external_url <> ''
  ORDER BY id ASC
";

$rows = [];
try {
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  echo "Query failed.\n";
  exit;
}

$total = count($rows);
$changed = 0;
$skipped_invalid = 0;
$unchanged = 0;
$issues = [];

$upd = null;
if ($doApply) {
  $upd = $pdo->prepare("
    UPDATE page_files
    SET external_url = :url, external_host = :host
    WHERE id = :id
    LIMIT 1
  ");
}

foreach ($rows as $r) {
  $id = (int)($r['id'] ?? 0);
  $pid = (int)($r['page_id'] ?? 0);
  $storedUrl = trim((string)($r['external_url'] ?? ''));
  $storedHost = trim((string)($r['external_host'] ?? ''));

  if ($id < 1 || $pid < 1 || $storedUrl === '') {
    $skipped_invalid++;
    $issues[] = [
      'type' => 'row_invalid',
      'id' => $id,
      'page_id' => $pid,
      'detail' => 'missing id/page_id/url'
    ];
    continue;
  }

  $v = mk_pagefile__validate_external_url($storedUrl);
  if (empty($v['ok']) || empty($v['url']) || empty($v['host'])) {
    $skipped_invalid++;
    $issues[] = [
      'type' => 'external_invalid',
      'id' => $id,
      'page_id' => $pid,
      'stored' => $storedUrl,
      'error' => (string)($v['error'] ?? 'invalid')
    ];
    continue;
  }

  $normUrl  = (string)$v['url'];
  $normHost = (string)$v['host'];

  $diffUrl  = ($normUrl !== $storedUrl);
  $diffHost = ($normHost !== $storedHost);

  if (!$diffUrl && !$diffHost) {
    $unchanged++;
    continue;
  }

  $changed++;

  $issues[] = [
    'type' => ($doApply ? 'updated' : 'would_update'),
    'id' => $id,
    'page_id' => $pid,
    'stored_url' => $storedUrl,
    'norm_url' => $normUrl,
    'stored_host' => $storedHost,
    'norm_host' => $normHost,
    'label' => $hasName ? (string)($r['original_name'] ?? '') : ''
  ];

  if ($doApply && $upd instanceof PDOStatement) {
    try {
      $upd->bindValue(':url', $normUrl, PDO::PARAM_STR);
      $upd->bindValue(':host', $normHost, PDO::PARAM_STR);
      $upd->bindValue(':id', $id, PDO::PARAM_INT);
      $upd->execute();
    } catch (Throwable $e) {
      $issues[] = [
        'type' => 'update_failed',
        'id' => $id,
        'page_id' => $pid,
        'detail' => 'db update failed'
      ];
    }
  }
}

echo "Rows scanned:            {$total}\n";
echo "Unchanged:              {$unchanged}\n";
echo "Normalize-needed:       {$changed}\n";
echo "Invalid/skipped:        {$skipped_invalid}\n";
echo "------------------------------------------------------------\n";

$max = 80;
echo "Issues (showing up to {$max}): " . min(count($issues), $max) . "\n";
for ($i = 0; $i < count($issues) && $i < $max; $i++) {
  echo "- " . json_encode($issues[$i], JSON_UNESCAPED_SLASHES) . "\n";
}

echo "------------------------------------------------------------\n";
if (!$doApply) {
  echo "To APPLY updates: click Apply in the runner, or use apply=1.\n";
}
