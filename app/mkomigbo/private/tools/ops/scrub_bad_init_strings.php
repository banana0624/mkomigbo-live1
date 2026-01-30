<?php
declare(strict_types=1);

/**
 * scrub_bad_init_strings.php (CLI)
 *
 * Purpose:
 * - Read ops/_init_offenders.list (absolute file paths under /public)
 * - In each file, safely neutralize legacy init references WITHOUT breaking PHP syntax:
 *    - Lines containing legacy path strings (e.g. "/private/assets/" . "initialize.php", "/app/private/assets/" . "initialize.php")
 *      are commented out, but any "{" or "}" on that same line is preserved.
 *    - Lines that include/require initialize.php are commented out (also brace-preserving).
 *
 * Safety:
 * - Creates a timestamped .bak_scrub_YYYYmmdd_HHMMSS backup per modified file.
 * - Only touches files listed in _init_offenders.list and under $publicRoot.
 *
 * Usage:
 *   php scrub_bad_init_strings.php
 */

$opsDir     = __DIR__;
$list       = $opsDir . '/_init_offenders.list';
$publicRoot = '/home/mkomigbo/public_html/public';

/** Legacy “bad” init path strings to scrub */
$needles = [
  '/private/assets/' . 'initialize.php',
  '/app/private/assets/' . 'initialize.php',
];

/** Also neutralize direct include/require of initialize.php */
$rxInitInclude = '~\b(require_once|require|include_once|include)\b[^;]*initialize\.php\b~i';

if (!is_file($list)) {
  fwrite(STDERR, "Missing offenders list: {$list}\n");
  exit(1);
}

$files = file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!is_array($files) || count($files) === 0) {
  echo "No offenders listed.\n";
  exit(0);
}

/**
 * Preserve structural braces on scrubbed lines.
 * If a scrubbed line contained "{" and/or "}", keep those tokens to avoid breaking blocks.
 */
function mk_preserve_braces(string $line): string {
  $keep = '';
  // Keep braces in original order of appearance
  $chars = str_split($line);
  foreach ($chars as $ch) {
    if ($ch === '{' || $ch === '}') $keep .= $ch;
  }
  return $keep;
}

/**
 * Comment a line but preserve any braces found on that line.
 */
function mk_comment_line_preserve_braces(string $line, string $note): string {
  $indent = '';
  if (preg_match('~^(\s+)~', $line, $m)) $indent = $m[1];

  $braces = mk_preserve_braces($line);
  $suffix = ($braces !== '') ? (' ' . $braces) : '';

  return $indent . '// [patched] ' . $note . $suffix;
}

$patched = 0;
$scanned = 0;

foreach ($files as $file) {
  $file = trim($file);
  if ($file === '') continue;

  // Guardrails: only patch real files under public root
  if (strpos($file, $publicRoot . '/') !== 0) continue;
  if (!is_file($file)) continue;

  $src = file_get_contents($file);
  if ($src === false) continue;

  $lines = preg_split("/\r\n|\n|\r/", $src);
  if (!is_array($lines)) continue;

  $changed = false;

  foreach ($lines as $i => $ln) {
    $orig = $ln;

    // Skip lines already patched
    $trim = ltrim($ln);
    if (strpos($trim, '// [patched]') === 0) continue;

    // 1) Scrub legacy path strings (needle match)
    $hitNeedle = false;
    foreach ($needles as $n) {
      if ($n !== '' && strpos($ln, $n) !== false) {
        $hitNeedle = true;
        break;
      }
    }
    if ($hitNeedle) {
      $lines[$i] = mk_comment_line_preserve_braces($orig, 'removed legacy initialize path reference');
      $changed = true;
      continue;
    }

    // 2) Scrub direct include/require initialize.php patterns
    if (preg_match($rxInitInclude, $ln)) {
      $lines[$i] = mk_comment_line_preserve_braces($orig, 'removed initialize.php include/require');
      $changed = true;
      continue;
    }

    // 3) As a last resort: if a line mentions initialize.php, comment it (brace-safe)
    //    (This catches dynamic $init patterns that didn’t match rx above.)
    if (stripos($ln, 'initialize.php') !== false) {
      $lines[$i] = mk_comment_line_preserve_braces($orig, 'removed initialize.php reference');
      $changed = true;
      continue;
    }
  }

  $scanned++;

  if (!$changed) continue;

  $bak = $file . '.bak_scrub_' . date('Ymd_His');
  file_put_contents($bak, $src);
  file_put_contents($file, implode("\n", $lines));

  $patched++;
  echo "Scrubbed: {$file}\n";
}

echo "Done.\n";
echo "Scanned files: {$scanned}\n";
echo "Scrubbed files: {$patched}\n";
