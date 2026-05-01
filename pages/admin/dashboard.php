<?php
require_once '../../includes/functions.php';
requireRole('admin');

$current_page = basename($_SERVER['PHP_SELF'], '.php');

$stmt = $pdo->query("SELECT COUNT(*) as total FROM events");
$total_events = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$total_users = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
$pending_users = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations");
$total_registrations = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM events WHERE status = 'pending_approval'");
$pending_events = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM registrations WHERE status = 'pending'");
$pending_registrations = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE() AND status = 'approved'");
$upcoming_events = $stmt->fetch()['total'];

$stmt = $pdo->query("
    SELECT e.id, e.title, e.event_date, e.status, e.participant_limit,
           (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as reg_count
    FROM events e 
    ORDER BY e.created_at DESC 
    LIMIT 10
");
$recent_events = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT r.id, r.status, e.title, u.first_name, u.last_name, u.student_id, r.created_at
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN users u ON r.user_id = u.id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$pending_regs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Campus Event Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="dashboard-header">
        <div class="container">
            <h1>Admin Dashboard</h1>
            <p>Manage and oversee all campus events</p>
        </div>
    </div>

        <div class="container" style="padding-bottom: 40px;">
            <?php include '../../includes/weather-widget.php'; ?>
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm" style="margin-bottom: 20px; display: inline-block;">← Back</a>

        <?php if ($pending_users > 0): ?>
        <div class="alert alert-warning" style="cursor: pointer;" onclick="location.href='manage-users.php?filter=pending'">
            ⏳ <strong><?= $pending_users ?></strong> user(s) waiting for account approval. <a href="manage-users.php?filter=pending" style="text-decoration: underline;">Review now →</a>
        </div>
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
                    <h3><?= $upcoming_events ?></h3>
                    <p>Upcoming Events</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">👥</div>
                <div class="stat-info">
                    <h3><?= $total_users ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">📝</div>
                <div class="stat-info">
                    <h3><?= $total_registrations ?></h3>
                    <p>Registrations</p>
                </div>
            </div>
        </div>

        <div class="stats-grid" style="margin-top: 24px;">
            <div class="stat-card" style="cursor: pointer;" onclick="location.href='manage-users.php?filter=pending'">
                <div class="stat-icon yellow">👤</div>
                <div class="stat-info">
                    <h3><?= $pending_users ?></h3>
                    <p>Pending User Approvals</p>
                </div>
            </div>
            <div class="stat-card" style="cursor: pointer;" onclick="location.href='manage-events.php?filter=pending'">
                <div class="stat-icon yellow">📅</div>
                <div class="stat-info">
                    <h3><?= $pending_events ?></h3>
                    <p>Pending Event Approvals</p>
                </div>
            </div>
            <div class="stat-card" style="cursor: pointer;" onclick="location.href='manage-registrations.php'">
                <div class="stat-icon yellow">📋</div>
                <div class="stat-info">
                    <h3><?= $pending_registrations ?></h3>
                    <p>Pending Registrations</p>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 32px;">
            <div class="card">
                <div class="card-header">
                    <h3>Recent Events</h3>
                    <a href="manage-events.php" class="btn btn-sm btn-secondary">View All</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_events as $e): 
                                    $badge = 'badge-info';
                                    if ($e['status'] === 'approved') $badge = 'badge-success';
                                    elseif ($e['status'] === 'pending_approval') $badge = 'badge-warning';
                                    elseif ($e['status'] === 'cancelled') $badge = 'badge-danger';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['title']) ?></td>
                                    <td><?= date('M d', strtotime($e['event_date'])) ?></td>
                                    <td><span class="badge <?= $badge ?>"><?= ucfirst(str_replace('_', ' ', $e['status'])) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Pending Registrations</h3>
                    <a href="manage-registrations.php" class="btn btn-sm btn-secondary">View All</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Event</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_regs as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                    <td><?= htmlspecialchars($r['title']) ?></td>
                                    <td><?= date('M d', strtotime($r['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Campus Event Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
