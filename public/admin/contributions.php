<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

if (!empty($_SESSION['flash'])) {
    echo '<div class="flash flash-success">' . htmlspecialchars($_SESSION['flash']) . '</div>';
    unset($_SESSION['flash']);
}

if (!empty($_SESSION['error'])) {
    echo '<div class="flash flash-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

$db = mk_db();

$stmt = $db->query("SELECT * FROM contributions WHERE status='pending' ORDER BY created_at DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html>
<head>
    <title>Moderation Queue</title>
    
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

<h1>Pending Contributions</h1>

<table border="1" cellpadding="8">
<tr>
    <th>ID</th>
    <th>Page</th>
    <th>Subject</th>
    <th>Name</th>
    <th>Actions</th>
</tr>

<?php foreach ($items as $row): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['page_path']) ?></td>
    <td><?= htmlspecialchars($row['subject_area']) ?></td>
    <td><?= htmlspecialchars($row['contributor_name']) ?></td>
    <td>
        <a href="/admin/view.php?id=<?= $row['id'] ?>">View</a> |
        <a href="/admin/approve.php?id=<?= $row['id'] ?>">Approve</a> |
        <a href="/admin/reject.php?id=<?= $row['id'] ?>">Reject</a>
    </td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>