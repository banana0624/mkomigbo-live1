<?php
require_once __DIR__ . "/../auth/core.php";
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/auth.php';
auth_require_role('admin');

header('Content-Type: application/json');

try {
    $db = mk_db();

    // ---- TOTALS ----
    $totals = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(status='pending') as pending,
            SUM(status='approved') as approved,
            SUM(status='rejected') as rejected
        FROM submissions
        WHERE deleted_at IS NULL
    ")->fetch(PDO::FETCH_ASSOC);

    // ---- DAILY TREND ----
    $trendStmt = $db->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM submissions
        WHERE deleted_at IS NULL
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $trend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

    // ---- STATUS DISTRIBUTION ----
    $statusStmt = $db->query("
        SELECT status, COUNT(*) as count
        FROM submissions
        WHERE deleted_at IS NULL
        GROUP BY status
    ");
    $statusDist = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'totals' => $totals,
        'trend' => $trend,
        'status' => $statusDist
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}