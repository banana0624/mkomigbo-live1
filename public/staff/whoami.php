<?php
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

if (function_exists('mk_staff_session_start')) {
  mk_staff_session_start();
} elsemk_staff_session_start();

header('Content-Type: text/plain; charset=utf-8');

echo "session_name=" . session_name() . PHP_EOL;
echo "session_id=" . session_id() . PHP_EOL;
echo "cookie_present=" . (isset($_COOKIE[session_name()]) ? 'YES' : 'NO') . PHP_EOL;
echo "cookie_value=" . (string)($_COOKIE[session_name()] ?? '') . PHP_EOL;
echo "staff_user_id=" . (int)($_SESSION['staff_user_id'] ?? 0) . PHP_EOL;
echo "staff_session_version=" . (int)($_SESSION['staff_session_version'] ?? 0) . PHP_EOL;
echo "staff_email=" . (string)($_SESSION['staff_email'] ?? '') . PHP_EOL;

if (function_exists('mk_staff_session_version_valid')) {
  echo "session_version_valid=" . (mk_staff_session_version_valid() ? 'YES' : 'NO') . PHP_EOL;
}
