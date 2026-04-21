<?php

function mk_load_page_by_slug(string $slug): ?array
{
  try {
    $pdo = function_exists('db') ? db() : null;
    if (!$pdo) return null;

    $stmt = $pdo->prepare("
      SELECT *
      FROM pages
      WHERE slug = ?
      ORDER BY id DESC
      LIMIT 1
    ");

    $stmt->execute([$slug]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

  } catch (Throwable $e) {
    return null;
  }
}