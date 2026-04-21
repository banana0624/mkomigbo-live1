<?php
declare(strict_types=1);

require '../../_init.php';
session_start();
require 'auth.php';
requireRole('admin');

require_once '../../private/functions/submission_actions.php';

require_once '../../private/functions/authz.php';
mk_require_cap('submission.restore');

$db = mk_db();

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid ID']);
    exit;
}

/* RESTORE */
$stmt = $db->prepare("
    UPDATE submissions
    SET deleted_at = NULL,
        deleted_by = NULL
    WHERE id = ?
      AND deleted_at IS NOT NULL
");

$stmt->bind_param("i", $id);
$stmt->execute();

logSubmissionAction(
    $db,
    $id,
    'restore',
    [],
    [],
    getActor()
);

echo json_encode(['ok' => true]);