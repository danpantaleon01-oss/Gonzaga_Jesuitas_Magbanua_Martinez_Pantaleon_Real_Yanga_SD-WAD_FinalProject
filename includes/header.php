<?php
$user_role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? '';
$user_initial = strtoupper(substr($user_name, 0, 1));
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$pending_users_count = 0;
if ($user_role === 'admin') {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
    $pending_users_count = $stmt->fetch()['total'];
}
?>
<nav class="navbar">
    <div class="container">
        <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">
            <img src="<?= BASE_URL ?>/assets/images/URSLogo.png" alt="Logo" style="height: 40px; width: auto;" onerror="this.outerHTML='📚'">
            <span>Campus Events</span>
        </a>
        <button class="mobile-toggle">☰</button>
        <ul class="navbar-nav">
            <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= BASE_URL ?>/pages/dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/pages/events.php" class="<?= $current_page === 'events.php' || $current_page === 'event-detail.php' ? 'active' : '' ?>">Events</a></li>
            <?php if ($user_role === 'admin'): ?>
                <li><a href="<?= BASE_URL ?>/pages/admin/dashboard.php" class="<?= $current_dir === 'admin' ? 'active' : '' ?>">
                    Admin Panel
                    <?php if ($pending_users_count > 0): ?>
                        <span class="badge badge-danger" style="margin-left: 4px; font-size: 0.7rem;"><?= $pending_users_count ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="<?= BASE_URL ?>/pages/admin/manage-events.php">Manage Events</a></li>
                <li><a href="<?= BASE_URL ?>/pages/admin/manage-users.php">Manage Users</a></li>
                <li><a href="<?= BASE_URL ?>/pages/admin/manage-registrations.php">Registrations</a></li>
                <li><a href="<?= BASE_URL ?>/pages/admin/reports.php">Reports</a></li>
            <?php elseif ($user_role === 'organizer'): ?>
                <li><a href="<?= BASE_URL ?>/pages/organizer/my-events.php" class="<?= $current_dir === 'organizer' ? 'active' : '' ?>">My Events</a></li>
                <li><a href="<?= BASE_URL ?>/pages/organizer/create-event.php">Create Event</a></li>
                <li><a href="<?= BASE_URL ?>/pages/organizer/attendance.php">Attendance</a></li>
            <?php endif; ?>
            <li><a href="<?= BASE_URL ?>/pages/my-registrations.php" class="<?= $current_page === 'my-registrations.php' ? 'active' : '' ?>">My Registrations</a></li>
            <li>
                <div class="user-menu">
                    <a href="#" data-dropdown-toggle>
                        <span class="user-avatar"><?= $user_initial ?></span>
                        <span><?= htmlspecialchars($user_name) ?></span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?= BASE_URL ?>/pages/profile.php">My Profile</a>
                        <a href="<?= BASE_URL ?>/pages/my-registrations.php">My Registrations</a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= BASE_URL ?>/logout.php">Logout</a>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>

<button class="dark-mode-toggle" aria-label="Toggle dark mode">
    <span class="sun-icon">☀️</span>
    <span class="moon-icon">🌙</span>
</button>
