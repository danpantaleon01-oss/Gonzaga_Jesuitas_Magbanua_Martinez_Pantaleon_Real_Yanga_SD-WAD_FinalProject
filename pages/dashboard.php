<?php
require_once '../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$role = $_SESSION['user_role'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM events WHERE status = 'approved'");
$total_events = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'participant' AND status = 'active'");
$total_participants = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE() AND status = 'approved'");
$upcoming_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE status = 'approved'");
$total_registrations = $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status = 'approved') as registered_count
    FROM events e 
    WHERE e.event_date >= CURDATE() AND e.status = 'approved'
    ORDER BY e.event_date ASC 
    LIMIT 5
");
$stmt->execute();
$upcoming_events = $stmt->fetchAll();

if ($role === 'organizer') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM events WHERE organizer_id = ?");
    $stmt->execute([$user['id']]);
    $my_events = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM registrations r JOIN events e ON r.event_id = e.id WHERE e.organizer_id = ?");
    $stmt->execute([$user['id']]);
    $my_total_registrations = $stmt->fetch()['total'];
} elseif ($role === 'participant') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM registrations WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $my_registrations = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM registrations WHERE user_id = ? AND attendance_status = 'present'");
    $stmt->execute([$user['id']]);
    $my_attendance = $stmt->fetch()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Campus Event Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-header">
        <div class="container">
            <h1>Welcome, <?= htmlspecialchars($user['first_name']) ?>!</h1>
            <p>Here's your event overview</p>
        </div>
    </div>

        <div class="container" style="padding-bottom: 40px;">
            <?php include '../includes/weather-widget.php'; ?>
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm" style="margin-bottom: 20px; display: inline-block;">← Back</a>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Action completed successfully!</div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-info">
                    <h3><?= $total_events ?></h3>
                    <p>Total Events</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📈</div>
                <div class="stat-info">
                    <h3><?= $upcoming_count ?></h3>
                    <p>Upcoming Events</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">👥</div>
                <div class="stat-info">
                    <h3><?= $total_participants ?></h3>
                    <p>Participants</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">✅</div>
                <div class="stat-info">
                    <h3><?= $total_registrations ?></h3>
                    <p>Registrations</p>
                </div>
            </div>
        </div>

        <?php if ($role === 'organizer'): ?>
        <div class="stats-grid" style="margin-top: 24px;">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div class="stat-info">
                    <h3><?= $my_events ?></h3>
                    <p>My Events</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📝</div>
                <div class="stat-info">
                    <h3><?= $my_total_registrations ?></h3>
                    <p>My Total Registrations</p>
                </div>
            </div>
            <div class="stat-card">
                <a href="create-event.php" class="btn btn-primary btn-block" style="height: 100%; display: flex; align-items: center; justify-content: center;">+ Create New Event</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($role === 'participant'): ?>
        <div class="stats-grid" style="margin-top: 24px;">
            <div class="stat-card">
                <div class="stat-icon blue">📝</div>
                <div class="stat-info">
                    <h3><?= $my_registrations ?></h3>
                    <p>My Registrations</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-info">
                    <h3><?= $my_attendance ?></h3>
                    <p>Events Attended</p>
                </div>
            </div>
            <div class="stat-card">
                <a href="events.php" class="btn btn-primary btn-block" style="height: 100%; display: flex; align-items: center; justify-content: center;">Browse Events</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="card" style="margin-top: 32px;">
            <div class="card-header">
                <h3>Upcoming Events</h3>
                <a href="events.php" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_events)): ?>
                    <div class="empty-state">
                        <div class="icon">📅</div>
                        <h3>No Upcoming Events</h3>
                        <p>Check back later for new events!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Venue</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_events as $event): ?>
                                <tr>
                                    <td data-label="Event"><strong><?= htmlspecialchars($event['title']) ?></strong></td>
                                    <td data-label="Date"><?= date('M d, Y', strtotime($event['event_date'])) ?></td>
                                    <td data-label="Time"><?= date('g:i A', strtotime($event['start_time'])) ?></td>
                                    <td data-label="Venue"><?= htmlspecialchars($event['venue']) ?></td>
                                    <td data-label="Registered">
                                        <span class="badge badge-info"><?= $event['registered_count'] ?> / <?= $event['participant_limit'] ?: '∞' ?></span>
                                    </td>
                                    <td data-label="Action">
                                        <a href="event-detail.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Campus Event Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <?php if (isset($_GET['login']) && $_GET['login'] === 'success'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() { showLoginAnimation(); }, 300);
        });
    </script>
    <?php endif; ?>
</body>
</html>
