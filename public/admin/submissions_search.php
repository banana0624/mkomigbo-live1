<?php
require_once __DIR__ . "/../auth/core.php";
declare(strict_types=1);

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../_init.php';

header('Content-Type: application/json');

$pdo = db();

try {
    $pdo = db();

    $q      = trim($_GET['q'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $type   = trim($_GET['type'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));

    $limit  = 20;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(message LIKE :q OR subject_area LIKE :q)";
        $params[':q'] = "%{$q}%";
    }

    if ($status !== '') {
        $where[] = "status = :status";
        $params[':status'] = $status;
    }

    if ($type !== '') {
        $where[] = "submission_type = :type";
        $params[':type'] = $type;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions {$whereSql}");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT id, subject_area, submission_type, status, message, created_at
        FROM submissions
        {$whereSql}
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode([
        'ok' => true,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;

