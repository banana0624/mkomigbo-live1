<?php
require_once __DIR__ . "/../auth/core.php";
require_once __DIR__ . '/../_init.php';

$pdo = mk_db();

echo "DB: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "<br>";

$count = $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();

echo "Submissions count: " . $count;