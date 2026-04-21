<?php
declare(strict_types=1);

if (!empty($_SESSION['flash'])) {
    echo '<div class="flash flash-success">' . htmlspecialchars($_SESSION['flash']) . '</div>';
    unset($_SESSION['flash']);
}

if (!empty($_SESSION['error'])) {
    echo '<div class="flash flash-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

require_once __DIR__ . '/_auth.php';

$db = mk_db();

$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM contributions WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    exit('Not found');
}
?>

<!doctype html>
<html>
<head>
    <title>View Contribution</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 20px;
        }
        
        h2 {
            margin-bottom: 10px;
        }
        
        table {
            background: #fff;
            border-collapse: collapse;
            width: 100%;
        }
        
        th {
            background: #222;
            color: #fff;
        }
        
        td, th {
            padding: 10px;
            text-align: left;
        }
        
        a {
            margin-right: 10px;
            text-decoration: none;
            color: #007BFF;
        }
        
        button {
            padding: 5px 10px;
            cursor: pointer;
        }
        
        form {
            display: inline;
        }
    </style>
</head>
<body>

<h2><?= htmlspecialchars($row['title']) ?></h2>

<p><strong>Page:</strong> <?= htmlspecialchars($row['page_path']) ?></p>
<p><strong>Subject:</strong> <?= htmlspecialchars($row['subject_area']) ?></p>

<hr>

<pre><?= htmlspecialchars($row['message_text']) ?></pre>

<hr>

<a href="/admin/approve.php?id=<?= $row['id'] ?>">Approve</a> |
<a href="/admin/reject.php?id=<?= $row['id'] ?>">Reject</a>

</body>
</html>