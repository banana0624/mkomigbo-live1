<?php
declare(strict_types=1);
// /home/mkomigbo/public_html/app/mkomigbo/private/tools/diag.php

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo "diag ok\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "SAPI: " . PHP_SAPI . "\n";
echo "FILE: " . __FILE__ . "\n";
echo "DIR: " . __DIR__ . "\n";
echo "URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
