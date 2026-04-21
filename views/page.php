<?php
declare(strict_types=1);

/**
 * GLOBAL HELPERS (SAFE)
 */
if (!function_exists('h')) {
    function h(string $v): string {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * EXPECTED: $page array from controller
 */
$title = $page['title'] ?? 'Untitled';
$body  = $page['body'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= h($title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<main style="max-width:800px;margin:40px auto;font-family:sans-serif;">

    <h1><?= h($title) ?></h1>

    <div>
        <?= $body ?>
    </div>

</main>

</body>
</html>