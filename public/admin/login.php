<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['flash'])) {
    echo '<div class="flash flash-success">' . htmlspecialchars($_SESSION['flash']) . '</div>';
    unset($_SESSION['flash']);
}

if (!empty($_SESSION['error'])) {
    echo '<div class="flash flash-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

$db = mk_db();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'All fields are required';
    } else {

        $stmt = $db->prepare("
            SELECT id, email, password_hash, role 
            FROM staff_users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {

            // Regenerate session (security)
            session_regenerate_id(true);

            $_SESSION['admin_user_id'] = (int)$user['id'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_role'] = $user['role'];

            // Optional: readable name
            $_SESSION['admin_username'] = $user['email'];

            header('Location: /public/admin/submissions.php');
            exit;

        } else {
            $error = 'Invalid credentials';
        }
    }
}
?>

<!doctype html>
<html>
<head>
    <title>Admin Login</title>
    
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

<h2>Admin Login</h2>

<?php if ($error): ?>
<p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>

</body>
</html>