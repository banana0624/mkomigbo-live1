<?php
require_once __DIR__ . "/../auth/core.php";
declare(strict_types=1);

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../_init.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/auth.php';
auth_require_role('admin');

$db = mk_db();

$title = "Moderation Logs";
require __DIR__ . '/layout.php';

/* FLASH MESSAGES */
if (!empty($_SESSION['flash'])) {
    echo '<div class="flash flash-success">' . htmlspecialchars($_SESSION['flash']) . '</div>';
    unset($_SESSION['flash']);
}

if (!empty($_SESSION['error'])) {
    echo '<div class="flash flash-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

$stmt = $db->query("
    SELECT * FROM moderation_logs
    ORDER BY created_at DESC
    LIMIT 50
");

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Moderation Activity Logs</h2>

<a href="submissions.php">Back</a> |
<a href="logout.php">Logout</a>

<table>
<tr>
    <th>ID</th>
    <th>Submission ID</th>
    <th>Action</th>
    <th>Admin</th>
    <th>Date</th>
</tr>

<?php foreach ($logs as $log): ?>
<tr>
    <td><?= $log['id'] ?></td>
    <td><?= $log['submission_id'] ?></td>

    <td>
        <span class="badge <?= $log['action'] ?>">
            <?= htmlspecialchars($log['action']) ?>
        </span>
    </td>

    <td><?= htmlspecialchars($log['admin_user']) ?></td>
    <td><?= $log['created_at'] ?></td>
</tr>
<?php endforeach; ?>

</table>

<?php require __DIR__ . '/layout_footer.php'; ?>