<?php
require_once '../../includes/functions.php';
requireRole('admin');

$success = '';
$error = '';

$event_filter = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending';

$sql = "SELECT r.*, e.title as event_title, e.event_date, u.first_name, u.last_name, u.student_id, u.course, u.year_level, u.contact_number, u.email
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        JOIN users u ON r.user_id = u.id
        WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
}

if ($event_filter) {
    $sql .= " AND r.event_id = ?";
    $params[] = $event_filter;
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, title FROM events WHERE status = 'approved' ORDER BY event_date DESC");
$events = $stmt->fetchAll();

if (isset($_GET['approve_reg'])) {
    $reg_id = (int)$_GET['approve_reg'];
    $stmt = $pdo->prepare("UPDATE registrations SET status = 'approved' WHERE id = ?");
    $stmt->execute([$reg_id]);
    logActivity('approve_registration', "Approved registration $reg_id");
    header('Location: manage-registrations.php?success=approved');
    exit;
}

if (isset($_GET['reject_reg'])) {
    $reg_id = (int)$_GET['reject_reg'];
    $stmt = $pdo->prepare("UPDATE registrations SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$reg_id]);
    logActivity('reject_registration', "Rejected registration $reg_id");
    header('Location: manage-registrations.php?success=rejected');
    exit;
}

if (isset($_GET['mark_attendance'])) {
    $reg_id = (int)$_GET['mark_attendance'];
    $stmt = $pdo->prepare("UPDATE registrations SET attendance_status = 'present' WHERE id = ?");
    $stmt->execute([$reg_id]);
    logActivity('mark_attendance', "Marked attendance for registration $reg_id");
    header('Location: manage-registrations.php?success=attendance_marked');
    exit;
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'approved') $success = 'Registration approved!';
    elseif ($_GET['success'] === 'rejected') $success = 'Registration rejected!';
    elseif ($_GET['success'] === 'attendance_marked') $success = 'Attendance marked!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Registrations - Admin</title>
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
                <h2>Manage Registrations</h2>
            </div>
            <button class="btn btn-secondary" onclick="exportTableToCSV('registrationsTable', 'registrations_export.csv')">📥 Export CSV</button>
        </div>

        <div class="filters-bar">
            <select class="form-control" style="width: auto;" id="statusFilter">
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
            </select>
            <select class="form-control" style="width: auto;" id="eventFilter">
                <option value="0">All Events</option>
                <?php foreach ($events as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $event_filter === $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card">
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table" id="registrationsTable">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Course/Year</th>
                                <th>Event</th>
                                <th>Event Date</th>
                                <th>Reg. Date</th>
                                <th>Status</th>
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
                                <td data-label="Student ID"><?= htmlspecialchars($reg['student_id']) ?></td>
                                <td data-label="Name">
                                    <strong><?= htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']) ?></strong><br>
                                    <small><?= htmlspecialchars($reg['email']) ?></small>
                                </td>
                                <td data-label="Course/Year"><?= htmlspecialchars($reg['course']) ?> - <?= htmlspecialchars($reg['year_level']) ?></td>
                                <td data-label="Event"><?= htmlspecialchars($reg['event_title']) ?></td>
                                <td data-label="Event Date"><?= date('M d, Y', strtotime($reg['event_date'])) ?></td>
                                <td data-label="Reg. Date"><?= date('M d, Y', strtotime($reg['created_at'])) ?></td>
                                <td data-label="Status"><span class="badge <?= $status_badge ?>"><?= ucfirst($reg['status']) ?></span></td>
                                <td data-label="Attendance"><span class="badge <?= $attendance_badge ?>"><?= ucfirst($reg['attendance_status']) ?></span></td>
                                <td data-label="Actions">
                                    <div class="table-actions">
                                        <?php if ($reg['status'] === 'pending'): ?>
                                            <a href="manage-registrations.php?approve_reg=<?= $reg['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this registration?')">Approve</a>
                                            <a href="manage-registrations.php?reject_reg=<?= $reg['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this registration?')">Reject</a>
                                        <?php endif; ?>
                                        <?php if ($reg['status'] === 'approved' && $reg['attendance_status'] === 'absent'): ?>
                                            <a href="manage-registrations.php?mark_attendance=<?= $reg['id'] ?>" class="btn btn-sm btn-primary">Mark Present</a>
                                        <?php endif; ?>
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
        document.getElementById('statusFilter').addEventListener('change', function() {
            window.location.href = 'manage-registrations.php?status=' + this.value + '&event_id=<?= $event_filter ?>';
        });
        document.getElementById('eventFilter').addEventListener('change', function() {
            window.location.href = 'manage-registrations.php?status=<?= $status_filter ?>&event_id=' + this.value;
        });
    </script>
</body>
</html>
