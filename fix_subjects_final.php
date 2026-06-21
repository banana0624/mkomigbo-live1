<?php
declare(strict_types=1);
// UPLOAD TO: /home/mkomigbo/public_html/fix_subjects_final.php
// RUN VIA CLI: php /home/mkomigbo/public_html/fix_subjects_final.php
// This script self-deletes after running.

$file = '/home/mkomigbo/public_html/subjects/index.php';
$c = file_get_contents($file);
if ($c === false) { die("Cannot read file\n"); }

// ── 1. Load theme_functions for accent colors ──────────────────────
$themeReq = "require_once '/home/mkomigbo/releases/2026-04-25-120559/private/functions/theme_functions.php';";
$hasAccentLine = '$has_accent = function_exists(\'pf__accent_for\');';
if (strpos($c, $themeReq) === false) {
    $c = str_replace($hasAccentLine, $themeReq . "\n" . $hasAccentLine, $c);
    echo "OK: theme_functions require added\n";
} else {
    echo "SKIP: theme_functions already loaded\n";
}

// ── 2. Fix onerror JS quote conflict ──────────────────────────────
// Original broken line uses double-quote attribute containing double-quoted JSON
$oldOnerror = 'onerror="this.onerror=null;this.style.display=\'none\';var p=this.parentNode;if(p){var sp=document.createElement(\'span\');sp.className=\'mk-subject-card__initial\';sp.textContent=<?= $ini_js ?>;p.appendChild(sp);}">';
$newOnerror = 'data-initial="<?= htmlspecialchars((string)($ini ?? \'\'), ENT_QUOTES) ?>">';
if (strpos($c, $oldOnerror) !== false) {
    $c = str_replace($oldOnerror, $newOnerror, $c);
    echo "OK: onerror replaced with data-initial\n";
} else {
    echo "SKIP: onerror pattern not found - checking alternate\n";
    // Try finding any onerror on the img
    $c = preg_replace(
        '/onerror="this\.onerror=null[^"]*">/',
        'data-initial="<?= htmlspecialchars((string)($ini ?? \'\'), ENT_QUOTES) ?>">',
        $c
    );
    echo "OK: onerror replaced via regex\n";
}

// ── 3. Add colored bar inline style ───────────────────────────────
$oldBar = '<div class="mk-card__bar" aria-hidden="true"></div>';
$newBar = '<div class="mk-card__bar" aria-hidden="true" style="background:<?= htmlspecialchars($accent, ENT_QUOTES) ?>;height:10px;display:block;width:100%;min-height:10px;"></div>';
if (strpos($c, $oldBar) !== false) {
    $c = str_replace($oldBar, $newBar, $c);
    echo "OK: colored bar added\n";
} else {
    echo "SKIP: bar pattern not found\n";
}

// ── 4. Add nav header ─────────────────────────────────────────────
$nav = '<header style="background:#fff;border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:20;"><div style="max-width:1200px;margin:0 auto;padding:0 16px;display:flex;align-items:center;justify-content:space-between;min-height:60px;gap:14px;flex-wrap:wrap;"><a href="/" style="display:inline-flex;align-items:center;gap:9px;text-decoration:none;color:inherit;"><img src="/assets/images/logos/mk-logo.png" width="30" height="30" style="border-radius:8px;" alt="Mkomigbo"><strong style="font-size:1rem;">Mkomigbo</strong></a><nav style="display:flex;gap:4px;flex-wrap:wrap;"><a href="/subjects/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:800;font-size:.9rem;color:#0d6efd;border:1px solid rgba(13,110,253,.30);background:rgba(13,110,253,.08);">Subjects</a><a href="/platforms/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Platforms</a><a href="/contributors/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Contributors</a><a href="/igbo-calendar/" style="padding:7px 14px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.9rem;color:#374151;border:1px solid #e5e7eb;background:#fff;">Calendar</a></nav></div></header>';

$cssLine = "<link rel='stylesheet' href='/assets/css/subjects-public.css'></head><body>";
if (strpos($c, $cssLine) !== false && strpos($c, 'mk-logo.png') === false) {
    $c = str_replace($cssLine, $cssLine . $nav, $c);
    echo "OK: nav added\n";
} else {
    echo "SKIP: nav already present or CSS pattern not found\n";
}

// ── 5. Add CSS links if missing ───────────────────────────────────
$titleLine = '"</title></head><body>"';
$cssInject = '"</title><link rel=\'stylesheet\' href=\'/assets/css/ui.css\'><link rel=\'stylesheet\' href=\'/assets/css/public.css\'><link rel=\'stylesheet\' href=\'/assets/css/subjects.css\'><link rel=\'stylesheet\' href=\'/assets/css/subjects-public.css\'></head><body>' . $nav . '"';
if (strpos($c, $titleLine) !== false) {
    $c = str_replace($titleLine, $cssInject, $c);
    echo "OK: CSS links added\n";
} else {
    echo "SKIP: CSS already injected\n";
}

// ── Write and verify ──────────────────────────────────────────────
$tmp = $file . '.fix_tmp';
file_put_contents($tmp, $c);
$result = shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1');
if (strpos((string)$result, 'No syntax errors') !== false) {
    rename($tmp, $file);
    echo "SUCCESS: file written and syntax verified\n";
} else {
    unlink($tmp);
    echo "FAIL: syntax error - original preserved\n";
    echo $result . "\n";
}

unlink(__FILE__);
echo "Script deleted.\n";