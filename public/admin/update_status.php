<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

require_once '../../private/functions/authz.php';
mk_require_cap('submission.update');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/auth.php';
requireRole('admin');

require_once __DIR__ . '/../../private/functions/submission_actions.php';

header('Content-Type: application/json');

$db = mk_db();

/* CSRF */
if (
    !isset($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')
) {
    echo json_encode(['ok' => false, 'message' => 'CSRF failed']);
    exit;
}

/* INPUT */
$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id || !in_array($status, ['approved','rejected'], true)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid input']);
    exit;
}

/* FETCH OLD */
$oldRow = getSubmissionById($db, $id);

if (!$oldRow) {
    echo json_encode(['ok' => false, 'message' => 'Not found']);
    exit;
}

if ($oldRow['status'] !== 'pending') {
    echo json_encode(['ok' => false, 'message' => 'Already processed']);
    exit;
}

/* UPDATE */
$stmt = $db->prepare("SELECT * FROM submissions WHERE id=?");
$stmt->execute([$id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("
    INSERT INTO submission_versions (submission_id, snapshot)
    VALUES (?, ?)
");
$stmt->execute([
    $id,
    json_encode($current)
]);

/* AUDIT LOG */
logSubmissionAction(
    $db,
    $id,
    'status_update',
    ['status' => $oldRow['status']],
    ['status' => $status],
    getActor()
);

/* OPTIONAL: keep legacy moderation log */
$stmt = $db->prepare("
    INSERT INTO moderation_logs (submission_id, action, admin_user)
    VALUES (?, ?, ?)
");
$stmt->execute([
    $id,
    $status,
    getActor()
]);

echo json_encode([
    'ok' => true,
    'status' => $status,
    'message' => 'Updated successfully'
]);
exit;