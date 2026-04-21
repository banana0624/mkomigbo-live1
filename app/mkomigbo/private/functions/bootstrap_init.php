<?php
declare(strict_types=1);

/* =========================
   CORE BOOTSTRAP LOADER
   ========================= */

if (!defined('APP_ROOT')) {
    die('APP_ROOT not defined');
}

/* LOAD CORE FILES (ONLY REAL FILES) */

$requiredFiles = [
    'db.php',
    'helpers.php',
    'page_loader.php',
    'page_engine_safe.php',
    'page_engine_v2.php',
    'page_attachments.php',
    'contain.php',
];

foreach ($requiredFiles as $file) {
    $path = APP_ROOT . '/private/functions/' . $file;

    if (!file_exists($path)) {
        die("BOOTSTRAP ERROR: Missing file → " . $file);
    }

    require_once $path;
}

/* INITIALIZE SYSTEM */
function mk_initialize(): void
{
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Optional: set error mode for PDO globally if db connects here
}