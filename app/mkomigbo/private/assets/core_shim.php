<?php
declare(strict_types=1);

/**
 * /private/assets/core_shim.php
 *
 * PURPOSE:
 * - Provide minimal global helpers early, so templates/shared code can run.
 * - MUST NOT implement db() in this shim (database.php must own db()).
 *
 * NOTE:
 * - Keep this file small and dependency-free.
 */

if (!function_exists('h')) {
  function h(?string $s): string
  {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}

if (!function_exists('u')) {
  function u(?string $s): string
  {
    return rawurlencode((string)$s);
  }
}

/**
 * url_for('/subjects/') => (WWW_ROOT.'/subjects/') if WWW_ROOT is set, else '/subjects/'
 */
if (!function_exists('url_for')) {
  function url_for(string $path): string
  {
    $path = trim($path);
    if ($path === '') $path = '/';
    if ($path[0] !== '/') $path = '/' . $path;

    // Normalize duplicate slashes
    $path = preg_replace('~/{2,}~', '/', $path) ?: $path;

    $www = defined('WWW_ROOT') ? (string)WWW_ROOT : '';
    $www = trim($www);

    if ($www === '' || $www === '/') {
      return $path;
    }

    if ($www[0] !== '/') $www = '/' . $www;
    $www = rtrim($www, '/');

    return $www . $path;
  }
}

if (!function_exists('is_post_request')) {
  function is_post_request(): bool
  {
    return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
  }
}

if (!function_exists('is_get_request')) {
  function is_get_request(): bool
  {
    return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET';
  }
}

if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void
  {
    // Fail-safe: never allow header injection
    $location = str_replace(["\r", "\n"], '', $location);
    header("Location: {$location}");
    exit;
  }
}

if (!function_exists('request_uri_path')) {
  function request_uri_path(): string
  {
    $uri  = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($uri, PHP_URL_PATH);
    $path = is_string($path) ? $path : '/';
    return ($path !== '') ? $path : '/';
  }
}
