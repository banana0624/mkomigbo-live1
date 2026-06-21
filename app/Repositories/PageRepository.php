<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class PageRepository
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pages WHERE slug = :slug LIMIT 1"
        );

        $stmt->execute([
            ':slug' => $slug
        ]);

        $page = $stmt->fetch();

        return $page ?: null;
    }
}