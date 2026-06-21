<?php
declare(strict_types=1);

/**
 * /private/functions/page_attachments.php
 * Secure attachment resolver + streamer (read-only)
 */

/* =========================================================
   HARD BOOTSTRAP CONTRACT (DO NOT REMOVE)
   ========================================================= */

if (!defined('APP_ROOT')) {
    throw new RuntimeException('APP_ROOT not defined');
}

/* Fallback safety (prevents earlier fatal you hit) */
if (!defined('PRIVATE_PATH')) {
    define('PRIVATE_PATH', APP_ROOT . '/private/functions');
}

/* =========================================================
   POLICY
   ========================================================= */

function mk_attachment_external_allowed_hosts(): array {
    return [];
}

function mk_attachment_inline_mimes(): array {
    return [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/gif',
        'text/plain',
    ];
}

function mk_attachment_allowed_mimes(): array {
    return [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/gif',
        'text/plain',
        'audio/mpeg',
        'audio/mp3',
        'audio/wav',
        'audio/x-wav',
        'audio/ogg',
        'video/mp4',
        'video/webm',
        'application/zip',
        'application/x-zip-compressed',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];
}

/* =========================================================
   PATH HELPERS
   ========================================================= */

function mk_page_attachment_base_dir(int $pageId): string {
    return rtrim(PRIVATE_PATH, '/\\') . '/uploads/pages/' . max(0, $pageId);
}

function mk_page_attachment_path(int $pageId, string $storedName): string {
    return mk_page_attachment_base_dir($pageId) . '/' . basename($storedName);
}

function mk_attachment_fail(int $code, string $msg = ''): never {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    if ($msg !== '') echo $msg;
    exit;
}

/* =========================================================
   RESOLUTION
   ========================================================= */

function mk_attachment_resolve_local_path(int $pageId, string $storedName): string {
    if ($storedName === '' || !preg_match('~^[A-Za-z0-9][A-Za-z0-9._-]{0,240}$~', basename($storedName))) {
        mk_attachment_fail(404, "File not found.\n");
    }

    $base = mk_page_attachment_base_dir($pageId);
    $target = mk_page_attachment_path($pageId, $storedName);

    $baseReal = realpath($base);
    $fileReal = realpath($target);

    if (!$baseReal || !$fileReal) {
        mk_attachment_fail(404, "File not found.\n");
    }

    $baseReal = rtrim(str_replace('\\', '/', $baseReal), '/') . '/';
    $fileReal = str_replace('\\', '/', $fileReal);

    if (strpos($fileReal, $baseReal) !== 0 || !is_readable($fileReal)) {
        mk_attachment_fail(404, "File not found.\n");
    }

    return $fileReal;
}

function mk_attachment_validate_external_url(?string $url): string {
    $url = trim((string)$url);

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        mk_attachment_fail(404, "Link not available.\n");
    }

    $parts = parse_url($url);
    $scheme = strtolower($parts['scheme'] ?? '');
    $host = strtolower($parts['host'] ?? '');

    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        mk_attachment_fail(404);
    }

    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        mk_attachment_fail(404);
    }

    $allow = mk_attachment_external_allowed_hosts();
    if ($allow && !in_array($host, $allow, true)) {
        mk_attachment_fail(404);
    }

    return $url;
}

/* =========================================================
   STREAMER
   ========================================================= */

function mk_page_attachment_stream(array $file): never {
    header('Cache-Control: private, no-store, no-cache, must-revalidate');

    if (!empty($file['is_external'])) {
        $url = mk_attachment_validate_external_url($file['external_url'] ?? null);
        header('Location: ' . $url, true, 302);
        exit;
    }

    $path = mk_attachment_resolve_local_path(
        (int)$file['page_id'],
        (string)$file['stored_name']
    );

    $mime = mime_content_type($path) ?: 'application/octet-stream';

    if (!in_array($mime, mk_attachment_allowed_mimes(), true)) {
        $mime = 'application/octet-stream';
    }

    $disposition = in_array($mime, mk_attachment_inline_mimes(), true)
        ? 'inline'
        : 'attachment';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($path) . '"');
    header('X-Content-Type-Options: nosniff');

    readfile($path);
    exit;
}