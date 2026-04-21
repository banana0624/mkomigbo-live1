<?php
declare(strict_types=1);

/* 🚨 MUST BE FIRST */
define('STAFF_LOGIN_PAGE', true);

require_once __DIR__ . '/../_init.php';

/* ---------------------------
 * SESSION
 * --------------------------- */
if (function_exists('mk__session_start')) {
    mk__session_start();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ---------------------------
 * HELPERS
 * --------------------------- */
function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): never {
    header('Location: ' . (function_exists('url_for') ? url_for($path) : $path));
    exit;
}

/* ---------------------------
 * AUTH
 * --------------------------- */
require_once PRIVATE_PATH . '/functions/auth.php';

/* ---------------------------
 * STATE
 * --------------------------- */
$return = $_POST['return'] ?? $_GET['return'] ?? '/staff/';
$return = '/' . ltrim($return, '/');

$email = '';
$error = '';

/* ---------------------------
 * HANDLE POST
 * --------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $res = mk_attempt_staff_login($email, $pass);

    if (!empty($res['ok'])) {
        session_regenerate_id(true);
        redirect_to($return);
    }

    $error = $res['error'] ?? 'Invalid email or password';
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Staff Login</title>
</head>
<body>

<h2>Staff Login</h2>

<?php if ($error): ?>
<div style="color:red;"><?= h($error) ?></div>
<?php endif; ?>

<form method="post">
<input type="hidden" name="return" value="<?= h($return) ?>">

<label>Email</label><br>
<input type="email" name="email" value="<?= h($email) ?>" required><br><br>

<label>Password</label><br>
<input type="password" name="password" required><br><br>

<button type="submit">Login</button>
</form>

</body>
</html>