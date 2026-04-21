<?php
require 'auth.php';
require '../db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid request");
}

/* FETCH SUBMISSION */
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ?");
$stmt->execute([$id]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    die("Submission not found");
}

/* HANDLE UPDATE (EDIT + ACTION) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireRole('admin');

    $message = $_POST['message'] ?? '';
    $action = $_POST['action'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if (!in_array($action, ['approved', 'rejected'])) {
        die("Invalid action");
    }

    /* UPDATE MESSAGE (EDIT BEFORE APPROVE) */
    $stmt = $pdo->prepare("
        UPDATE submissions
        SET message = ?
        WHERE id = ?
    ");
    $stmt->execute([$message, $id]);

    /* UPDATE STATUS */
    $stmt = $pdo->prepare("
        UPDATE submissions
        SET status = ?
        WHERE id = ?
    ");
    $stmt->execute([$action, $id]);
    
    /* =========================
       AUTO-PUBLISH TO PAGES
    ========================= */
    
    if ($action === 'approved') {
    
        // Basic mapping (adjust as needed)
        $title = substr(strip_tags($message), 0, 80);
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
    
        // Example: map subject_area → subject_id (TEMP fallback)
        $subject_id = 1; // TODO: replace with real mapping table
    
        // Check if page already exists (by slug)
        $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        $existingPage = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($existingPage) {
    
            // UPDATE existing page
            $stmt = $pdo->prepare("
                UPDATE pages
                SET body = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$message, $existingPage['id']]);
    
        } else {
    
            // INSERT new page
            $stmt = $pdo->prepare("
                INSERT INTO pages (subject_id, title, slug, body, is_public, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$subject_id, $title, $slug, $message]);
        }
    }
    

    /* AUDIT LOG WITH NOTES */
    $stmt = $pdo->prepare("
        INSERT INTO moderation_logs (contribution_id, action, admin_user, notes)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $id,
        $action,
        $_SESSION['admin_username'] ?? 'unknown',
        $notes
    ]);

    header("Location: submissions.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submission Detail</title>
    
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

<a href="submissions.php">← Back</a>
<a href="logout.php" style="float:right;">Logout</a>

<h2>Submission #<?= $submission['id'] ?></h2>

<p><strong>Subject:</strong> <?= htmlspecialchars($submission['subject_area']) ?></p>
<p><strong>Type:</strong> <?= htmlspecialchars($submission['submission_type']) ?></p>
<p><strong>Status:</strong> <?= htmlspecialchars($submission['status']) ?></p>
<p><strong>Date:</strong> <?= $submission['created_at'] ?></p>

<hr>

<form method="POST">

    <h3>Edit Message (before approval)</h3>
    <textarea name="message" rows="8" cols="80"><?= htmlspecialchars($submission['message']) ?></textarea>

    <h3>Moderation Notes</h3>
    <textarea name="notes" rows="4" cols="80" placeholder="Internal notes (not public)"></textarea>

    <br><br>

    <?php if ($submission['status'] === 'pending'): ?>
        <button name="action" value="approved">Approve</button>
        <button name="action" value="rejected">Reject</button>
    <?php else: ?>
        <p><em>Already processed</em></p>
    <?php endif; ?>

</form>

</body>
</html>