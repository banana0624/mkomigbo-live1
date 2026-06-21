<?php
declare(strict_types=1);

define('STAFF_LOGIN_PAGE', true);

require_once __DIR__ . '/_init.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {

        $error = 'All fields required';

    } else {

        $pdo = staff_pdo();

        if (!$pdo instanceof PDO) {

            $error = 'Database connection failed';

        } else {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    email,
                    password_hash,
                    role,
                    session_version
                FROM staff_users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $user &&
                isset($user['password_hash']) &&
                password_verify($pass, (string)$user['password_hash'])
            ) {

                session_regenerate_id(true);

                $_SESSION['staff_user_id'] = (int)$user['id'];

                $_SESSION['staff_session_version'] = (int)$user['session_version'];
                $_SESSION['staff_user'] = [
                    'id'    => (int)$user['id'],
                    'email' => (string)$user['email'],
                    'role'  => (string)($user['role'] ?? 'staff'),
                ];

                if (function_exists('auth_login')) {

                    try {

                        auth_login(
                            (int)$user['id'],
                            'staff',
                            [
                                'capabilities' => [],
                            ]
                        );

                    } catch (Throwable $e) {
                    }
                }

                $return =
                    $_GET['return']
                    ?? $_POST['return']
                    ?? '/staff/';

                if (
                    !is_string($return) ||
                    strpos($return, '/staff/') !== 0
                ) {
                    $return = '/staff/';
                }

                header('Location: ' . $return, true, 302);
                exit;
            }

            $error = 'Invalid credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Staff Login</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/lib/css/ui.css">
<link rel="stylesheet" href="/lib/css/staff.css">
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg,#f8f9fb);">

<div style="width:100%;max-width:420px;background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:20px;padding:32px;box-shadow:0 8px 30px rgba(0,0,0,.08);"><div style="text-align:center;margin-bottom:24px;"><img src="/assets/images/logos/mk-logo.png" width="48" height="48" style="border-radius:12px;border:1px solid var(--border,#e5e7eb);" alt="Mkomigbo"><h2 style="margin:12px 0 4px;font-size:1.3rem;font-weight:900;">Staff Login</h2><p style="margin:0;color:var(--muted,#6b7280);font-size:.9rem;">Mkomigbo Staff Area</p></div>

<?php if ($error !== ''): ?>
    <p style="color:red;">
        <?= h($error) ?>
    </p>
<?php endif; ?>

<form method="post">

    <input
        type="hidden"
        name="return"
        value="<?= h((string)($_GET['return'] ?? '/staff/')) ?>"
    >

    <input
        type="email"
        name="email"
        placeholder="Email"
        value="<?= h($email) ?>"
        required
    >

    <br><br>

    <input
        type="password"
        name="password"
        placeholder="Password"
        required
    >

    <br><br>

    <button type="submit">
        Login
    </button>

</form>

</div></body>
</html>