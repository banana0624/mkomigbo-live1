<?php
require_once __DIR__ . "/../auth/core.php";
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

auth_require_role('staff');

if (!ob_get_level()) { @ob_start(); }

header('Content-Type: text/plain; charset=utf-8');

if (!function_exists('db')) { http_response_code(500); echo "no_db()\n"; exit; }

try {
  $pdo = db();
  if (!$pdo instanceof PDO) { http_response_code(500); echo "db_not_pdo\n"; exit; }

  $st = $pdo->prepare("
    INSERT INTO staff_audit_log
      (ts, staff_user_id, event, ip, user_agent, uri, context_json)
    VALUES
      (NOW(), 0, 'web_probe', :ip, :ua, :uri, :ctx)
  ");
  $st->execute([
    ':ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
    ':ua'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ':uri' => $_SERVER['REQUEST_URI'] ?? null,
    ':ctx' => json_encode(['t' => time()], JSON_UNESCAPED_SLASHES),
  ]);

  echo "web_probe_ok\n";
} catch (Throwable $e) {
  http_response_code(500);
  echo "web_probe_err: " . $e->getMessage() . "\n";
}
