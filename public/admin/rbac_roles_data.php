<?php
require_once __DIR__ . "/../auth/core.php";
require_once __DIR__ . '/../../private/bootstrap.php';

header('Content-Type: application/json');

$db = mk_db();

/**
 * Get roles
 */
$rolesStmt = $db->query("SELECT id, name FROM roles");
$roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Get capabilities
 */
$capsStmt = $db->query("SELECT id, name FROM capabilities");
$caps = $capsStmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Map role → capabilities
 */
$resultRoles = [];

foreach ($roles as $role) {
    $stmt = $db->prepare("
        SELECT capability_id 
        FROM role_capabilities 
        WHERE role_id = ?
    ");
    $stmt->execute([$role['id']]);

    $assigned = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'capability_id'));

    $resultRoles[] = [
        'id' => $role['id'],
        'name' => $role['name'],
        'capabilities' => $assigned
    ];
}

echo json_encode([
    'roles' => $resultRoles,
    'capabilities' => $caps
]);