<?php
require_once __DIR__ . '/app/mkomigbo/private/assets/initialize.php';

try {
    $pdo = db();
    echo "DB CONNECT OK";
} catch (Throwable $e) {
    echo $e->getMessage();
}