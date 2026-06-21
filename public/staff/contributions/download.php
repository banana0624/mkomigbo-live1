<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
auth_require_role('staff');

require_once APP_ROOT . '/private/functions/contributions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    exit('Invalid contribution id.');
}

mk_contribution_stream_file_for_staff($id);