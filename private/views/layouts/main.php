<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Mkomigbo'; ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }

        header,
        footer {
            background: #111;
            color: white;
            padding: 16px;
        }

        main {
            padding: 24px;
            background: white;
            max-width: 1000px;
            margin: 30px auto;
        }

        nav a {
            color: white;
            margin-right: 16px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<?php require PRIVATE_PATH . '/views/partials/header.php'; ?>

<main>
    <?php require $view; ?>
</main>

<?php require PRIVATE_PATH . '/views/partials/footer.php'; ?>

</body>
</html>