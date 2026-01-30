<?php
declare(strict_types=1);

/**
 * /public/download.php
 * Central "force download" wrapper for /public/media.php.
 *
 * Supports:
 *   /download.php?subject=&page=&file=&scope=private|public
 *   /download.php?s=&p=&f=&scope=private|public
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/_init.php';

/* Force download semantics for both param styles */
$_GET['dl'] = '1';
$_GET['in'] = 0;

require __DIR__ . '/media.php';
