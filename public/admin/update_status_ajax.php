<?php
require_once __DIR__ . "/../auth/core.php";
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/auth.php';
requireRole('admin');

header('Content-Type: application/json');

$db = mk_db();

/* CSRF CHECK */
$data = json_decode(file_get_contents("php://input"), true);

if (
    !isset($data['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])
) {
    echo json_encode(['success' => false, 'message' => 'CSRF failed']);
    exit;
}

$id = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';

$allowedStatuses = ['approved', 'rejected'];

if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

/* CHECK CURRENT STATUS */
$stmt = $db->prepare("SELECT status FROM submissions WHERE id = ?");
$stmt->execute([$id]);
$current = $stmt->fetchColumn();

if ($current !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Already moderated']);
    exit;
}

/* UPDATE */
$stmt = $db->prepare("UPDATE submissions SET status = ? WHERE id = ?");
$stmt->execute([$status, $id]);

/* LOG */
$stmt = $db->prepare("
    INSERT INTO moderation_logs (submission_id, action, admin_user)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $id,
    $status,
    $_SESSION['admin_email'] ?? 'unknown'
]);

echo json_encode([
    'success' => true,
    'status' => $status,
    'message' => 'Updated successfully'
]);