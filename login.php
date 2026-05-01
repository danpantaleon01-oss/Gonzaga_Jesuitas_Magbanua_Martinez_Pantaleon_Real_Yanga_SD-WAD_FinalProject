<?php
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';
$success = '';
$lockout_message = '';

if (isset($_GET['pending'])) {
    $success = 'Your account has been created successfully! It is now pending admin approval. You will be able to log in once an administrator approves your account.';
}

if (isLockedOut()) {
    $remaining = getRemainingLockoutTime();
    $lockout_message = "Too many failed attempts. Please wait {$remaining} seconds before trying again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($lockout_message)) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'pending') {
                $error = 'Your account is pending admin approval. Please wait until an administrator reviews your registration.';
            } elseif ($user['status'] === 'inactive') {
                $error = 'Your account has been deactivated. Please contact an administrator.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                resetLoginAttempts();
                logActivity('login', 'User logged in successfully');
                echo '<script>window.location.href = "' . BASE_URL . '/pages/dashboard.php?login=success";</script>';
                exit;
            }
        } else {
            recordFailedAttempt();
            $remaining = getRemainingAttempts();
            if ($remaining > 0) {
                $error = "Invalid email or password. {$remaining} attempts remaining.";
            } else {
                $error = "Account locked for " . LOCKOUT_DURATION . " seconds due to too many failed attempts.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <img src="assets/images/URSLogo.png" alt="Logo" style="height: 80px; margin-bottom: 16px;" onerror="this.style.display='none'">
                <h1>Campus Events</h1>
                <p>Sign in to your account</p>
            </div>

            <a href="index.php" class="btn btn-secondary btn-sm btn-block" style="margin-bottom: 20px;">← Back to Home</a>

            <?php if ($lockout_message): ?>
                <div class="alert alert-danger">⏳ <?= $lockout_message ?></div>
            <?php endif; ?>

            <?php if ($error && empty($lockout_message)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if (!isLockedOut()): ?>
            <form method="POST" data-validate id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginBtn">Sign In</button>
            </form>
            <?php else: ?>
            <div class="alert alert-warning">Please wait before trying again...</div>
            <?php endif; ?>

            <div class="auth-footer">
                <p><a href="forgot-password.php">Forgot your password?</a></p>
                <p>Don't have an account? <a href="register.php">Create one</a></p>
            </div>
        </div>
    </div>

    <button class="dark-mode-toggle" aria-label="Toggle dark mode">
        <span class="sun-icon">☀️</span>
        <span class="moon-icon">🌙</span>
    </button>

    <script src="assets/js/main.js"></script>
    <?php if (isLockedOut()): ?>
    <script>
        let remaining = <?= getRemainingLockoutTime() ?>;
        const timer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                clearInterval(timer);
                location.reload();
            }
        }, 1000);
    </script>
    <?php endif; ?>
</body>
</html>
