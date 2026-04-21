<?php
declare(strict_types=1);

function backup_file(string $path): void {
    $ts = date('Ymd_His');
    @copy($path, $path . '.bak.pffinal_' . $ts);
}

function rr(string $path): string {
    $s = file_get_contents($path);
    if ($s === false) {
        fwrite(STDERR, "Failed to read: {$path}\n");
        exit(1);
    }
    return $s;
}

function ww(string $path, string $s): void {
    backup_file($path);
    file_put_contents($path, $s);
    echo "PATCHED: {$path}\n";
}

/* ---------------------------------------------------------
   1) Fix local path resolution in open.php
--------------------------------------------------------- */
$open = 'public/staff/page-files/open.php';
$s = rr($open);

$old = <<<'TXT'
$abs = $relative;
if ($abs[0] !== '/' && $abs[0] !== '\\') {
  $abs = $uploadsRootReal . '/' . ltrim($abs, '/\\');
}

$real = realpath($abs);
TXT;

$new = <<<'TXT'
$relNorm = str_replace('\\', '/', $relative);
$abs = '';

/* Case 1: stored as web-root relative, e.g. /lib/uploads/page_files/... */
if (strpos($relNorm, '/lib/uploads/page_files/') === 0) {
  $abs = dirname(__DIR__, 3) . $relNorm;
/* Case 2: stored as relative under uploads root */
} elseif ($relNorm !== '' && $relNorm[0] !== '/') {
  $abs = $uploadsRootReal . '/' . ltrim($relNorm, '/\\');
/* Case 3: stored as absolute filesystem path already */
} else {
  $abs = $relNorm;
}

$real = realpath($abs);
TXT;

if (strpos($s, $old) !== false) {
    $s = str_replace($old, $new, $s);
    ww($open, $s);
} else {
    echo "UNCHANGED: {$open}\n";
}

/* ---------------------------------------------------------
   2) Fix local path resolution + audit placement in download.php
--------------------------------------------------------- */
$download = 'public/staff/page-files/download.php';
$s = rr($download);

$old = <<<'TXT'
$abs = $relative;
if ($abs[0] !== '/' && $abs[0] !== '\\') {
  $abs = $uploadsRootReal . '/' . ltrim($abs, '/\\');
}

$real = realpath($abs);
TXT;

$new = <<<'TXT'
$relNorm = str_replace('\\', '/', $relative);
$abs = '';

/* Case 1: stored as web-root relative, e.g. /lib/uploads/page_files/... */
if (strpos($relNorm, '/lib/uploads/page_files/') === 0) {
  $abs = dirname(__DIR__, 3) . $relNorm;
/* Case 2: stored as relative under uploads root */
} elseif ($relNorm !== '' && $relNorm[0] !== '/') {
  $abs = $uploadsRootReal . '/' . ltrim($relNorm, '/\\');
/* Case 3: stored as absolute filesystem path already */
} else {
  $abs = $relNorm;
}

$real = realpath($abs);
TXT;

if (strpos($s, $old) !== false) {
    $s = str_replace($old, $new, $s);
}

/* Ensure download headers block has audit after variables are defined */
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

if (strpos($s, $badAudit) !== false) {
    $s = str_replace($badAudit, "/* Download headers */\n", $s);
}

$needle = <<<'TXT'
$mime = trim((string)($row['mime_type'] ?? ''));
if ($mime === '') $mime = 'application/octet-stream';

TXT;

$insert = <<<'TXT'
mk_staff_audit_try($pdo, 'page_file.download.local_attachment', [
      'file_id' => $fileId,
      'page_id' => $pageId,
      'name'    => $downloadName,
      'mime'    => $mime,
      'bytes'   => (int)@filesize($real),
    ]);

TXT;

if (strpos($s, $insert) === false) {
    $pos = strpos($s, $needle);
    if ($pos !== false) {
        $pos += strlen($needle);
        $s = substr($s, 0, $pos) . $insert . substr($s, $pos);
    }
}

ww($download, $s);

/* ---------------------------------------------------------
   3) Tighten page-files index table layout
--------------------------------------------------------- */
$index = 'public/staff/page-files/index.php';
$s = rr($index);

$replacements = [
    "style=\"width:100%; min-width:1080px;\"" => "style=\"width:100%; table-layout:fixed;\"",
    "<th>ID</th>" => "<th style=\"width:72px;\">ID</th>",
    "<th>page_id</th>" => "<th style=\"width:84px;\">page_id</th>",
    "<th>Name / URL</th>" => "<th style=\"width:46%;\">Name / URL</th>",
    "<th>Meta</th>" => "<th style=\"width:26%;\">Meta</th>",
    "<th style=\"text-align:right;\">Actions</th>" => "<th style=\"width:220px; text-align:right;\">Actions</th>",
    "<td class=\"mono\"><?php echo (int)\$id; ?></td>" => "<td class=\"mono\" style=\"white-space:nowrap;\"><?php echo (int)\$id; ?></td>",
    "<td class=\"mono\"><?php echo (int)\$pid; ?></td>" => "<td class=\"mono\" style=\"white-space:nowrap;\"><?php echo (int)\$pid; ?></td>",
    "<div class=\"muted\" style=\"margin-top:4px; font-size:.92rem; word-break:break-word;\">" => "<div class=\"muted\" style=\"margin-top:4px; font-size:.92rem; word-break:break-word; overflow-wrap:anywhere;\">",
    "<td class=\"muted\"><?php echo h(implode(' • ', array_filter(\$meta))); ?></td>" => "<td class=\"muted\" style=\"overflow-wrap:anywhere;\"><?php echo h(implode(' • ', array_filter(\$meta))); ?></td>",
    "<td style=\"text-align:right; white-space:nowrap;\">" => "<td style=\"text-align:right; white-space:normal;\">",
];

$changed = false;
foreach ($replacements as $a => $b) {
    if (strpos($s, $a) !== false) {
        $s = str_replace($a, $b, $s);
        $changed = true;
    }
}

if ($changed) {
    ww($index, $s);
} else {
    echo "UNCHANGED: {$index}\n";
}

/* ---------------------------------------------------------
   4) Fix normalize page action overlap
--------------------------------------------------------- */
$norm = 'public/staff/page-files/normalize.php';
$s = rr($norm);

$rep2 = [
    "<div class=\"hero\">" => "<div class=\"hero\" style=\"margin-bottom:16px;\">",
    "<div class=\"hero__row\">" => "<div class=\"hero__row\" style=\"display:flex; gap:14px; justify-content:space-between; align-items:flex-start; flex-wrap:wrap;\">",
    "<div class=\"hero__actions\">" => "<div class=\"hero__actions\" style=\"display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:4px;\">",
];

$changed = false;
foreach ($rep2 as $a => $b) {
    if (strpos($s, $a) !== false) {
        $s = str_replace($a, $b, $s);
        $changed = true;
    }
}

if ($changed) {
    ww($norm, $s);
} else {
    echo "UNCHANGED: {$norm}\n";
}
