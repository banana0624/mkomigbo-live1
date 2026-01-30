<?php
declare(strict_types=1);

/**
 * /private/tools/ops/attachments/normalize_external.php
 *
 * Mutating tool:
 * - DRY-RUN by default
 * - APPLY when POST contains apply=1
 *
 * Runner enforces:
 * - staff auth
 * - role gating
 * - realpath boundary
 * - POST+CSRF for mutating tools
 *
 * This tool does NOT print HTML forms. The runner UI should provide the Apply button.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$started = gmdate('c');

if (!defined('APP_ROOT')) {
  // Safety: load initialize if tool executed outside runner for any reason.
  $guess = __DIR__ . '/../../../assets/initialize.php'; // /private/tools/ops/attachments -> /private/assets
  if (is_file($guess)) require_once $guess;
}

if (!function_exists('db')) {
  echo "Normalize External Attachments (Cleanup)\n";
  echo "Started: {$started}\n";
  echo "FATAL: db() not available.\n";
  exit;
}

$pdo = db();
if (!$pdo instanceof PDO) {
  echo "Normalize External Attachments (Cleanup)\n";
  echo "Started: {$started}\n";
  echo "FATAL: db() did not return PDO.\n";
  exit;
}

/* APPLY only via POST (runner will POST+CSRF) */
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$apply  = ($method === 'POST' && (string)($_POST['apply'] ?? '') === '1');

echo "Normalize External Attachments (Cleanup)\n";
echo "Started: {$started}\n";
echo "Mode: " . ($apply ? "APPLY (mutating)" : "DRY-RUN (no changes)") . "\n";
echo str_repeat('-', 60) . "\n";

/* ---- helpers ---- */
$strip_controls = static function (string $s): string {
  $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s) ?? $s;
  return trim($s);
};

$normalize_host = static function (string $host): string {
  $host = strtolower(trim($host));
  $host = preg_replace('/^www\./i', '', $host) ?? $host;
  return $host;
};

$normalize_url = static function (string $url): string {
  $url = preg_replace('/[\x00-\x1F\x7F]/u', '', $url) ?? $url;
  $url = trim($url);

  if ($url === '') return '';

  // Remove <...>
  if ($url[0] === '<' && substr($url, -1) === '>') {
    $url = trim(substr($url, 1, -1));
  }

  // Take first token if pasted with extra text
  if (preg_match('/\s/u', $url)) {
    $parts = preg_split('/\s+/u', $url);
    if (is_array($parts) && isset($parts[0])) $url = trim((string)$parts[0]);
  }

  // Strip trailing punctuation often pasted
  $url = rtrim($url, " \t\n\r\0\x0B.,;:)]}'\"");

  // Scheme-less -> https
  if (preg_match('~^//~', $url)) $url = 'https:' . $url;
  if (!preg_match('~^[a-z][a-z0-9+\-.]*://~i', $url)) {
    $url = 'https://' . ltrim($url, '/');
  }

  // Force https
  $p = @parse_url($url);
  if (is_array($p)) {
    $host = (string)($p['host'] ?? '');
    $path = (string)($p['path'] ?? '');
    $qs   = isset($p['query']) ? ('?' . (string)$p['query']) : '';
    $frag = isset($p['fragment']) ? ('#' . (string)$p['fragment']) : '';

    $host = $strip_controls($host);
    $path = $strip_controls($path);

    if ($host !== '') {
      $url = 'https://' . $host . $path . $qs . $frag;
    }
  }

  // Normalize: remove www. host (canonical)
  $p = @parse_url($url);
  if (!is_array($p)) return $url;

  $host = strtolower((string)($p['host'] ?? ''));
  $host = preg_replace('/^www\./i', '', $host) ?? $host;

  $path = (string)($p['path'] ?? '');
  $qs   = isset($p['query']) ? ('?' . (string)$p['query']) : '';
  $frag = isset($p['fragment']) ? ('#' . (string)$p['fragment']) : '';

  if ($host === '') return $url;

  return 'https://' . $host . $path . $qs . $frag;
};

$host_from_url = static function (string $url): string {
  $p = @parse_url($url);
  if (!is_array($p)) return '';
  $host = strtolower((string)($p['host'] ?? ''));
  $host = preg_replace('/^www\./i', '', $host) ?? $host;
  return $host;
};

/* ---- schema check ---- */
$have = [];
try {
  $st = $pdo->query("SHOW COLUMNS FROM page_files");
  foreach (($st ? $st->fetchAll(PDO::FETCH_ASSOC) : []) as $r) {
    $f = (string)($r['Field'] ?? '');
    if ($f !== '') $have[$f] = true;
  }
} catch (Throwable $e) {
  echo "FATAL: could not introspect schema.\n";
  exit;
}

$needed = ['id','page_id','is_external','external_url'];
foreach ($needed as $c) {
  if (empty($have[$c])) {
    echo "FATAL: missing required column: {$c}\n";
    exit;
  }
}

$canHostUpdate = !empty($have['external_host']);

/* ---- scan rows ---- */
$sql = "SELECT id, page_id, is_external, external_url" . ($canHostUpdate ? ", external_host" : "") . " FROM page_files WHERE is_external = 1 ORDER BY id ASC";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

$scanned = 0;
$unchanged = 0;
$need = 0;
$skipped = 0;
$updated = 0;

$issues = [];

foreach ($rows as $r) {
  $scanned++;
  $id = (int)($r['id'] ?? 0);
  $pid = (int)($r['page_id'] ?? 0);

  $storedUrl = (string)($r['external_url'] ?? '');
  $storedHost = $canHostUpdate ? (string)($r['external_host'] ?? '') : '';

  $storedUrl = $strip_controls($storedUrl);
  $normUrl = $normalize_url($storedUrl);

  if ($normUrl === '') {
    $skipped++;
    $issues[] = ["type"=>"invalid_skipped","id"=>$id,"page_id"=>$pid,"detail"=>"empty after normalization","stored"=>$storedUrl];
    continue;
  }

  $normHost = $host_from_url($normUrl);
  $storedHostClean = $normalize_host($storedHost);

  $needsUrl = ($normUrl !== $storedUrl);
  $needsHost = ($canHostUpdate && $normHost !== '' && $storedHostClean !== '' && $normHost !== $storedHostClean);

  if (!$needsUrl && !$needsHost) {
    $unchanged++;
    continue;
  }

  $need++;

  if (!$apply) {
    $issues[] = [
      "type" => "would_update",
      "id" => $id,
      "page_id" => $pid,
      "stored_url" => $storedUrl,
      "norm_url" => $normUrl,
      "stored_host" => $storedHost,
      "norm_host" => ($normHost !== '' ? $normHost : $storedHost),
    ];
    continue;
  }

  // APPLY
  try {
    $pdo->beginTransaction();

    if ($canHostUpdate) {
      $newHost = ($normHost !== '' ? $normHost : $storedHostClean);
      $st = $pdo->prepare("UPDATE page_files SET external_url = :u, external_host = :h WHERE id = :id AND page_id = :pid LIMIT 1");
      $st->execute([
        ':u' => $normUrl,
        ':h' => $newHost,
        ':id'=> $id,
        ':pid'=>$pid,
      ]);
    } else {
      $st = $pdo->prepare("UPDATE page_files SET external_url = :u WHERE id = :id AND page_id = :pid LIMIT 1");
      $st->execute([
        ':u' => $normUrl,
        ':id'=> $id,
        ':pid'=>$pid,
      ]);
    }

    $pdo->commit();
    $updated++;
    $issues[] = [
      "type" => "updated",
      "id" => $id,
      "page_id" => $pid,
      "stored_url" => $storedUrl,
      "norm_url" => $normUrl,
      "stored_host" => $storedHost,
      "norm_host" => ($normHost !== '' ? $normHost : $storedHost),
    ];
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $issues[] = [
      "type" => "update_failed",
      "id" => $id,
      "page_id" => $pid,
      "detail" => $e->getMessage(),
    ];
  }
}

echo "Rows scanned:            {$scanned}\n";
echo "Unchanged:              {$unchanged}\n";
echo "Normalize-needed:       {$need}\n";
echo "Invalid/skipped:        {$skipped}\n";
if ($apply) echo "Updated:                {$updated}\n";
echo str_repeat('-', 60) . "\n";

$cap = 80;
echo "Issues (showing up to {$cap}): " . min(count($issues), $cap) . "\n";
foreach (array_slice($issues, 0, $cap) as $it) {
  echo "- " . json_encode($it, JSON_UNESCAPED_SLASHES) . "\n";
}
echo str_repeat('-', 60) . "\n";
