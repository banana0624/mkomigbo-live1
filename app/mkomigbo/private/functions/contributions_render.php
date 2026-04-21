<?php
declare(strict_types=1);

function mk_get_approved_contributions(PDO $db, string $pagePath): array
{
    $stmt = $db->prepare("
        SELECT contributor_name, title, message_text, created_at
        FROM contributions
        WHERE status = 'approved'
        AND page_path = ?
        ORDER BY created_at ASC
    ");

    $stmt->execute([$pagePath]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}