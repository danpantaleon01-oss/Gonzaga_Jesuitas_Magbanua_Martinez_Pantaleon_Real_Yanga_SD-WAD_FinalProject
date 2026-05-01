<?php
require_once '../includes/functions.php';
requireLogin();

$user = getCurrentUser();

$stmt = $pdo->prepare("
    SELECT r.*, e.title, e.event_date, e.start_time, e.end_time, e.venue, e.category, e.status as event_status,
           u.first_name, u.last_name
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    LEFT JOIN users u ON e.organizer_id = u.id
    WHERE r.user_id = ?
    ORDER BY e.event_date DESC
");
$stmt->execute([$user['id']]);
$registrations = $stmt->fetchAll();

if (isset($_GET['cancel']) && isset($_GET['reg_id'])) {
    $reg_id = (int)$_GET['reg_id'];
    $stmt = $pdo->prepare("UPDATE registrations SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$reg_id, $user['id']]);
    logActivity('cancel_registration', 'Cancelled registration');
    header('Location: my-registrations.php?cancelled=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registrations - Campus Event Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container" style="padding: 40px 20px;">
        <?php if (isset($_GET['cancelled'])): ?>
            <div class="alert alert-success">Registration cancelled successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Action completed successfully!</div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <a href="javascript:history.back()" class="btn btn-secondary btn-sm" style="margin-bottom: 8px; display: inline-block;">← Back</a>
                <h2>My Registrations</h2>
            </div>
            <a href="events.php" class="btn btn-primary">Browse Events</a>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (empty($registrations)): ?>
                    <div class="empty-state">
                        <div class="icon">📝</div>
                        <h3>No Registrations Yet</h3>
                        <p>You haven't registered for any events yet.</p>
                        <a href="events.php" class="btn btn-primary">Find Events</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="registrationsTable">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Venue</th>
                                    <th>Reg. Status</th>
                                    <th>Attendance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $reg): 
                                    $status_badge = 'badge-warning';
                                    if ($reg['status'] === 'approved') $status_badge = 'badge-success';
                                    elseif ($reg['status'] === 'rejected') $status_badge = 'badge-danger';
                                    elseif ($reg['status'] === 'cancelled') $status_badge = 'badge-gray';
                                    
                                    $attendance_badge = $reg['attendance_status'] === 'present' ? 'badge-success' : 'badge-gray';
                                ?>
                                <tr>
                                    <td data-label="Event">
                                        <strong><?= htmlspecialchars($reg['title']) ?></strong><br>
                                        <small class="badge badge-info"><?= ucwords(str_replace('_', ' ', $reg['category'])) ?></small>
                                    </td>
                                    <td data-label="Date"><?= date('M d, Y', strtotime($reg['event_date'])) ?></td>
                                    <td data-label="Time"><?= date('g:i A', strtotime($reg['start_time'])) ?></td>
                                    <td data-label="Venue"><?= htmlspecialchars($reg['venue']) ?></td>
                                    <td data-label="Reg. Status"><span class="badge <?= $status_badge ?>"><?= ucfirst($reg['status']) ?></span></td>
                                    <td data-label="Attendance"><span class="badge <?= $attendance_badge ?>"><?= ucfirst($reg['attendance_status']) ?></span></td>
                                    <td data-label="Actions">
                                        <div class="table-actions">
                                            <a href="event-detail.php?id=<?= $reg['event_id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                            <?php if ($reg['status'] === 'pending'): ?>
                                                <a href="my-registrations.php?cancel=1&reg_id=<?= $reg['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this registration?')">Cancel</a>
                                            <?php endif; ?>
                                        </div>
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
</body>
</html>
