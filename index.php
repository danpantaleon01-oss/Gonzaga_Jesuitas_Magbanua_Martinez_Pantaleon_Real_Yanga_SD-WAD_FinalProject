    <?php
require_once 'includes/functions.php';

$stmt = $pdo->query("SELECT * FROM events WHERE status = 'approved' AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 6");
$upcoming_events = $stmt->fetchAll();

$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM events WHERE status = 'approved' GROUP BY category");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">
                <img src="assets/images/URSLogo.png" alt="Logo" style="height: 40px; width: auto;" onerror="this.outerHTML='📚'">
                <span>Campus Events</span>
            </a>
            <button class="mobile-toggle">☰</button>
            <ul class="navbar-nav">
                <li><a href="index.php" class="active">Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="pages/dashboard.php">Dashboard</a></li>
                    <li><a href="pages/events.php">Events</a></li>
                    <li>
                        <div class="user-menu">
                            <a href="#" data-dropdown-toggle>
                                <span class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
                            </a>
                            <div class="dropdown-menu">
                                <a href="pages/profile.php">My Profile</a>
                                <a href="pages/my-registrations.php">My Registrations</a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php">Logout</a>
                            </div>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="pages/events.php">Events</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn btn-primary btn-sm">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <section class="dashboard-header" style="text-align: center; padding: 80px 0;">
        <div class="container">
            <h1 style="font-size: 2.5rem; margin-bottom: 16px;">Welcome to Campus Event Management</h1>
            <p style="font-size: 1.2rem; opacity: 0.9; max-width: 600px; margin: 0 auto 32px;">
                Discover, register, and manage campus events all in one place. Stay connected with your university community.
            </p>
            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-lg" style="background: white; color: var(--primary);">Get Started</a>
                    <a href="pages/events.php" class="btn btn-outline btn-lg" style="border-color: white; color: white;">Browse Events</a>
                <?php else: ?>
                    <a href="pages/dashboard.php" class="btn btn-lg" style="background: white; color: var(--primary);">Go to Dashboard</a>
                    <a href="pages/events.php" class="btn btn-outline btn-lg" style="border-color: white; color: white;">Browse Events</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section style="padding: 60px 0;">
        <div class="container">
            <h2 style="font-size: 1.8rem; margin-bottom: 32px; color: var(--gray-900);">Upcoming Events</h2>
            <?php if (empty($upcoming_events)): ?>
                <div class="empty-state">
                    <div class="icon">📅</div>
                    <h3>No Upcoming Events</h3>
                    <p>Check back later for new events!</p>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($upcoming_events as $event): ?>
                        <div class="event-card">
                            <div class="event-poster">
                                <?php if ($event['poster_image']): ?>
                                    <img src="<?= UPLOAD_URL . htmlspecialchars($event['poster_image']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                                <?php else: ?>
                                    <div class="no-image">📅</div>
                                <?php endif; ?>
                                <span class="event-category-badge"><?= ucwords(str_replace('_', ' ', $event['category'])) ?></span>
                            </div>
                            <div class="event-content">
                                <h3><?= htmlspecialchars($event['title']) ?></h3>
                                <div class="event-meta">
                                    <span>📅 <?= date('M d, Y', strtotime($event['event_date'])) ?></span>
                                    <span>⏰ <?= date('g:i A', strtotime($event['start_time'])) ?> - <?= date('g:i A', strtotime($event['end_time'])) ?></span>
                                    <span>📍 <?= htmlspecialchars($event['venue']) ?></span>
                                </div>
                                <div class="event-footer">
                                    <a href="pages/event-detail.php?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div style="text-align: center; margin-top: 40px;">
                <a href="pages/events.php" class="btn btn-outline btn-lg">View All Events</a>
            </div>
        </div>
    </section>

    <section style="padding: 60px 0; background: var(--gray-50);">
        <div class="container">
            <h2 style="font-size: 1.8rem; margin-bottom: 40px; text-align: center; color: var(--gray-900);">Event Categories</h2>
            <div class="stats-grid">
                <?php 
                $category_icons = [
                    'seminar' => '🎓',
                    'workshop' => '🔧',
                    'sports_fest' => '⚽',
                    'cultural_event' => '🎭',
                    'student_org' => '👥',
                    'academic' => '📖',
                    'other' => '📌'
                ];
                foreach ($categories as $cat): 
                ?>
                    <a href="pages/events.php?category=<?= $cat['category'] ?>" class="stat-card" style="text-decoration: none;">
                        <span style="font-size: 2rem;"><?= $category_icons[$cat['category']] ?? '📌' ?></span>
                        <div class="stat-info">
                            <h3><?= $cat['count'] ?></h3>
                            <p><?= ucwords(str_replace('_', ' ', $cat['category'])) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Campus Event Management System. All rights reserved.</p>
        </div>
    </footer>

    <button class="dark-mode-toggle" aria-label="Toggle dark mode">
        <span class="sun-icon">☀️</span>
        <span class="moon-icon">🌙</span>
    </button>

    <script src="assets/js/main.js"></script>
</body>
</html>
