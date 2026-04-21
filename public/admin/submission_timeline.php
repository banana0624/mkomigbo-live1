<?php

require_once '../../private/functions/authz.php';
mk_require_cap('submission.audit_view');

/* FETCH VERSIONS */
$stmt = $db->prepare("
    SELECT snapshot, created_at
    FROM submission_versions
    WHERE submission_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$versions = [];
$prev = null;

foreach ($rows as $row) {
    $curr = json_decode($row['snapshot'], true) ?? [];

    $diff = [];

    if ($prev !== null) {
        foreach ($curr as $key => $value) {
            $oldVal = $prev[$key] ?? null;

            if ($oldVal !== $value) {
                $diff[$key] = [
                    'old' => $oldVal,
                    'new' => $value
                ];
            }
        }
    }

    $versions[] = [
        'created_at' => $row['created_at'],
        'diff' => $diff,
        'full' => $curr
    ];

    $prev = $curr;
}