<?php
declare(strict_types=1);

/**
 * Submission Actions + Audit Logging
 */

if (!function_exists('getSubmissionById')) {
    function getSubmissionById(PDO $db, int $id): ?array {
        $stmt = $db->prepare("SELECT * FROM submissions WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('logSubmissionAction')) {
    function logSubmissionAction(
        PDO $db,
        int $id,
        string $action,
        array $old = [],
        array $new = [],
        string $actor = 'system'
    ): void {
        $stmt = $db->prepare("
            INSERT INTO submission_audit_log
            (submission_id, action, old_value, new_value, actor)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $action,
            json_encode($old, JSON_UNESCAPED_UNICODE),
            json_encode($new, JSON_UNESCAPED_UNICODE),
            $actor
        ]);
    }
}

if (!function_exists('getActor')) {
    function getActor(): string {
        return $_SESSION['admin_email']
            ?? $_SESSION['user_email']
            ?? 'system';
    }
}

function saveSubmissionVersion(PDO $db, int $id): void {
    $stmt = $db->prepare("SELECT * FROM submissions WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) return;

    $stmt = $db->prepare("
        INSERT INTO submission_versions (submission_id, snapshot)
        VALUES (?, ?)
    ");

    $stmt->execute([
        $id,
        json_encode($row, JSON_UNESCAPED_UNICODE)
    ]);
}