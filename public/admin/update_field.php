<?php
require_once __DIR__ . "/../auth/core.php";
require '../../_init.php';

require_once '../../private/functions/authz.php';
mk_require_cap('submission.update');

$data = json_decode(file_get_contents("php://input"), true);

$id = (int)$data['id'];
$field = $data['field'];
$value = $data['value'];

$allowed = ['message'];

if (!in_array($field, $allowed)) {
    echo json_encode(['ok'=>false]);
    exit;
}

$db = mk_db();

// OLD
$old = $db->query("SELECT * FROM submissions WHERE id=$id")->fetch();

// UPDATE
$stmt = $db->prepare("UPDATE submissions SET $field=? WHERE id=?");
$stmt->execute([$value, $id]);

// LOG
logSubmissionAction($db, $id, 'field_update', [$field=>$old[$field]], [$field=>$value], $_SESSION['user_email']);

echo json_encode(['ok'=>true]);