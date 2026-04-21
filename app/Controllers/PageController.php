<?php

require_once __DIR__ . '/../Models/Page.php';

class PageController
{
    public function show(string $slug): void
    {
        $page = Page::findBySlug($slug);

        if (!$page) {
            http_response_code(404);
            echo "<h1>404 - Page Not Found</h1>";
            return;
        }

        require dirname(__DIR__, 2) . '/views/page.php';
    }
}