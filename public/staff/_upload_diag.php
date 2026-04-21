<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

mk_require_staff_login();

header('Content-Type: text/plain; charset=utf-8');

echo "upload_diag=OK\n";
echo "staff_user_id=" . (int)($_SESSION['staff_user_id'] ?? 0) . "\n";
echo "staff_email=" . (string)($_SESSION['staff_email'] ?? '') . "\n";
