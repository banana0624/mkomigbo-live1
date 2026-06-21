<?php

namespace App\Services;

use App\Repositories\PageRepository;

class PageService
{
    public function __construct(
        private PageRepository $repo
    ) {}

    public function getPage(string $slug): ?array
    {
        return $this->repo->findBySlug($slug);
    }
}