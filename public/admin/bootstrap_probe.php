<?php
require_once __DIR__ . "/../auth/core.php";

ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "A\n";

require_once __DIR__ . '/../../_init.php';

echo "B\n";

var_dump(function_exists('mk_initialize'));
var_dump(function_exists('mk_public_bootstrap'));

echo "C\n";