<?php
declare(strict_types=1);

/**
 * /public/contributors/_init.php
 * Module shim for Contributors.
 *
 * Contract:
 * - Delegates to /public/_init.php (single source of truth)
 * - Does not define constants, does not scan for initialize.php
 * - Keeps module entrypoints consistent and audit-clean
 */

require_once dirname(__DIR__) . '/_init.php';
