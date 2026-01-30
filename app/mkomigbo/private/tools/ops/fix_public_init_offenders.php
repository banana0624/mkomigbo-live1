<?php
declare(strict_types=1);

/**
 * fix_public_init_offenders.php
 *
 * Reads _init_offenders.list and patches each public file to:
 * - insert require_once to the correct /public/_init.php relatively
 * - comment out any initialize.php includes
 */

$list = __DIR__ . '/_init_offenders.list';
$publicRoot = '/home/mkomigbo/public_html/public';

if (!is_file($list)) {
    fwrite(STDERR, "Missing offenders list: {$list}\n");
    exit(1);
}

$files = file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$files) {
    echo "No offenders listed.\n";
    exit(0);
}

function relativeInit(string $file, string $publicRoot): string {
    $dir = dirname($file);
    $rel = ltrim(str_replace($publicRoot, '', $dir), '/');
    if ($rel === '') {
        return "__DIR__ . '/_init.php'";
    }
    $depth = substr_count($rel, '/') + 1;
    return "__DIR__ . '" . str_repeat('/..', $depth) . "/_init.php'";
}

$patched = 0;

foreach ($files as $file) {
    if (!is_file($file)) continue;
    if (strpos($file, $publicRoot . '/') !== 0) continue;

    $src = file_get_contents($file);
    if ($src === false) continue;

    $lines = preg_split("/\r\n|\n|\r/", $src);
    if (!$lines) continue;

    $reqLine = 'require_once ' . relativeInit($file, $publicRoot) . ';';
    $changed = false;

    // Insert require_once _init.php if missing
    $hasInit = false;
    foreach ($lines as $ln) {
        if (stripos($ln, '_init.php') !== false && preg_match('~\b(require|include)\b~i', $ln)) {
            $hasInit = true;
            break;
        }
    }

    if (!$hasInit) {
        $insertAt = 0;
        for ($i = 0; $i < count($lines); $i++) {
            $t = trim($lines[$i]);
            if ($t === '') continue;
            if (strpos($t, '<?php') === 0) { 
                $insertAt = $i + 1; 
                continue; 
            }
            if (preg_match('~^declare\s*\(~', $t)) { 
                $insertAt = $i + 1; 
                continue; 
            }
            break;
        }
        array_splice($lines, $insertAt, 0, [$reqLine, '']);
        $changed = true;
    }

    // Comment out initialize.php includes
    foreach ($lines as $i => $ln) {
        if (stripos($ln, 'initialize.php') !== false) {
            $trim = ltrim($ln);
            if (strpos($trim, '//') === 0) continue;
            $lines[$i] = '// [patched] ' . $ln;
            $changed = true;
        }
    }

    if (!$changed) continue;

    // Create backup
    $bak = $file . '.bak_' . date('Ymd_His');
    file_put_contents($bak, $src);
    
    // Save patched file
    file_put_contents($file, implode("\n", $lines));

    $patched++;
    echo "Patched: {$file}\n";
}

echo "Done. Patched files: {$patched}\n";