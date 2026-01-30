<?php
declare(strict_types=1);

/**
 * /private/functions/page_attachments_external.php
 * Add external/remote attachments safely.
 *
 * Guarantees:
 * - HTTPS only
 * - host allowlist enforced (supports subdomains/wildcards via config)
 * - optional path-prefix allowlist enforced
 * - never fetches remote content (no SSRF)
 * - stores normalized host + full URL in DB
 */

if (!function_exists('mk_pagefile__load_allowlist')) {
  function mk_pagefile__load_allowlist(): array {
    $fn = (defined('APP_ROOT') ? rtrim((string)APP_ROOT, "/\\") : '') . '/private/config/external_attachments_allowlist.php';
    if ($fn !== '' && is_file($fn)) {
      $cfg = require $fn;
      return is_array($cfg) ? $cfg : [];
    }
    return [];
  }
}

if (!function_exists('mk_pagefile__normalize_host')) {
  function mk_pagefile__normalize_host(string $host): string {
    $host = strtolower(trim($host));
    $host = rtrim($host, '.');
    $host = preg_replace('/^www\./i', '', $host) ?? $host;
    return $host;
  }
}

if (!function_exists('mk_pagefile__ends_with')) {
  function mk_pagefile__ends_with(string $haystack, string $needle): bool {
    if ($needle === '') return true;
    $len = strlen($needle);
    if ($len === 0) return true;
    return substr($haystack, -$len) === $needle;
  }
}

if (!function_exists('mk_pagefile__allowlist_match')) {
  /**
   * @return array{ok:bool, root?:string, rule?:array}
   */
  function mk_pagefile__allowlist_match(string $host, array $allow): array {
    // Exact
    if (isset($allow[$host]) && is_array($allow[$host])) {
      return ['ok' => true, 'root' => $host, 'rule' => $allow[$host]];
    }

    // Wildcard keys "*.domain.tld"
    foreach ($allow as $k => $rule) {
      if (!is_string($k) || !is_array($rule)) continue;
      $k = trim(strtolower($k));
      if ($k === '' || strpos($k, '*.') !== 0) continue;
      $root = substr($k, 2);
      $root = mk_pagefile__normalize_host($root);
      if ($root === '') continue;

      // wildcard requires a subdomain: x.root
      if ($host !== $root && mk_pagefile__ends_with($host, '.' . $root)) {
        return ['ok' => true, 'root' => $root, 'rule' => $rule];
      }
    }

    // Root keys with 'subdomains' => true
    foreach ($allow as $root => $rule) {
      if (!is_string($root) || !is_array($rule)) continue;
      $rootN = mk_pagefile__normalize_host($root);
      if ($rootN === '') continue;

      $sub = !empty($rule['subdomains']);
      if (!$sub) continue;

      if ($host === $rootN || mk_pagefile__ends_with($host, '.' . $rootN)) {
        return ['ok' => true, 'root' => $rootN, 'rule' => $rule];
      }
    }

    return ['ok' => false];
  }
}

if (!function_exists('mk_pagefile__is_private_ip')) {
  function mk_pagefile__is_private_ip(string $host): bool {
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $long = ip2long($host);
      if ($long === false) return true;

      $ranges = [
        ['10.0.0.0',    '10.255.255.255'],
        ['172.16.0.0',  '172.31.255.255'],
        ['192.168.0.0', '192.168.255.255'],
        ['127.0.0.0',   '127.255.255.255'],
        ['169.254.0.0', '169.254.255.255'],
        ['0.0.0.0',     '0.255.255.255'],
      ];
      foreach ($ranges as [$a, $b]) {
        $la = ip2long($a); $lb = ip2long($b);
        if ($la !== false && $lb !== false && $long >= $la && $long <= $lb) return true;
      }
      return false;
    }

    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      $h = strtolower($host);
      if ($h === '::1') return true;
      if (str_starts_with($h, 'fc') || str_starts_with($h, 'fd')) return true;
      if (str_starts_with($h, 'fe80')) return true;
      return false;
    }

    return false;
  }
}

if (!function_exists('mk_pagefile__encode_path_safely')) {
  /**
   * Encode URL path safely:
   * - preserves existing %XX sequences
   * - encodes spaces and other unsafe characters
   * - keeps "/" separators
   */
  function mk_pagefile__encode_path_safely(string $path): string {
    if ($path === '') return '/';
    if ($path[0] !== '/') $path = '/' . $path;

    // Split by "/" and encode each segment
    $segs = explode('/', $path);
    foreach ($segs as $i => $seg) {
      // Preserve existing percent-escapes by decoding then re-encoding
      // rawurldecode turns "+" into "+" (not space) which is fine for path segments.
      $decoded = rawurldecode($seg);

      // Now re-encode as RFC 3986 for path segments
      $segs[$i] = rawurlencode($decoded);
    }
    // rawurlencode encodes spaces as %20 automatically
    $out = implode('/', $segs);

    // Ensure leading slash preserved
    if ($out === '') $out = '/';
    if ($out[0] !== '/') $out = '/' . $out;

    return $out;
  }
}

if (!function_exists('mk_pagefile__validate_external_url')) {
  /**
   * @return array{ok:bool, url?:string, host?:string, error?:string, matched_root?:string}
   */
  function mk_pagefile__validate_external_url(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') return ['ok' => false, 'error' => 'URL is required.'];

    // Strip control chars + CRLF (header injection)
    $raw = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw) ?? $raw;
    $raw = str_replace(["\r", "\n"], '', $raw);

    // Encode any remaining whitespace (never truncate)
    if (preg_match('/\s/u', $raw)) {
      $raw = preg_replace('/\s+/u', '%20', $raw) ?? $raw;
    }

    // Normalize common inputs:
    // - //host/path  -> https://host/path
    // - host/path    -> https://host/path
    if (strncmp($raw, '//', 2) === 0) {
      $raw = 'https:' . $raw;
    } elseif (!preg_match('~^[a-zA-Z][a-zA-Z0-9+\-.]*://~', $raw)) {
      if ($raw !== '' && ($raw[0] === '/' || $raw[0] === '\\')) {
        return ['ok' => false, 'error' => 'Invalid URL.'];
      }
      $raw = 'https://' . $raw;
    }

    $p = @parse_url($raw);
    if (!is_array($p)) return ['ok' => false, 'error' => 'Invalid URL.'];

    $scheme = strtolower((string)($p['scheme'] ?? ''));
    $host   = (string)($p['host'] ?? '');
    $path   = (string)($p['path'] ?? '/');

    if ($scheme !== 'https') return ['ok' => false, 'error' => 'Only HTTPS URLs are allowed.'];
    if ($host === '') return ['ok' => false, 'error' => 'URL host is missing.'];

    // Disallow userinfo: https://user:pass@host/...
    if (isset($p['user']) || isset($p['pass'])) {
      return ['ok' => false, 'error' => 'Blocked URL format.'];
    }

    $host = mk_pagefile__normalize_host($host);

    // Host sanity: block spaces/underscores/etc.
    if (!preg_match('~^[a-z0-9.-]+$~i', $host)) {
      return ['ok' => false, 'error' => 'Blocked URL host.'];
    }

    // Block literal private/loopback IP hosts
    if (mk_pagefile__is_private_ip($host)) {
      return ['ok' => false, 'error' => 'Blocked URL host.'];
    }

    // Allowlist enforcement
    $allow = mk_pagefile__load_allowlist();
    $m = mk_pagefile__allowlist_match($host, $allow);
    if (empty($m['ok']) || empty($m['rule']) || !is_array($m['rule'])) {
      return ['ok' => false, 'error' => 'Host is not allowlisted.'];
    }

    // Normalize + encode path robustly (not just spaces)
    $path = ($path !== '') ? $path : '/';
    $path = mk_pagefile__encode_path_safely($path);

    // Optional path-prefix rules (compare decoded path to reduce false negatives)
    $paths = $m['rule']['paths'] ?? null;
    if (is_array($paths) && $paths !== []) {
      $ok = false;
      $pathCheck = rawurldecode($path);

      foreach ($paths as $prefix) {
        $prefix = (string)$prefix;
        if ($prefix === '') continue;
        if (str_starts_with($pathCheck, $prefix)) { $ok = true; break; }
      }
      if (!$ok) return ['ok' => false, 'error' => 'URL path is not allowed for this host.'];
    }

    // Keep query/fragment as-is
    $query = isset($p['query']) ? ('?' . (string)$p['query']) : '';
    $frag  = isset($p['fragment']) ? ('#' . (string)$p['fragment']) : '';

    $norm = 'https://' . $host . $path . $query . $frag;

    if (strlen($norm) > 2048) return ['ok' => false, 'error' => 'URL is too long.'];

    return [
      'ok' => true,
      'url' => $norm,
      'host' => $host,
      'matched_root' => (string)($m['root'] ?? ''),
    ];
  }
}

if (!function_exists('mk_staff_add_external_page_attachment')) {
  /**
   * @return array{ok:bool, error?:string, id?:int}
   */
  function mk_staff_add_external_page_attachment(PDO $pdo, int $pageId, int $staffId, string $url, string $label = ''): array {
    if ($pageId < 1) return ['ok' => false, 'error' => 'Invalid page_id.'];
    if ($staffId < 1) return ['ok' => false, 'error' => 'Not authenticated.'];

    // Single source of truth: validate + normalize here
    $v = mk_pagefile__validate_external_url($url);
    if (empty($v['ok']) || empty($v['url']) || empty($v['host'])) {
      return ['ok' => false, 'error' => (string)($v['error'] ?? 'Invalid URL.')];
    }

    $safeLabel = trim($label);
    if ($safeLabel === '') $safeLabel = 'External link';
    $safeLabel = str_replace(["\r", "\n"], '', $safeLabel);
    if (function_exists('mb_substr')) $safeLabel = (string)mb_substr($safeLabel, 0, 255, 'UTF-8');
    else $safeLabel = substr($safeLabel, 0, 255);

    $sql = "
      INSERT INTO page_files
        (page_id, is_external, external_url, external_host, original_name, stored_name, file_path, stored_path, mime_type, file_size, sort_order, created_at)
      VALUES
        (:page_id, 1, :external_url, :external_host, :original_name, '', '', NULL, NULL, NULL, NULL, NOW())
    ";

    try {
      $st = $pdo->prepare($sql);
      $st->execute([
        ':page_id'       => $pageId,
        ':external_url'  => (string)$v['url'],
        ':external_host' => (string)$v['host'],
        ':original_name' => $safeLabel,
      ]);
      $id = (int)$pdo->lastInsertId();
      return ['ok' => true, 'id' => $id];
    } catch (Throwable $e) {
      return ['ok' => false, 'error' => 'DB insert failed.'];
    }
  }
}
