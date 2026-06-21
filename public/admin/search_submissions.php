<?php
require_once __DIR__ . "/../auth/core.php";
declare(strict_types=1);

require_once __DIR__ . '/../_init.php';

header('Content-Type: application/json');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/auth.php';
auth_require_role('admin');

headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': CSRF_TOKEN
},

$db = mk_db();

/* INPUT */
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM submissions WHERE 1=1";
$params = [];

if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
}

if ($search !== '') {
    $sql .= " AND message LIKE ?";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC LIMIT 50";

$stmt = $db->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $rows
]);