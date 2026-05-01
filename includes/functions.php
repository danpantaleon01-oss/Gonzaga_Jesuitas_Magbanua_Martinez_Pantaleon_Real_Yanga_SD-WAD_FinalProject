<?php
session_start();
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 30);

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['user_role'], (array)$roles)) {
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    }
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function logActivity($action, $description = '') {
    global $pdo;
    if (!isLoggedIn()) return;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $action, $description, $_SERVER['REMOTE_ADDR']]);
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateQRCode() {
    return 'QR-' . strtoupper(bin2hex(random_bytes(8)));
}

function isLockedOut() {
    if (!isset($_SESSION['login_attempts'])) {
        return false;
    }
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $lockout_end = $_SESSION['lockout_time'] + LOCKOUT_DURATION;
        if (time() < $lockout_end) {
            return true;
        }
        unset($_SESSION['login_attempts']);
        unset($_SESSION['lockout_time']);
    }
    return false;
}

function recordFailedAttempt() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['login_attempts']++;
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['lockout_time'] = time();
    }
}

function resetLoginAttempts() {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['lockout_time']);
}

function getRemainingLockoutTime() {
    if (!isset($_SESSION['lockout_time'])) return 0;
    $remaining = ($_SESSION['lockout_time'] + LOCKOUT_DURATION) - time();
    return max(0, $remaining);
}

function getRemainingAttempts() {
    $attempts = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] : 0;
    return max(0, MAX_LOGIN_ATTEMPTS - $attempts);
}
?>
