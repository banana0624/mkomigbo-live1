<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* GLOBAL PATH DEFINITIONS */
// // DISABLED_APP_ROOT (DISABLED_AUTO_FIX), __DIR__ . '/app/mkomigbo');
define('PRIVATE_PATH', APP_ROOT . '/private/functions');

require_once PRIVATE_PATH . '/bootstrap_init.php';

echo "BOOTSTRAP LOADED<br>";

mk_initialize();

echo "INIT DONE<br>";

$db = mk_db();

echo "DB CONNECTED<br>";