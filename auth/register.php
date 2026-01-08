<?php
require_once __DIR__ . '/../config/bootstrap.php';

$error = get_flash('error');
$success = get_flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    if (!in_array($role, ['admin', 'staff'], true)) {
        flash('error', 'Invalid role selected.');
        redirect('/auth/register.php');
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        flash('error', 'Email already registered.');
        redirect('/auth/register.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
        ->execute([$name, $email, $hash, $role]);
    $userId = (int)$pdo->lastInsertId();
    log_action($pdo, $userId, "registered as {$role}");
    flash('success', 'Account created, you can now login.');
    redirect('/auth/login.php');
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Register - Hotel Reservation</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Register as Admin or Customer</p>
        </div>
        <?php if ($error): ?>
            <div class="flash"><?php echo safe_output($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="flash"><?php echo safe_output($success); ?></div>
        <?php endif; ?>
        <form method="post">
            <label>Name</label>
            <input type="text" name="name" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required minlength="6">
            <label>Role</label>
            <select name="role">
                <option value="admin">Admin</option>
                <option value="staff" selected>Customer</option>
            </select>
            <button type="submit" style="width:100%; margin-top:10px;">Register</button>
        </form>
        <div class="auth-footer">
            Already have an account?
            <a href="<?php echo BASE_PATH; ?>/auth/login.php">Back to login</a>
        </div>
    </div>
</div>
</body>
</html>
