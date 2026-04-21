<?php
declare(strict_types=1);

$targets = [
    __DIR__ . '/../public/staff/pages/attachments_delete.php',
    __DIR__ . '/../public/staff/pages/attachments_external_add.php',
    __DIR__ . '/../public/staff/pages/attachments_upload.php',
    __DIR__ . '/../public/staff/pages/show.php',
    __DIR__ . '/../public/staff/pages/workflow_update.php',
    __DIR__ . '/../public/staff/login.php',
    __DIR__ . '/../public/staff/logout.php',
    __DIR__ . '/../public/staff/whoami.php',
];

$ts = date('Ymd_His');

$patterns = [
    // Full mk__session_start / @session_start fallback block
    '~if\s*\(\s*function_exists\(\'mk__session_start\'\)\s*\)\s*\{\s*mk__session_start\(\);\s*\}\s*elseif\s*\(\s*session_status\(\)\s*!==\s*PHP_SESSION_ACTIVE\s*\)\s*\{\s*@session_start\(\);\s*\}~si',
    '~if\s*\(\s*function_exists\("mk__session_start"\)\s*\)\s*\{\s*mk__session_start\(\);\s*\}\s*elseif\s*\(\s*session_status\(\)\s*!==\s*PHP_SESSION_ACTIVE\s*\)\s*\{\s*@session_start\(\);\s*\}~si',

    // Standalone raw session_start guard
    '~if\s*\(\s*session_status\(\)\s*!==\s*PHP_SESSION_ACTIVE\s*\)\s*\{\s*@session_start\(\);\s*\}~si',
];

$changed = [];

foreach ($targets as $path) {
    if (!is_file($path)) {
        echo "SKIP missing: {$path}\n";
        continue;
    }

    $src = file_get_contents($path);
    if ($src === false) {
        echo "SKIP unreadable: {$path}\n";
        continue;
    }

    $orig = $src;

    foreach ($patterns as $rx) {
        $src = preg_replace($rx, 'mk_staff_session_start();', $src);
    }

    // Clean up accidental duplicates
    $src = preg_replace('/(?:mk_staff_session_start\(\);\s*){2,}/', "mk_staff_session_start();\n", $src);

    if ($src !== $orig) {
        copy($path, $path . '.bak.sessfix_' . $ts);
        file_put_contents($path, $src);
        $changed[] = $path;
    }
}

echo "Changed: " . count($changed) . PHP_EOL;
foreach ($changed as $p) {
    echo $p . PHP_EOL;
}
