<?php
declare(strict_types=1);

/**
 * Compatibility shim: diagnostics/project_audit.php
 * Temporary wrapper so registry/run.php links don’t 500.
 *
 * You currently have: diagnostics/attachments_integrity_scan.php
 * This shim runs that tool until you restore/build a real project_audit tool.
 */

require __DIR__ . "/attachments_integrity_scan.php";
