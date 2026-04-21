<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

mk_require_staff_login();

/**
 * /public/staff/page-files/upload.php
 * Compatibility endpoint: delegates to the canonical upload handler.
 *
 * Why include instead of redirect?
 * - Redirects can break multipart/form-data uploads.
 */

require_once __DIR__ . '/../pages/attachments_upload.php';