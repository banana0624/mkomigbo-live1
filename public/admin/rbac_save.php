<?php
require_once __DIR__ . '/../../private/bootstrap.php';

header('Content-Type: application/json');

$db = mk_db();

$data = json_decode(file_get_contents("php://input"), true);

$roleId = (int)($data['roleId'] ?? 0);
$capId  = (int)($data['capId'] ?? 0);
$assigned = (bool)($data['assigned'] ?? false);

if ($roleId <= 0 || $capId <= 0) {
    echo json_encode(['ok' => false]);
    exit;
}

if ($assigned) {
    $stmt = $db->prepare("
        INSERT IGNORE INTO role_capabilities (role_id, capability_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$roleId, $capId]);
} else {
    $stmt = $db->prepare("
        DELETE FROM role_capabilities
        WHERE role_id = ? AND capability_id = ?
    ");
    $stmt->execute([$roleId, $capId]);
}

echo json_encode(['ok' => true]);