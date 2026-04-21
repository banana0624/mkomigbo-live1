<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../app/Core/Router.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/**
 * SINGLE ENTRY — NO DUPLICATION
 */
Router::dispatch($uri);