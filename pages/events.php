<?php
require_once '../includes/functions.php';

$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql = "SELECT e.*, u.first_name, u.last_name,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status = 'approved') as registered_count
        FROM events e 
        JOIN users u ON e.organizer_id = u.id
        WHERE e.status = 'approved'";
$params = [];

if ($category) {
    $sql .= " AND e.category = ?";
    $params[] = $category;
}

if ($search) {
    $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY e.event_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$stmt = $pdo->query("SELECT DISTINCT category FROM events WHERE status = 'approved' ORDER BY category");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Campus Event Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
        <?php include '../includes/header.php'; ?>
    <?php else: ?>
        <nav class="navbar">
            <div class="container">
                <a href="<?= BASE_URL ?>/index.php" class="navbar-brand">
                    <img src="<?= BASE_URL ?>/assets/images/URSLogo.png" alt="Logo" style="height: 40px; width: auto;" onerror="this.outerHTML='📚'">
                    <span>Campus Events</span>
                </a>
                <button class="mobile-toggle">☰</button>
                <ul class="navbar-nav">
                    <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                    <li><a href="events.php" class="active">Events</a></li>
                    <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
                    <li><a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">Register</a></li>
                </ul>
            </div>
        </nav>
    <?php endif; ?>

    <section style="padding: 40px 0 20px; background: var(--gray-50);">
        <div class="container">
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm" style="margin-bottom: 16px; display: inline-block;">← Back</a>
            <h1 style="font-size: 2rem; margin-bottom: 24px; color: var(--gray-900);">Browse Events</h1>
            
            <div class="filters-bar">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search events..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <select class="form-control" style="width: auto;" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category'] ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                            <?= ucwords(str_replace('_', ' ', $cat['category'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <section style="padding: 40px 0;">
        <div class="container">
            <?php if (empty($events)): ?>
                <div class="empty-state">
                    <div class="icon">📅</div>
                    <h3>No Events Found</h3>
                    <p>Try adjusting your search or filter criteria.</p>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): 
                        $is_full = $event['participant_limit'] && $event['registered_count'] >= $event['participant_limit'];
                        $is_past = strtotime($event['event_date']) < strtotime(date('Y-m-d'));
                    ?>
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
                                    <span>👤 By <?= htmlspecialchars($event['first_name'] . ' ' . $event['last_name']) ?></span>
                                </div>
                                <div class="event-footer">
                                    <span class="badge <?= $is_full ? 'badge-danger' : 'badge-info' ?>">
                                        <?= $is_full ? 'Full' : $event['registered_count'] . ' / ' . ($event['participant_limit'] ?: '∞') ?>
                                    </span>
                                    <a href="event-detail.php?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Campus Event Management System. All rights reserved.</p>
        </div>
    </footer>

    <?php if (!isLoggedIn()): ?>
    <button class="dark-mode-toggle" aria-label="Toggle dark mode">
        <span class="sun-icon">☀️</span>
        <span class="moon-icon">🌙</span>
    </button>
    <?php endif; ?>

    <script src="../assets/js/main.js"></script>
    <script>
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('categoryFilter').addEventListener('change', applyFilters);

        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            let url = 'events.php?';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (category) url += 'category=' + encodeURIComponent(category);
            window.location.href = url;
        }
    </script>
</body>
</html>
