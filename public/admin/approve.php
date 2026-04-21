<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

if (!empty($_SESSION['flash'])) {
    echo '<div class="flash flash-success">' . htmlspecialchars($_SESSION['flash']) . '</div>';
    unset($_SESSION['flash']);
}

if (!empty($_SESSION['error'])) {
    echo '<div class="flash flash-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

$db = mk_db();

$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("UPDATE contributions SET status='approved', reviewed_at=NOW(), reviewed_by=? WHERE id=?");
$stmt->execute([$adminId, $id]);

$log = $db->prepare("INSERT INTO moderation_logs (contribution_id, action, admin_user) VALUES (?, 'approved', ?)");
$log->execute([$id, $adminId]);

header('Location: /admin/contributions.php');
exit;