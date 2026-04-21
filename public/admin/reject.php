<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

$db = mk_db();

$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("UPDATE contributions SET status='rejected', reviewed_at=NOW(), reviewed_by=? WHERE id=?");
$stmt->execute([$adminId, $id]);

$log = $db->prepare("INSERT INTO moderation_logs (contribution_id, action, admin_user) VALUES (?, 'rejected', ?)");
$log->execute([$id, $adminId]);

header('Location: /admin/contributions.php');
exit;