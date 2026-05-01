<?php
require_once '../../includes/functions.php';
requireRole('admin');

$success = '';
$error = '';

$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql = "SELECT e.*, u.first_name, u.last_name,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status = 'approved') as registered_count
        FROM events e JOIN users u ON e.organizer_id = u.id WHERE 1=1";
$params = [];

if ($filter === 'pending') {
    $sql .= " AND e.status = 'pending_approval'";
} elseif ($filter === 'approved') {
    $sql .= " AND e.status = 'approved'";
} elseif ($filter === 'cancelled') {
    $sql .= " AND e.status = 'cancelled'";
}

if ($search) {
    $sql .= " AND (e.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY e.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

if (isset($_GET['approve'])) {
    $stmt = $pdo->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
    $stmt->execute([(int)$_GET['approve']]);
    logActivity('approve_event', 'Approved event ID: ' . (int)$_GET['approve']);
    header('Location: manage-events.php?success=approved');
    exit;
}

if (isset($_GET['reject'])) {
    $stmt = $pdo->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([(int)$_GET['reject']]);
    logActivity('reject_event', 'Rejected event ID: ' . (int)$_GET['reject']);
    header('Location: manage-events.php?success=rejected');
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    logActivity('delete_event', 'Deleted event ID: ' . (int)$_GET['delete']);
    header('Location: manage-events.php?success=deleted');
    exit;
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'approved') $success = 'Event approved successfully!';
    elseif ($_GET['success'] === 'rejected') $success = 'Event rejected successfully!';
    elseif ($_GET['success'] === 'deleted') $success = 'Event deleted successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Admin</title>
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
                <h2>Manage Events</h2>
            </div>
            <a href="../organizer/create-event.php" class="btn btn-primary">+ Create Event</a>
        </div>

        <div class="filters-bar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search events..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select class="form-control" style="width: auto;" id="filterSelect">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Events</option>
                <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                <option value="approved" <?= $filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="cancelled" <?= $filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

        <div class="card">
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table" id="eventsTable">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Organizer</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): 
                                $status_badge = 'badge-info';
                                if ($event['status'] === 'approved') $status_badge = 'badge-success';
                                elseif ($event['status'] === 'pending_approval') $status_badge = 'badge-warning';
                                elseif ($event['status'] === 'cancelled') $status_badge = 'badge-danger';
                                elseif ($event['status'] === 'completed') $status_badge = 'badge-gray';
                            ?>
                            <tr>
                                <td data-label="Event"><strong><?= htmlspecialchars($event['title']) ?></strong></td>
                                <td data-label="Organizer"><?= htmlspecialchars($event['first_name'] . ' ' . $event['last_name']) ?></td>
                                <td data-label="Date"><?= date('M d, Y', strtotime($event['event_date'])) ?></td>
                                <td data-label="Category"><?= ucwords(str_replace('_', ' ', $event['category'])) ?></td>
                                <td data-label="Registered"><?= $event['registered_count'] ?> / <?= $event['participant_limit'] ?: '∞' ?></td>
                                <td data-label="Status"><span class="badge <?= $status_badge ?>"><?= ucfirst(str_replace('_', ' ', $event['status'])) ?></span></td>
                                <td data-label="Actions">
                                    <div class="table-actions">
                                        <a href="../event-detail.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                        <?php if ($event['status'] === 'pending_approval'): ?>
                                            <a href="manage-events.php?approve=<?= $event['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this event?')">Approve</a>
                                            <a href="manage-events.php?reject=<?= $event['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this event?')">Reject</a>
                                        <?php endif; ?>
                                        <a href="manage-events.php?delete=<?= $event['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this event permanently?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
    <script>
        document.getElementById('searchInput').addEventListener('input', function() {
            window.location.href = 'manage-events.php?filter=<?= $filter ?>&search=' + encodeURIComponent(this.value);
        });
        document.getElementById('filterSelect').addEventListener('change', function() {
            window.location.href = 'manage-events.php?filter=' + this.value + '&search=<?= $search ?>';
        });
    </script>
</body>
</html>
