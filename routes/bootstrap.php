<?php

declare(strict_types=1);


require_once __DIR__ . '/web.php';
require_once __DIR__ . '/RouteDispatcher.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

RouteDispatcher::dispatch($uri);