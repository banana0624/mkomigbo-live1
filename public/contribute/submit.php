<?php
declare(strict_types=1);

/**
 * /public/contribute/submit.php
 * Clean, safe, redirect-correct contribution handler
 */

@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';

if (function_exists('mk__session_start')) {
    mk__session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/* ---------------- HELPERS ---------------- */

if (!function_exists('mk_safe_redirect')) {
    function mk_safe_redirect(string $url): void {
        header('Location: ' . str_replace(["\r","\n"], '', $url));
        exit;
    }
}

if (!function_exists('mk_clean_slug')) {
    function mk_clean_slug(string $v): string {
        $v = strtolower(trim($v));
        $v = preg_replace('/[^a-z0-9\-_\/]/', '', $v);
        return trim($v ?? '', '/');
    }
}

/* ---------------- METHOD GUARD ---------------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

/* ---------------- INPUT ---------------- */

$subject_area = trim($_POST['subject_area'] ?? '');
$page_path    = trim($_POST['page_path'] ?? '');
$name         = trim($_POST['contributor_name'] ?? '');
$content      = trim($_POST['message_text'] ?? '');

/* ---------------- VALIDATION ---------------- */

if ($subject_area === '' || $page_path === '' || $content === '') {
    http_response_code(400);
    exit('Missing required fields');
}

/* ---------------- EXTRACT CLEAN SLUGS ---------------- */

/**
 * Expected page_path:
 * /subjects/history/intro/
 */

$page_path = mk_clean_slug($page_path);

/* explode path */
$parts = explode('/', $page_path);

/*
Expected:
[subjects, history, intro]
*/
$subject_slug = '';
$page_slug    = '';

if (count($parts) >= 3 && $parts[0] === 'subjects') {
    $subject_slug = $parts[1] ?? '';
    $page_slug    = $parts[2] ?? '';
}

/* fallback if malformed */
if ($subject_slug === '' || $page_slug === '') {
    http_response_code(400);
    exit('Invalid page path');
}

/* ---------------- STORAGE ---------------- */

$dir = __DIR__ . '/../storage/contributions';

if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}

if (!is_writable($dir)) {
    http_response_code(500);
    exit('Storage not writable');
}

/* ---------------- SAVE ---------------- */

$data = [
    'subject'    => $subject_slug,
    'page'       => $page_slug,
    'name'       => $name,
    'content'    => $content,
    'status'     => 'pending',
    'created_at' => date('c'),
    'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
];

$file = $dir . '/' . uniqid('contrib_', true) . '.json';

if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500);
    exit('Failed to save contribution');
}

/* ---------------- REDIRECT (FIXED) ---------------- */

$redirect = "/subjects/" . rawurlencode($subject_slug) . "/" . rawurlencode($page_slug) . "/?contrib=success";

mk_safe_redirect($redirect);