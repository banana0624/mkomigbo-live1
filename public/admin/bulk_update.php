<?php
require_once __DIR__ . "/../auth/core.php";
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

require_once '../../private/functions/authz.php';
mk_require_cap('submission.bulk_update');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': CSRF_TOKEN
},

require __DIR__ . '/auth.php';
requireRole('admin');

$db = mk_db();

/* INPUT */
$data = json_decode(file_get_contents('php://input'), true);

$ids = $data['ids'] ?? [];
$status = $data['status'] ?? '';
$csrf = $data['csrf_token'] ?? '';

/* VALIDATION */
if (!$ids || !is_array($ids)) {
    echo json_encode(['success' => false, 'message' => 'No IDs provided']);
    exit;
}

if (!in_array($status, ['approved', 'rejected'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

if (!$csrf || $csrf !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF failed']);
    exit;
}

/* UPDATE */
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $db->prepare("
    UPDATE submissions
    SET status = ?
    WHERE id IN ($placeholders)
    AND status = 'pending'
");

$stmt->execute(array_merge([$status], $ids));

/* LOG */
$logStmt = $db->prepare("
    INSERT INTO moderation_logs (submission_id, action, admin_user)
    VALUES (?, ?, ?)
");

foreach ($ids as $id) {
    $logStmt->execute([
        $id,
        $status,
        $_SESSION['admin_email'] ?? 'unknown'
    ]);
}

echo json_encode([
    'success' => true,
    'message' => 'Bulk update successful'
]);