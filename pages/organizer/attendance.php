<?php
require_once '../../includes/functions.php';
requireRole(['organizer', 'admin']);

$user = getCurrentUser();
$success = '';
$error = '';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $user['id']]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header('Location: my-events.php');
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT r.*, u.first_name, u.last_name, u.student_id, u.course, u.year_level, u.contact_number, u.email
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.event_id = ? AND r.status = 'approved'
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$event_id]);
    $participants = $stmt->fetchAll();

    if (isset($_GET['mark_present'])) {
        $reg_id = (int)$_GET['mark_present'];
        $stmt = $pdo->prepare("UPDATE registrations SET attendance_status = 'present' WHERE id = ? AND event_id = ?");
        $stmt->execute([$reg_id, $event_id]);
        logActivity('mark_attendance', "Marked attendance for event $event_id");
        header('Location: attendance.php?event_id=' . $event_id . '&success=marked');
        exit;
    }

    if (isset($_GET['mark_all'])) {
        $stmt = $pdo->prepare("UPDATE registrations SET attendance_status = 'present' WHERE event_id = ? AND status = 'approved' AND attendance_status = 'absent'");
        $stmt->execute([$event_id]);
        logActivity('mark_all_attendance', "Marked all attendance for event $event_id");
        header('Location: attendance.php?event_id=' . $event_id . '&success=all_marked');
        exit;
    }

    if (isset($_GET['success'])) {
        if ($_GET['success'] === 'marked') $success = 'Attendance marked!';
        elseif ($_GET['success'] === 'all_marked') $success = 'All attendance marked!';
    }

    $present_count = 0;
    foreach ($participants as $p) {
        if ($p['attendance_status'] === 'present') $present_count++;
    }
}

$stmt = $pdo->prepare("SELECT id, title, event_date FROM events WHERE organizer_id = ? AND status = 'approved' ORDER BY event_date DESC");
$stmt->execute([$user['id']]);
$my_events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracking - Campus Event Management</title>
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
                <h2>Attendance Tracking</h2>
            </div>
        </div>

        <div class="card" style="margin-bottom: 24px;">
            <div class="card-body">
                <form method="GET" style="display: flex; gap: 16px; align-items: flex-end;">
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label for="event_id">Select Event</label>
                        <select id="event_id" name="event_id" class="form-control" onchange="this.form.submit()">
                            <option value="">Choose an event...</option>
                            <?php foreach ($my_events as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= $event_id === $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['title']) ?> (<?= date('M d, Y', strtotime($e['event_date'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($event_id && isset($event)): ?>
            <div class="stats-grid" style="margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-icon blue">👥</div>
                    <div class="stat-info">
                        <h3><?= count($participants) ?></h3>
                        <p>Total Participants</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <h3><?= $present_count ?></h3>
                        <p>Present</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">❌</div>
                    <div class="stat-info">
                        <h3><?= count($participants) - $present_count ?></h3>
                        <p>Absent</p>
                    </div>
                </div>
                <div class="stat-card">
                    <a href="attendance.php?event_id=<?= $event_id ?>&mark_all=1" class="btn btn-success btn-block" style="height: 100%; display: flex; align-items: center; justify-content: center;" onclick="return confirm('Mark all participants as present?')">Mark All Present</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><?= htmlspecialchars($event['title']) ?> - Participant List</h3>
                    <button class="btn btn-sm btn-secondary" onclick="exportTableToCSV('attendanceTable', 'attendance_<?= $event_id ?>.csv')">📥 Export</button>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="table" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course/Year</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $p): ?>
                                <tr>
                                    <td data-label="Student ID"><?= htmlspecialchars($p['student_id']) ?></td>
                                    <td data-label="Name"><strong><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></strong></td>
                                    <td data-label="Course/Year"><?= htmlspecialchars($p['course']) ?> - <?= htmlspecialchars($p['year_level']) ?></td>
                                    <td data-label="Contact"><?= htmlspecialchars($p['contact_number']) ?></td>
                                    <td data-label="Status">
                                        <span class="badge <?= $p['attendance_status'] === 'present' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($p['attendance_status']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Action">
                                        <?php if ($p['attendance_status'] === 'absent'): ?>
                                            <a href="attendance.php?event_id=<?= $event_id ?>&mark_present=<?= $p['id'] ?>" class="btn btn-sm btn-success">Mark Present</a>
                                        <?php else: ?>
                                            <span class="badge badge-success">✓</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
