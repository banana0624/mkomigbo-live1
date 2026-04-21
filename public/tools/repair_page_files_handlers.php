<?php
declare(strict_types=1);

function backup_and_write(string $path, string $content): void {
    $ts = date('Ymd_His');
    @copy($path, $path . '.bak.pfhandler_' . $ts);
    file_put_contents($path, $content);
}

function insert_once_after(string $src, string $needle, string $insert): string {
    if (strpos($src, $insert) !== false) return $src;
    $pos = strpos($src, $needle);
    if ($pos === false) return $src;
    $pos += strlen($needle);
    return substr($src, 0, $pos) . $insert . substr($src, $pos);
}

$open = __DIR__ . '/../public/staff/page-files/open.php';
$download = __DIR__ . '/../public/staff/page-files/download.php';

if (!is_file($open) || !is_file($download)) {
    fwrite(STDERR, "Required files not found.\n");
    exit(1);
}

/* ---------------- open.php ---------------- */
$src = file_get_contents($open);
if ($src === false) {
    fwrite(STDERR, "Failed to read open.php\n");
    exit(1);
}

$sessionBlock = <<<'TXT'

if (function_exists('mk_staff_session_start')) {
  mk_staff_session_start();
} elseif (function_exists('mk__session_start')) {
  mk__session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
  @session_start();
}
TXT;

$src2 = insert_once_after($src, "require_once \$publicInit;\n", $sessionBlock . "\n");

if ($src2 !== $src) {
    backup_and_write($open, $src2);
    echo "PATCHED: public/staff/page-files/open.php\n";
} else {
    echo "UNCHANGED: public/staff/page-files/open.php\n";
}

/* ---------------- download.php ---------------- */
$src = file_get_contents($download);
if ($src === false) {
    fwrite(STDERR, "Failed to read download.php\n");
    exit(1);
}

$sessionBlock2 = <<<'TXT'

if (function_exists('mk_staff_session_start')) {
  mk_staff_session_start();
} elseif (function_exists('mk__session_start')) {
  mk__session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
  @session_start();
}
TXT;

$src2 = $src;

/* Ensure session starts before fallback CSRF helper */
$src2 = insert_once_after(
    $src2,
    "if (\$method !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }\n",
    $sessionBlock2 . "\n"
);

/* Remove misplaced audit block that uses variables before they are defined */
$badAudit = <<<'TXT'
mk_staff_audit_try($pdo, 'page_file.download.local_attachment', [
      'file_id' => $fileId,
      'page_id' => $pageId,
      'name'    => $downloadName,
      'mime'    => $mime,
      'bytes'   => (int)@filesize($real),
    ]);

/* Download headers */
TXT;

$src2 = str_replace($badAudit, "/* Download headers */\n", $src2);

/* Reinsert audit after $downloadName and $mime are defined */
$goodNeedle = <<<'TXT'
$mime = trim((string)($row['mime_type'] ?? ''));
if ($mime === '') $mime = 'application/octet-stream';

TXT;

$goodAudit = <<<'TXT'
mk_staff_audit_try($pdo, 'page_file.download.local_attachment', [
      'file_id' => $fileId,
      'page_id' => $pageId,
      'name'    => $downloadName,
      'mime'    => $mime,
      'bytes'   => (int)@filesize($real),
    ]);

TXT;

if (strpos($src2, $goodAudit) === false) {
    $src2 = insert_once_after($src2, $goodNeedle, $goodAudit);
}

if ($src2 !== $src) {
    backup_and_write($download, $src2);
    echo "PATCHED: public/staff/page-files/download.php\n";
} else {
    echo "UNCHANGED: public/staff/page-files/download.php\n";
}
