<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "STEP 1 OK<br>";

try {
    echo "STEP 2 BEFORE INIT<br>";

    require_once __DIR__ . '/_init.php';

    echo "STEP 3 AFTER INIT<br>";

    $pdo = mk_db();
    echo "DB OK<br>";

    echo $pdo->query("SELECT DATABASE()")->fetchColumn();

} catch (Throwable $e) {
    echo "<pre>";
    echo "FATAL ERROR:\n";
    echo $e->getMessage() . "\n\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}