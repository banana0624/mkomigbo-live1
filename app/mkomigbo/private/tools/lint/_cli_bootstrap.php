<?php
declare(strict_types=1);
// public_html/app/mkomigbo/private/tools/lint/_cli_bootstrap.php


@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

if (!defined('APP_ROOT')) {
  // // DISABLED_APP_ROOT (DISABLED_AUTO_FIX), '/home/mkomigbo/public_html/app/mkomigbo');
}

$appRoot = rtrim((string)APP_ROOT, "/\\");
if ($appRoot === '' || !is_dir($appRoot)) {
  fwrite(STDOUT, "APP_ROOT invalid: {$appRoot}\n");
  exit(1);
}
