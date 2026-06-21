<?php
require_once __DIR__ . "/../auth/core.php";
require_once __DIR__ . '/../../private/bootstrap.php';

header('Content-Type: application/json');

$db = mk_db();

$data = json_decode(file_get_contents("php://input"), true);
$name = trim($data['name'] ?? '');

if ($name === '') {
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $db->prepare("INSERT IGNORE INTO roles (name) VALUES (?)");
$stmt->execute([$name]);

echo json_encode(['ok' => true]);