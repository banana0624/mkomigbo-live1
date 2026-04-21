<?php
require '../../_init.php';

require_once '../../private/functions/authz.php';

mk_require_cap('submission.delete');

session_start();
require 'auth.php';
requireRole('admin');

require_once '../../private/functions/submission_actions.php';

$db = mk_db();

$data = json_decode(file_get_contents("php://input"), true);

$id = (int)$data['id'];

$old = getSubmissionById($db, $id);
if (!$old) {
    echo json_encode(['ok'=>false]);
    exit;
}

/* SAVE VERSION BEFORE DELETE */
saveSubmissionVersion($db, $id);

/* SOFT DELETE */
$stmt = $db->prepare("
    UPDATE submissions
    SET deleted_at = NOW(), deleted_by = ?
    WHERE id = ?
");
$stmt->execute([getActor(), $id]);

/* LOG */
logSubmissionAction(
    $db,
    $id,
    'soft_delete',
    $old,
    [],
    getActor()
);

echo json_encode(['ok'=>true]);