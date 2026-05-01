<?php
require_once '../../includes/functions.php';
requireRole(['organizer', 'admin']);

$user = getCurrentUser();
$success = '';

$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as total_registrations,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status = 'approved') as approved_count,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status = 'pending') as pending_count
        FROM events e WHERE e.organizer_id = ? ORDER BY e.event_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user['id']]);
$events = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([(int)$_GET['delete'], $user['id']]);
    logActivity('delete_event', 'Deleted event ID: ' . (int)$_GET['delete']);
    header('Location: my-events.php?success=deleted');
    exit;
}

if (isset($_GET['success']) && $_GET['success'] === 'deleted') {
    $success = 'Event deleted successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Events - Campus Event Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container" style="padding: 40px 20px;">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <a href="javascript:history.back()" class="btn btn-secondary btn-sm" style="margin-bottom: 8px; display: inline-block;">← Back</a>
                <h2>My Events</h2>
            </div>
            <a href="create-event.php" class="btn btn-primary">+ Create New Event</a>
        </div>

        <?php if (empty($events)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <div class="icon">📅</div>
                        <h3>No Events Yet</h3>
                        <p>You haven't created any events yet.</p>
                        <a href="create-event.php" class="btn btn-primary">Create Your First Event</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $event): 
                    $status_badge = 'badge-info';
                    if ($event['status'] === 'approved') $status_badge = 'badge-success';
                    elseif ($event['status'] === 'pending_approval') $status_badge = 'badge-warning';
                    elseif ($event['status'] === 'cancelled') $status_badge = 'badge-danger';
                    elseif ($event['status'] === 'completed') $status_badge = 'badge-gray';
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
                                <span>⏰ <?= date('g:i A', strtotime($event['start_time'])) ?></span>
                                <span>📍 <?= htmlspecialchars($event['venue']) ?></span>
                            </div>
                            <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                                <span class="badge <?= $status_badge ?>"><?= ucfirst(str_replace('_', ' ', $event['status'])) ?></span>
                                <span class="badge badge-info"><?= $event['approved_count'] ?> attendees</span>
                                <?php if ($event['pending_count'] > 0): ?>
                                    <span class="badge badge-warning"><?= $event['pending_count'] ?> pending</span>
                                <?php endif; ?>
                            </div>
                            <div class="event-footer">
                                <a href="../event-detail.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                <div class="table-actions">
                                    <a href="edit-event.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="../admin/manage-registrations.php?event_id=<?= $event['id'] ?>" class="btn btn-sm btn-secondary">Registrations</a>
                                    <a href="my-events.php?delete=<?= $event['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this event?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Campus Event Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
