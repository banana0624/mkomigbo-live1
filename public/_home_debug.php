<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/_init.php';

echo "BOOT OK<br>";

$pdo = db();
echo "DB OK<br>";

echo "Functions check:<br>";

$functions = ['url_for', 'h', 'mk_db'];

foreach ($functions as $fn) {
    echo $fn . ': ' . (function_exists($fn) ? 'YES' : 'NO') . "<br>";
}

echo "DONE";