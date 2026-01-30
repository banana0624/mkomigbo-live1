<?php
declare(strict_types=1);

/**
 * /private/tools/staff-tools/scan_auto.php
 *
 * Same as scan_project.php but forces render=auto.
 */

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$_GET['render'] = 'auto';

require __DIR__ . '/scan_project.php';
