<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <title>
        <?= htmlspecialchars($page['title'] ?? 'Untitled') ?>
    </title>

</head>
<body>

    <h1>
        <?= htmlspecialchars($page['title'] ?? '') ?>
    </h1>

    <div>
        <?= $page['body'] ?? 'No content yet.' ?>
    </div>

</body>
</html>