<?php
require_once __DIR__ . '/../config/bootstrap.php';

$error = get_flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        log_action($pdo, (int)$user['id'], 'login');
        if ($user['role'] === 'admin') {
            redirect('/admin/index.php');
        }
        redirect('/staff/index.php');
    } else {
        flash('error', 'Invalid credentials');
        redirect('/auth/login.php');
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login - Hotel Reservation</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Login</h2>
            <p>Sign in to your account</p>
        </div>
        <?php if ($error): ?>
            <div class="flash"><?php echo safe_output($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit" style="width:100%; margin-top:10px;">Login</button>
        </form>
        <div class="auth-footer">
            Don't have an account?
            <a href="<?php echo BASE_PATH; ?>/auth/register.php">Register here</a>
        </div>
    </div>
</div>
</body>
</html>
