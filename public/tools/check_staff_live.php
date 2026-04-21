<?php
declare(strict_types=1);

$root = __DIR__ . '/../public/staff';
$badGuard = [];
$badSession = [];
$phpFiles = [];

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($it as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) continue;

    $path = $file->getPathname();
    $base = basename($path);

    if (strtolower($file->getExtension()) !== 'php') continue;
    if (preg_match('/(\.bak\b|~$|\.old\b|\.orig\b|\.save\b|bak_|\.tmp\b)/i', $base)) continue;

    $phpFiles[] = $path;
    $src = file_get_contents($path);
    if ($src === false) continue;

    if (preg_match('/\brequire_staff_login\s*\(|\brequire_staff\s*\(/', $src)) {
        $badGuard[] = $path;
    }

    if (preg_match('/session_status\s*\(\)\s*!==\s*PHP_SESSION_ACTIVE|@session_start\s*\(/', $src)) {
        $badSession[] = $path;
    }
}

echo "LIVE PHP FILES: " . count($phpFiles) . PHP_EOL;

echo PHP_EOL . "LIVE FILES WITH LEGACY GUARDS:" . PHP_EOL;
if (!$badGuard) {
    echo "NONE" . PHP_EOL;
} else {
    foreach ($badGuard as $p) echo $p . PHP_EOL;
}

echo PHP_EOL . "LIVE FILES WITH RAW SESSION STARTS:" . PHP_EOL;
if (!$badSession) {
    echo "NONE" . PHP_EOL;
} else {
    foreach ($badSession as $p) echo $p . PHP_EOL;
}
