<?php
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';
$success = '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

$stmt = $pdo->prepare("SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = 'Invalid or expired reset link. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $reset['user_id']]);

        $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $stmt->execute([$token]);

        logActivity('password_reset', 'Password reset for user: ' . $reset['email']);
        header('Location: login.php?reset=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Campus Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <a href="login.php" class="btn btn-secondary btn-sm btn-block" style="margin-bottom: 20px;">← Back to Login</a>

            <div class="auth-header">
                <h1>Reset Password</h1>
                <p>Enter your new password</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($reset): ?>
            <form method="POST" data-validate>
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Reset Password</button>
            </form>
            <?php else: ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <a href="forgot-password.php" class="btn btn-primary btn-block">Request New Link</a>
            <?php endif; ?>

            <div class="auth-footer">
                <p><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>

    <button class="dark-mode-toggle" aria-label="Toggle dark mode">
        <span class="sun-icon">☀️</span>
        <span class="moon-icon">🌙</span>
    </button>

    <script src="assets/js/main.js"></script>
</body>
</html>
