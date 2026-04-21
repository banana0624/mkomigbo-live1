<?php
require_once __DIR__ . '/../../_init.php';

$db = mk_db();

$id = (int)($_GET['id'] ?? 0);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="timeline_'.$id.'.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, ['Date', 'Field', 'Old', 'New']);

$stmt = $db->prepare("
    SELECT snapshot, created_at
    FROM submission_versions
    WHERE submission_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$prev = null;

foreach ($rows as $row) {
    $curr = json_decode($row['snapshot'], true) ?? [];

    if ($prev !== null) {
        foreach ($curr as $key => $val) {
            $old = $prev[$key] ?? null;

            if ($old !== $val) {
                fputcsv($output, [
                    $row['created_at'],
                    $key,
                    $old,
                    $val
                ]);
            }
        }
    }

    $prev = $curr;
}

fclose($output);
exit;