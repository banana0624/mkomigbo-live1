<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

auth_require_role('staff');

/**
 * /public/staff/page-files/save.php
 * Compatibility endpoint: delegates to the canonical external-link add handler.
 */

require_once __DIR__ . '/../pages/attachments_external_add.php';
