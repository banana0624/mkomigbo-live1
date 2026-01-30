<?php
declare(strict_types=1);

/**
 * /public/staff/pages/attachments_external_add.php
 * Staff: add an external attachment (allowlisted HTTPS only).
 *
 * POST:
 * - csrf_token
 * - page_id
 * - external_url
 * - label (optional)
 * - return
 *
 * Redirect: attach=saved|badurl|invalid|denied|csrf|error
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../_init.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }

/* ---------------------------------------------------------
   Minimal fallbacks
--------------------------------------------------------- */
if (!function_exists('staff_safe_return_url')) {
  function staff_safe_return_url(string $raw, string $default): string {
    $raw = trim($raw);
    if ($raw === '') return $default;
    $raw = rawurldecode($raw);
    if ($raw === '' || $raw[0] !== '/') return $default;
    if (preg_match('~^//~', $raw)) return $default;
    if (preg_match('~^[a-z]+:~i', $raw)) return $default;
    if (strpos($raw, '/staff/') !== 0) return $default;
    return $raw;
  }
}
if (!function_exists('staff_redirect')) {
  function staff_redirect(string $location, int $code = 302): void {
    $location = str_replace(["\r","\n"], '', $location);
    header('Location: ' . $location, true, $code);
    exit;
  }
}
if (!function_exists('staff_pdo')) {
  function staff_pdo(): ?PDO {
    return (function_exists('db') && db() instanceof PDO) ? db() : null;
  }
}
if (!function_exists('staff_csrf_verify')) {
  function staff_csrf_verify(string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    $sess = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sess) || $sess === '' || $token === '') return false;
    return hash_equals($sess, $token);
  }
}

$return = staff_safe_return_url((string)($_POST['return'] ?? ''), '/staff/subjects/pgs/index.php');

$go = static function (string $return, string $code): never {
  $target = $return . (strpos($return, '?') === false ? '?' : '&') . 'attach=' . rawurlencode($code);
  $target = str_replace(["\r","\n"], '', $target);
  if (function_exists('url_for')) $target = (string)url_for($target);
  staff_redirect($target, 303);
};

/* ---------------------------------------------------------
   Normalize common URL inputs (scheme-less, protocol-relative)
--------------------------------------------------------- */
$normalize = static function(string $url): string {
  $url = preg_replace('/[\x00-\x1F\x7F]/u', '', $url) ?? $url;
  $url = trim($url);
  if ($url === '') return '';

  if ($url[0] === '<' && substr($url, -1) === '>') {
    $url = trim(substr($url, 1, -1));
  }

  if (preg_match('/\s/u', $url)) {
    $parts = preg_split('/\s+/u', $url);
    if (is_array($parts) && isset($parts[0])) $url = trim((string)$parts[0]);
  }

  $url = rtrim($url, " \t\n\r\0\x0B.,;:)]}'\"");

  if (strncmp($url, '//', 2) === 0) {
    $url = 'https:' . $url;
  } elseif (!preg_match('~^[a-zA-Z][a-zA-Z0-9+\-.]*://~', $url)) {
    if ($url !== '' && ($url[0] === '/' || $url[0] === '\\')) return '';
    $url = 'https://' . $url;
  }

  return $url;
};

/* Auth */
$staffId = function_exists('staff_id') ? (int)staff_id() : 0;
if ($staffId < 1) $go($return, 'denied');

/* CSRF */
$token = (string)($_POST['csrf_token'] ?? ($_POST['csrf'] ?? ''));
if (!staff_csrf_verify($token)) $go($return, 'csrf');

/* Inputs */
$pageId = (int)($_POST['page_id'] ?? 0);
$rawUrl = (string)($_POST['external_url'] ?? '');
$label  = trim((string)($_POST['label'] ?? ''));

if ($pageId < 1) $go($return, 'invalid');

$cleanUrl = $normalize($rawUrl);
if ($cleanUrl === '') $go($return, 'invalid');

/* sanitize label */
$label = str_replace(["\r","\n"], '', $label);
if (function_exists('mb_substr')) $label = (string)mb_substr($label, 0, 255, 'UTF-8');
else $label = substr($label, 0, 255);
$label = trim($label);

/* DB */
$pdo = staff_pdo();
if (!$pdo instanceof PDO) $go($return, 'error');

/* Private authoritative validator/inserter */
if (!defined('PRIVATE_PATH') || !is_string(PRIVATE_PATH) || PRIVATE_PATH === '') $go($return, 'error');

$fn = rtrim(PRIVATE_PATH, '/\\') . '/functions/page_attachments_external.php';
if (!is_file($fn)) $go($return, 'error');
require_once $fn;

/**
 * WRITE-TIME NORMALIZATION (authoritative)
 * - validate + normalize using the same allowlist logic used everywhere else
 * - always save the normalized URL so DB is consistent (no www.* drift)
 */
if (!function_exists('mk_pagefile__validate_external_url')) $go($return, 'error');

$v = mk_pagefile__validate_external_url($cleanUrl);
if (empty($v['ok']) || empty($v['url']) || empty($v['host'])) {
  $go($return, 'badurl');
}

/* save normalized URL */
$cleanUrl = (string)$v['url'];

/* Insert via canonical helper */
if (!function_exists('mk_staff_add_external_page_attachment')) $go($return, 'error');

try {
  $res = mk_staff_add_external_page_attachment($pdo, $pageId, $staffId, $cleanUrl, $label);

  if (!is_array($res) || empty($res['ok'])) {
    $err = strtolower(trim((string)($res['error'] ?? '')));
    if ($err !== '' && (
      (function_exists('str_contains') && (
        str_contains($err, 'allow') ||
        str_contains($err, 'https') ||
        str_contains($err, 'host') ||
        str_contains($err, 'path') ||
        str_contains($err, 'url')
      ))
    )) {
      $go($return, 'badurl');
    }
    $go($return, 'error');
  }

  $go($return, 'saved');
} catch (Throwable $e) {
  $go($return, 'error');
}
