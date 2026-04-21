<?php
declare(strict_types=1);

/**
 * /private/functions/subjects_fs_meta.php
 *
 * Safe-ish metadata extractor for subject page files:
 * - Reads PHP source
 * - Extracts literal string values for keys like 'title' and 'excerpt_html'
 * - DOES NOT execute the file
 *
 * Contract:
 * - Works best when page files contain a literal return array like:
 *     return ['title' => '...', 'excerpt_html' => '...'];
 * - If values are computed (function calls, concatenation, etc.), extractor returns partial/empty.
 */

if (!function_exists('mk_subject_fs_extract_meta')) {
  function mk_subject_fs_extract_meta(string $file, int $max_bytes = 200000): array {
    if ($file === '' || !is_file($file) || !is_readable($file)) return [];

    $src = @file_get_contents($file, false, null, 0, $max_bytes);
    if (!is_string($src) || $src === '') return [];

    // Quick reject
    if (stripos($src, 'return') === false) return [];

    $tokens = @token_get_all($src);
    if (!is_array($tokens) || !$tokens) return [];

    $want = [
      'title' => true,
      'excerpt_html' => true,
      'excerpt' => true,       // optional alias you may use later
      'lede' => true,          // optional alias
    ];

    $out = [];
    $count = 0;

    $next_non_ws = static function(array $tokens, int $i): int {
      $n = count($tokens);
      for ($j = $i; $j < $n; $j++) {
        $t = $tokens[$j];
        if (is_array($t)) {
          if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) continue;
          return $j;
        }
        // string tokens: skip whitespace-like not represented; just return
        return $j;
      }
      return $n;
    };

    $unquote = static function(string $s): string {
      // token_get_all gives quoted strings including quotes
      $q = $s[0] ?? '';
      if (($q === "'" || $q === '"') && substr($s, -1) === $q) {
        $inner = substr($s, 1, -1);
        // handle common escapes
        if ($q === "'") return str_replace(["\\'","\\\\"], ["'","\\"], $inner);
        return stripcslashes($inner);
      }
      return $s;
    };

    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
      if (++$count > 5000) break; // safety cap

      $t = $tokens[$i];
      if (!is_array($t) || $t[0] !== T_CONSTANT_ENCAPSED_STRING) continue;

      $key = strtolower(trim($unquote((string)$t[1])));
      if ($key === '' || !isset($want[$key])) continue;

      // expect =>
      $j = $next_non_ws($tokens, $i + 1);
      if ($j >= $n) continue;
      $arrow = $tokens[$j];
      if (is_array($arrow)) continue;
      if ($arrow !== '=>') continue;

      // expect literal string
      $k = $next_non_ws($tokens, $j + 1);
      if ($k >= $n) continue;
      $valTok = $tokens[$k];

      if (is_array($valTok) && $valTok[0] === T_CONSTANT_ENCAPSED_STRING) {
        $val = $unquote((string)$valTok[1]);
        $out[$key] = $val;
      }

      // stop early if we got what we need
      if (isset($out['title']) && (isset($out['excerpt_html']) || isset($out['excerpt']) || isset($out['lede']))) {
        break;
      }
    }

    // Normalize excerpt key preference
    if (!isset($out['excerpt_html'])) {
      if (isset($out['excerpt'])) $out['excerpt_html'] = (string)$out['excerpt'];
      elseif (isset($out['lede'])) $out['excerpt_html'] = (string)$out['lede'];
    }

    // Return only canonical keys
    $ret = [];
    if (isset($out['title']) && is_string($out['title'])) $ret['title'] = trim($out['title']);
    if (isset($out['excerpt_html']) && is_string($out['excerpt_html'])) $ret['excerpt_html'] = (string)$out['excerpt_html'];
    return $ret;
  }
}
