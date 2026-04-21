<?php
declare(strict_types=1);

$targets = [
    'public/staff/pages/show.php',
    'public/staff/pages/workflow_update.php',
    'public/staff/pages/attachments_upload.php',
    'public/staff/pages/attachments_external_add.php',
    'public/staff/pages/attachments_delete.php',
];

$ts = date('Ymd_His');

function pick_backup(string $live): ?string {
    $candidates = glob($live . '.bak*') ?: [];

    if (!$candidates) {
        return null;
    }

    usort($candidates, static function(string $a, string $b): int {
        $ta = @filemtime($a) ?: 0;
        $tb = @filemtime($b) ?: 0;
        return $tb <=> $ta;
    });

    return $candidates[0] ?? null;
}

foreach ($targets as $live) {
    echo "=== {$live}\n";

    if (!is_file($live)) {
        echo "SKIP missing live file\n\n";
        continue;
    }

    $bak = pick_backup($live);
    if (!$bak || !is_file($bak)) {
        echo "SKIP no backup found\n\n";
        continue;
    }

    $origLive = file_get_contents($live);
    $fromBak  = file_get_contents($bak);

    if (!is_string($origLive) || !is_string($fromBak) || trim($fromBak) === '') {
        echo "SKIP unreadable or empty backup\n\n";
        continue;
    }

    copy($live, $live . '.pre_repair_' . $ts);

    $src = $fromBak;

    // Standardize require_once _init.php path only if missing
    if (!preg_match('~require_once\s+__DIR__\s*\.\s*[\'"]/.+?_init\.php[\'"]\s*;~', $src)) {
        $src = preg_replace(
            '~^\s*<\?php\s+declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*~',
            "<?php\ndeclare(strict_types=1);\n\nrequire_once __DIR__ . '/../../_init.php';\n",
            $src,
            1
        );
    }

    // Remove scattered raw session starts near top-level and normalize
    $src = preg_replace(
        '~if\s*\(\s*function_exists\(\'mk__session_start\'\)\s*\)\s*\{\s*mk__session_start\(\);\s*\}\s*elseif\s*\(\s*session_status\(\)\s*!==\s*PHP_SESSION_ACTIVE\s*\)\s*\{\s*@session_start\(\);\s*\}~si',
        "mk_staff_session_start();",
        $src
    );
    $src = preg_replace(
        '~if\s*\(\s*function_exists\("mk__session_start"\)\s*\)\s*\{\s*mk__session_start\(\);\s*\}\s*elseif\s*\(\s*session_status\(\)\s*!==\s*PHP_SESSION_ACTIVE\s*\)\s*\{\s*@session_start\(\);\s*\}~si',
        "mk_staff_session_start();",
        $src
    );
    $src = preg_replace(
        '~^\s*if\s*\(\s*session_status\(\)\s*!==\s*PHP_SESSION_ACTIVE\s*\)\s*\{\s*@session_start\(\);\s*\}\s*$~mi',
        "mk_staff_session_start();",
        $src
    );

    // Canonical guard normalization
    $src = preg_replace(
        '~if\s*\(\s*function_exists\(\'require_staff\'\)\s*\)\s*\{\s*require_staff\(\);\s*\}\s*'
      . 'elseif\s*\(\s*function_exists\(\'require_staff_login\'\)\s*\)\s*\{\s*require_staff_login\(\);\s*\}\s*'
      . 'elseif\s*\(\s*function_exists\(\'mk_require_staff_login\'\)\s*\)\s*\{\s*mk_require_staff_login\(\);\s*\}~si',
        "mk_require_staff_login();",
        $src
    );

    $src = preg_replace('~^\s*require_staff_login\(\);\s*$~mi', 'mk_require_staff_login();', $src);
    $src = preg_replace('~^\s*require_staff\(\);\s*$~mi', 'mk_require_staff_login();', $src);

    // Deduplicate accidental repeats
    $src = preg_replace('/(?:mk_staff_session_start\(\);\s*){2,}/', "mk_staff_session_start();\n", $src);
    $src = preg_replace('/(?:mk_require_staff_login\(\);\s*){2,}/', "mk_require_staff_login();\n", $src);

    file_put_contents($live, $src);

    echo "RESTORED FROM: {$bak}\n";
    echo "WROTE: {$live}\n\n";
}
