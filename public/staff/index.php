<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

echo "<h1>Staff Area Online</h1>";

echo "<pre>";

echo "SESSION ACTIVE: ";
echo session_status() === PHP_SESSION_ACTIVE ? "YES\n" : "NO\n";

echo "STAFF USER ID: ";
echo $_SESSION['staff_user_id'] ?? 'NONE';
echo "\n";

echo "</pre>";