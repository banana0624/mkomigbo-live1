<?php

declare(strict_types=1);

if (defined('MK_APP_INITIALIZED')) {
    return;
}

define('MK_APP_INITIALIZED', true);

if (!defined('APP_ROOT')) {
    // optionally log or throw
    throw new RuntimeException("APP_ROOT must be defined by bootstrap");
}
define('PRIVATE_PATH', APP_ROOT . '/private');
define('PUBLIC_PATH', APP_ROOT . '/public');

error_reporting(E_ALL);
ini_set('display_errors', '1');

function url_for(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}