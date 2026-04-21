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
 * - stores normalized host + canonical URL + kind metadata (schema-tolerant)
 */

if (!function_exists('mk_pagefile__default_allowlist')) {
  /**
   * Safe baseline allowlist.
   * You can override/extend via external_attachments_allowlist.php.
   *
   * Rule format:
   *  [
   *    'example.com' => ['subdomains'=>true, 'paths'=>['/allowed/prefix']],
   *    '*.example.com' => ['paths'=>['/whatever']],
   *  ]
   */
  function mk_pagefile__default_allowlist(): array {
    return [
      // Wikipedia (tight): allow subdomains, restrict to /wiki/
      'wikipedia.org' => [
        'subdomains' => true,
        'paths'      => ['/wiki/'],
      ],
      'wikimedia.org' => [
        'subdomains' => true,
        'paths'      => ['/wiki/'],
      ],

      // YouTube (common)
      'youtube.com' => [
        'subdomains' => true,
        // allow typical paths; keep open (no 'paths' means all paths allowed)
      ],
      'youtu.be' => [
        // exact host only; short links
      ],
    ];
  }
}

if (!function_exists('mk_pagefile__load_allowlist')) {
  function mk_pagefile__load_allowlist(): array {
    $candidates = [];

    // Preferred: PRIVATE_PATH (your runtime source of truth)
    if (defined('PRIVATE_PATH') && is_string(PRIVATE_PATH) && PRIVATE_PATH !== '') {
      $candidates[] = rtrim(PRIVATE_PATH, "/\\") . '/config/external_attachments_allowlist.php';
    }

    // Back-compat: APP_ROOT/private/config
    if (defined('APP_ROOT') && is_string(APP_ROOT) && APP_ROOT !== '') {
      $candidates[] = rtrim((string)APP_ROOT, "/\\") . '/private/config/external_attachments_allowlist.php';
    }

    foreach ($candidates as $fn) {
      if ($fn !== '' && is_file($fn)) {
        $cfg = require $fn;
        if (is_array($cfg) && $cfg !== []) return $cfg;
        // If file exists but empty/invalid, fall back to defaults.
        break;
      }
    }

    return mk_pagefile__default_allowlist();
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
    // Exact key
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
   * - preserves existing %XX sequences (via decode+encode)
   * - encodes spaces and unsafe characters
   * - keeps "/" separators
   */
  function mk_pagefile__encode_path_safely(string $path): string {
    if ($path === '') return '/';
    if ($path[0] !== '/') $path = '/' . $path;

    $segs = explode('/', $path);
    foreach ($segs as $i => $seg) {
      $decoded = rawurldecode($seg);
      $segs[$i] = rawurlencode($decoded);
    }
    $out = implode('/', $segs);

    if ($out === '') $out = '/';
    if ($out[0] !== '/') $out = '/' . $out;

    return $out;
  }
}

if (!function_exists('mk_pagefile__infer_kind')) {
  function mk_pagefile__infer_kind(string $url, string $host): string {
    $u = strtolower($url);
    $h = strtolower($host);

    if ($h === 'youtu.be' || str_contains($h, 'youtube.com')) return 'video';
    if (str_contains($h, 'wikipedia.org')) return 'wiki';
    if (preg_match('~\.pdf([?#]|$)~i', $u)) return 'pdf';
    if (preg_match('~\.(mp3|wav|m4a)([?#]|$)~i', $u)) return 'audio';
    if (preg_match('~\.(mp4|webm|mov)([?#]|$)~i', $u)) return 'video';
    return 'web';
  }
}

if (!function_exists('mk_pagefile__infer_lang')) {
  function mk_pagefile__infer_lang(string $host): string {
    $h = strtolower($host);
    // Wikipedia subdomains like en.wikipedia.org, fr.wikipedia.org
    if (preg_match('~^([a-z]{2,3})\.wikipedia\.org$~i', $h, $m)) {
      return strtolower($m[1]);
    }
    return '';
  }
}

if (!function_exists('mk_pagefile__make_source_key')) {
  function mk_pagefile__make_source_key(string $host, string $url): string {
    // Small stable key: host + short hash of URL
    $h = strtolower($host);
    $hash = substr(sha1($url), 0, 12);
    $key = $h . ':' . $hash;
    // enforce <= 64
    return (strlen($key) > 64) ? substr($key, 0, 64) : $key;
  }
}

if (!function_exists('mk_pagefile__make_source_label')) {
  function mk_pagefile__make_source_label(string $host, ?string $root = null): string {
    $h = strtolower($host);
    $r = $root ? strtolower($root) : '';
    $show = $r !== '' ? $r : $h;
    return 'External link (' . $show . ')';
  }
}

if (!function_exists('mk_pagefile__validate_external_url')) {
  /**
   * @return array{
   *   ok:bool,
   *   url?:string,
   *   host?:string,
   *   matched_root?:string,
   *   kind?:string,
   *   lang?:string,
   *   source_key?:string,
   *   source_label?:string,
   *   error?:string
   * }
   */
  function mk_pagefile__validate_external_url(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') return ['ok' => false, 'error' => 'URL is required.'];

    // Strip control chars + CRLF
    $raw = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw) ?? $raw;
    $raw = str_replace(["\r", "\n"], '', $raw);

    // Encode whitespace safely without modifying length
    if (preg_match('/\s/u', $raw)) {
      $raw = preg_replace('/\s+/u', '%20', $raw) ?? $raw;
    }

    // Normalize common inputs
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
    $port   = (int)($p['port'] ?? 0);

    if ($scheme !== 'https') return ['ok' => false, 'error' => 'Only HTTPS URLs are allowed.'];
    if ($host === '') return ['ok' => false, 'error' => 'URL host is missing.'];

    // Disallow userinfo
    if (isset($p['user']) || isset($p['pass'])) {
      return ['ok' => false, 'error' => 'Blocked URL format.'];
    }

    // Disallow weird ports (only default 443 or none)
    if ($port !== 0 && $port !== 443) {
      return ['ok' => false, 'error' => 'Blocked URL port.'];
    }

    $host = mk_pagefile__normalize_host($host);

    if (!preg_match('~^[a-z0-9.-]+$~i', $host)) {
      return ['ok' => false, 'error' => 'Blocked URL host.'];
    }

    if (mk_pagefile__is_private_ip($host)) {
      return ['ok' => false, 'error' => 'Blocked URL host.'];
    }

    // Allowlist enforcement
    $allow = mk_pagefile__load_allowlist();
    $m = mk_pagefile__allowlist_match($host, $allow);
    if (empty($m['ok']) || empty($m['rule']) || !is_array($m['rule'])) {
      return ['ok' => false, 'error' => 'Host is not allowlisted.'];
    }

    // Encode path robustly
    $path = ($path !== '') ? $path : '/';
    $path = mk_pagefile__encode_path_safely($path);

    // Optional path-prefix rules (compare decoded path)
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

    $query = isset($p['query']) ? ('?' . (string)$p['query']) : '';
    $frag  = isset($p['fragment']) ? ('#' . (string)$p['fragment']) : '';

    // Build canonical URL (HTTPS only, normalized host + encoded path)
    $url = 'https://' . $host . $path . $query . $frag;

    $root = (string)($m['root'] ?? '');
    $kind = mk_pagefile__infer_kind($url, $host);
    $lang = mk_pagefile__infer_lang($host);

    return [
      'ok'          => true,
      'url'         => $url,
      'host'        => $host,
      'matched_root'=> ($root !== '' ? $root : $host),
      'kind'        => $kind,
      'lang'        => $lang,
      'source_key'  => mk_pagefile__make_source_key($host, $url),
      'source_label'=> mk_pagefile__make_source_label($host, ($root !== '' ? $root : null)),
    ];
  }
}
