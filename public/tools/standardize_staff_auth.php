<?php
declare(strict_types=1);

$root = __DIR__ . '/../public/staff';
if (!is_dir($root)) {
    fwrite(STDERR, "staff root not found: {$root}\n");
    exit(1);
}

$ts = date('Ymd_His');
$changed = [];
$scanned = 0;

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($it as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) continue;
    $path = $file->getPathname();

    if (strtolower($file->getExtension()) !== 'php') continue;

    $base = basename($path);

    // Skip obvious backups/temp files
    if (preg_match('/(\.bak\b|~$|\.old\b|\.orig\b|\.save\b|bak_|\.tmp\b)/i', $base)) {
        continue;
    }

    $src = file_get_contents($path);
    if ($src === false) continue;

    $orig = $src;
    $scanned++;

    // 1) Remove redundant raw session_start blocks
    $src = preg_replace(
        '~^\s*if\s*\(\s*session_status\(\)\s*!==\s*PHP_SESSION_ACTIVE\s*\)\s*\{\s*@session_start\(\);\s*\}\s*\R?~mi',
        '',
        $src
    );

    // 2) Replace mixed fallback guard chains with the canonical guard
    $src = preg_replace(
        '~if\s*\(\s*function_exists\(\'require_staff\'\)\s*\)\s*\{\s*require_staff\(\);\s*\}\s*'
      . 'elseif\s*\(\s*function_exists\(\'require_staff_login\'\)\s*\)\s*\{\s*require_staff_login\(\);\s*\}\s*'
      . 'elseif\s*\(\s*function_exists\(\'mk_require_staff_login\'\)\s*\)\s*\{\s*mk_require_staff_login\(\);\s*\}~si',
        "mk_require_staff_login();",
        $src
    );

    // 3) Replace direct legacy guard calls with canonical guard
    $src = preg_replace(
        '~^\s*require_staff_login\(\);\s*$~mi',
        'mk_require_staff_login();',
        $src
    );

    $src = preg_replace(
        '~^\s*require_staff\(\);\s*$~mi',
        'mk_require_staff_login();',
        $src
    );

    // 4) Normalize accidental duplicate blank lines a little
    $src = preg_replace("/\n{3,}/", "\n\n", $src);

    if ($src !== $orig) {
        $bak = $path . '.bak.authstd_' . $ts;
        if (!copy($path, $bak)) {
            fwrite(STDERR, "backup failed: {$path}\n");
            exit(2);
        }
        file_put_contents($path, $src);
        $changed[] = $path;
    }
}

echo "Scanned: {$scanned}\n";
echo "Changed: " . count($changed) . "\n";
foreach ($changed as $p) {
    echo $p . "\n";
}
