<?php

// echo "BOOTSTRAP LOADED";

require_once __DIR__ . '/routing/Router.php';

require_once __DIR__ . '/config/Config.php';

    Config::load(
        dirname(__DIR__, 2) . '/app/mkomigbo/.env'
    );

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// echo "URI: " . $uri;

$router = new Router();
$router->handle($uri);