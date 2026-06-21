<?php
require_once __DIR__ . "/../auth/core.php";

ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "STEP 1\n";

require_once __DIR__ . '/../../_init.php';

echo "STEP 2 (after init)\n";

if (function_exists('mk_initialize')) {
    echo "mk_initialize exists\n";
} else {
    echo "mk_initialize missing\n";
}

if (function_exists('mk_public_bootstrap')) {
    echo "mk_public_bootstrap exists\n";
} else {
    echo "mk_public_bootstrap missing\n";
}

echo "STEP 3 before DB\n";

$db = mk_db();

echo "STEP 4 DB OK\n";

echo $db->query("SELECT DATABASE()")->fetchColumn();