<?php
declare(strict_types=1);
// /home/mkomigbo/public_html/app/mkomigbo/private/tools/subjects_diag.php

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!defined('APP_ROOT')) {
  // __DIR__ = .../app/mkomigbo/private/tools
  // // DISABLED_APP_ROOT (DISABLED_AUTO_FIX), dirname(__DIR__, 2)); // .../app/mkomigbo
}

$init = APP_ROOT . '/private/assets/initialize.php';
if (!is_file($init)) {
  echo "FAIL: initialize.php missing at {$init}\n";
  exit(1);
}
require_once $init;

header('Content-Type: text/plain; charset=utf-8');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo "subjects diag start\n";
echo "FILE: " . __FILE__ . "\n";
echo "DIR: " . __DIR__ . "\n";

echo "REQUEST_ID: " . (defined('REQUEST_ID') ? REQUEST_ID : 'n/a') . "\n";
echo "APP_ROOT: " . (defined('APP_ROOT') ? APP_ROOT : 'n/a') . "\n";
echo "SITE_ROOT: " . (defined('SITE_ROOT') ? SITE_ROOT : 'n/a') . "\n";
echo "PUBLIC_PATH: " . (defined('PUBLIC_PATH') ? PUBLIC_PATH : 'n/a') . "\n";
echo "WWW_ROOT: " . (defined('WWW_ROOT') ? WWW_ROOT : 'n/a') . "\n";
echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'n/a') . "\n";
echo "APP_DEBUG: " . (defined('APP_DEBUG') ? (APP_DEBUG ? '1' : '0') : 'n/a') . "\n";
echo "APP_DEBUG_OVERRIDE: " . (defined('APP_DEBUG_OVERRIDE') ? (APP_DEBUG_OVERRIDE ? '1' : '0') : 'n/a') . "\n";
echo "PHP: " . PHP_VERSION . "\n";

/* Check if APP_ROOT looks sane and the private assets directory exists (without hardcoding the full file path string) */
$assetsDir = (defined('APP_ROOT') && is_string(APP_ROOT) && APP_ROOT !== '')
  ? (APP_ROOT . '/private/assets')
  : '';

if ($assetsDir !== '') {
  echo "PRIVATE ASSETS DIR: {$assetsDir}\n";
  echo "PRIVATE ASSETS DIR exists: " . (is_dir($assetsDir) ? 'YES' : 'NO') . "\n";
}

/* DB */
$dbOk = 'n/a';
$dbName = 'n/a';
try {
  if (function_exists('db')) {
    $pdo = db();
    if ($pdo instanceof PDO) {
      $dbOk = '1';
      $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
      $dbName = $dbName !== '' ? $dbName : '(empty)';
    } else {
      $dbOk = '0';
    }
  } else {
    $dbOk = '0 (db() missing)';
  }
} catch (Throwable $e) {
  $dbOk = '0 (exception)';
}

echo "DB OK: {$dbOk}\n";
echo "DB NAME: {$dbName}\n";
echo "diag done\n";
