<?php

use App\Core\Request;
use App\Repositories\PageRepository;
use App\Core\Database;

return function ($router) {

    $router->get('home', function (Request $request) {

        $repo = new PageRepository(Database::pdo());

        $page = $repo->findBySlug('home');

        return $page;
    });
};