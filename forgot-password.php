<?php
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);

    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expires]);

            $resetLink = BASE_URL . '/reset-password.php?token=' . $token;
            $success = 'Password reset link has been generated. In a production environment, this would be sent to your email.';
            $success .= '<br><br><strong>Your reset link:</strong> <a href="' . $resetLink . '">' . $resetLink . '</a>';
        } else {
            $success = 'If an account exists with that email, a password reset link has been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Campus Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Forgot Password</h1>
                <p>Enter your email to reset your password</p>
            </div>

            <a href="login.php" class="btn btn-secondary btn-sm btn-block" style="margin-bottom: 20px;">← Back to Login</a>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" data-validate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your registered email" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Send Reset Link</button>
            </form>

            <div class="auth-footer">
                <p>Remember your password? <a href="login.php">Sign in here</a></p>
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
