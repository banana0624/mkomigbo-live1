<?php

class Page
{
    public static function findBySlug(string $slug)
    {
        $stmt = db()->prepare("SELECT * FROM pages WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);

        return $stmt->fetch();
    }
}