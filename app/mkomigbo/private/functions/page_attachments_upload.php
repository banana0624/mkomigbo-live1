<?php
declare(strict_types=1);

/**
 * /app/mkomigbo/private/functions/page_attachments_upload.php
 *
 * Function: mk_staff_upload_page_attachments(PDO $pdo, int $pageId, int $staffId, array $files): array
 *
 * Storage:
 * - Disk:   /public_html/lib/uploads/page_files/{page_id}/
 * - DB:     page_files.file_path = /lib/uploads/page_files/{page_id}/{stored}
 *
 * Returns:
 * - ['ok'=>bool, 'saved'=>int, 'errors'=>string[]]
 */

if (!function_exists('mk_staff_upload_page_attachments')) {

  function mk_staff_upload_page_attachments(PDO $pdo, int $pageId, int $staffId, array $files): array {
    $out = ['ok' => false, 'saved' => 0, 'errors' => []];

    if ($pageId < 1) {
      $out['errors'][] = 'Invalid page_id.';
      return $out;
    }

    // Detect web root (/public_html) via PUBLIC_ROOT if available
    $webRoot = null;
    if (defined('PUBLIC_ROOT') && is_string(PUBLIC_ROOT) && PUBLIC_ROOT !== '') {
      $webRoot = realpath(dirname(PUBLIC_ROOT));
    }
    if (!$webRoot) {
      // Fallback: derive from APP_ROOT (/public_html/app/mkomigbo) -> /public_html/app -> /public_html
      if (defined('APP_ROOT') && is_string(APP_ROOT) && APP_ROOT !== '') {
        $webRoot = realpath(dirname(dirname(APP_ROOT)));
      }
    }
    if (!$webRoot) {
      $out['errors'][] = 'Could not resolve web root.';
      return $out;
    }
    $webRoot = rtrim(str_replace('\\', '/', (string)$webRoot), '/');

    $baseRel = '/lib/uploads/page_files/' . $pageId;
    $baseDir = $webRoot . $baseRel;

    if (!is_dir($baseDir)) {
      if (!@mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
        $out['errors'][] = 'Could not create upload directory.';
        return $out;
      }
    }

    // Normalize $_FILES structure
    $names = $files['name'] ?? null;
    $tmps  = $files['tmp_name'] ?? null;
    $errs  = $files['error'] ?? null;
    $sizes = $files['size'] ?? null;
    $types = $files['type'] ?? null;

    if (!is_array($names) || !is_array($tmps) || !is_array($errs)) {
      $out['errors'][] = 'Invalid upload payload.';
      return $out;
    }

    // Introspect page_files columns (schema tolerant)
    $have = [];
    try {
      $stc = $pdo->query("SHOW COLUMNS FROM page_files");
      $cols = $stc ? ($stc->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
      foreach ($cols as $r) {
        $f = (string)($r['Field'] ?? '');
        if ($f !== '') $have[$f] = true;
      }
    } catch (Throwable $e) {
      $out['errors'][] = 'page_files table not accessible.';
      return $out;
    }

    if (!isset($have['page_id'])) {
      $out['errors'][] = 'page_files.page_id missing.';
      return $out;
    }

    // Policy
    $maxBytes = 25 * 1024 * 1024; // 25MB (adjust later if you want)
    $allowedPrefix = '/lib/uploads/page_files/';

    $saved = 0;

    $count = count($names);
    for ($i = 0; $i < $count; $i++) {
      $orig = is_string($names[$i] ?? null) ? trim((string)$names[$i]) : '';
      $tmp  = is_string($tmps[$i] ?? null) ? (string)$tmps[$i] : '';
      $err  = (int)($errs[$i] ?? UPLOAD_ERR_NO_FILE);
      $size = isset($sizes[$i]) ? (int)$sizes[$i] : 0;
      $type = is_string($types[$i] ?? null) ? trim((string)$types[$i]) : '';

      if ($err === UPLOAD_ERR_NO_FILE) continue;

      if ($err !== UPLOAD_ERR_OK) {
        $out['errors'][] = ($orig !== '' ? $orig . ': ' : '') . 'Upload error code ' . $err;
        continue;
      }

      if ($tmp === '' || !is_uploaded_file($tmp)) {
        $out['errors'][] = ($orig !== '' ? $orig . ': ' : '') . 'Temp upload missing.';
        continue;
      }

      if ($size > $maxBytes) {
        $out['errors'][] = ($orig !== '' ? $orig . ': ' : '') . 'File too large.';
        continue;
      }

      // Generate stored name
      $ext = '';
      if ($orig !== '') {
        $bn = basename($orig);
        $pos = strrpos($bn, '.');
        if ($pos !== false) {
          $ext = strtolower(substr($bn, $pos + 1));
          $ext = preg_replace('/[^a-z0-9]+/', '', $ext) ?? '';
          if ($ext !== '') $ext = '.' . $ext;
        }
      }
      $stored = bin2hex(random_bytes(16)) . $ext;

      $dest = rtrim($baseDir, '/\\') . '/' . $stored;

      if (!@move_uploaded_file($tmp, $dest)) {
        $out['errors'][] = ($orig !== '' ? $orig . ': ' : '') . 'Could not move uploaded file.';
        continue;
      }
      @chmod($dest, 0644);

      $filePath = $baseRel . '/' . $stored; // begins with /lib/...
      if (strpos($filePath, $allowedPrefix) !== 0) {
        // should never happen, but keep safety
        @unlink($dest);
        $out['errors'][] = ($orig !== '' ? $orig . ': ' : '') . 'Blocked by path policy.';
        continue;
      }

      // Detect MIME from file (prefer finfo)
      $mime = $type;
      if (function_exists('finfo_open')) {
        try {
          $fi = finfo_open(FILEINFO_MIME_TYPE);
          if ($fi) {
            $det = finfo_file($fi, $dest);
            finfo_close($fi);
            if (is_string($det) && $det !== '') $mime = $det;
          }
        } catch (Throwable $e) {}
      }
      if (!is_string($mime) || trim($mime) === '') $mime = 'application/octet-stream';

      // Build INSERT dynamically based on existing columns
      $cols = [];
      $bind = [];

      $cols[] = 'page_id';   $bind[':page_id'] = $pageId;

      if (isset($have['original_name'])) { $cols[] = 'original_name'; $bind[':original_name'] = ($orig !== '' ? $orig : $stored); }
      if (isset($have['stored_name']))   { $cols[] = 'stored_name';   $bind[':stored_name'] = $stored; }
      if (isset($have['file_path']))     { $cols[] = 'file_path';     $bind[':file_path'] = $filePath; }
      if (isset($have['mime_type']))     { $cols[] = 'mime_type';     $bind[':mime_type'] = $mime; }
      if (isset($have['file_size']))     { $cols[] = 'file_size';     $bind[':file_size'] = (int)@filesize($dest); }

      if (isset($have['is_external']))   { $cols[] = 'is_external';   $bind[':is_external'] = 0; }
      if (isset($have['external_url']))  { $cols[] = 'external_url';  $bind[':external_url'] = null; }

      // Optional audit fields (if your schema has them)
      foreach (['uploaded_by','created_by','staff_id'] as $who) {
        if (isset($have[$who])) { $cols[] = $who; $bind[':'.$who] = $staffId; break; }
      }

      // created_at if NOT auto-managed; only set if column exists AND likely not auto
      if (isset($have['created_at'])) {
        // harmless even if default exists; MySQL will accept explicit value
        $cols[] = 'created_at';
        $bind[':created_at'] = date('Y-m-d H:i:s');
      }

      $sql = "INSERT INTO page_files (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_map(fn($c) => ':' . $c, $cols)) . ")";
      // But our bind keys already include :page_id etc; map accordingly:
      $sql = "INSERT INTO page_files (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_keys($bind)) . ")";

      try {
        $ins = $pdo->prepare($sql);
        foreach ($bind as $k => $v) {
          if ($v === null) $ins->bindValue($k, null, PDO::PARAM_NULL);
          elseif (is_int($v)) $ins->bindValue($k, $v, PDO::PARAM_INT);
          else $ins->bindValue($k, (string)$v, PDO::PARAM_STR);
        }
        $ins->execute();
        $saved++;
      } catch (Throwable $e) {
        // Roll back disk file if DB insert fails
        @unlink($dest);
        $out['errors'][] = ($orig !== '' ? $orig . ': ' : '') . 'DB insert failed: ' . $e->getMessage();
        continue;
      }
    }

    $out['saved'] = $saved;
    $out['ok'] = ($saved > 0);
    return $out;
  }
}
