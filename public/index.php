<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo '<pre>';

try {

    require_once __DIR__ . '/../private/assets/initialize.php';

    echo "INIT OK\n";

    require_once __DIR__ . '/../routes/web.php';

    echo "ROUTES OK\n";

} catch (Throwable $e) {

    echo "FATAL ERROR\n\n";

    echo "Message:\n";
    echo $e->getMessage() . "\n\n";

    echo "File:\n";
    echo $e->getFile() . "\n\n";

    echo "Line:\n";
    echo $e->getLine() . "\n\n";

    echo "Trace:\n";
    echo $e->getTraceAsString();
}

echo '</pre>';