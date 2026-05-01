<?php
require_once '../../includes/functions.php';
requireRole('admin');

$report_type = isset($_GET['type']) ? sanitize($_GET['type']) : 'events';
$date_from = isset($_GET['from']) ? sanitize($_GET['from']) : date('Y-m-01');
$date_to = isset($_GET['to']) ? sanitize($_GET['to']) : date('Y-m-d');

if ($report_type === 'events') {
    $sql = "SELECT e.*, u.first_name, u.last_name,
            (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status = 'approved') as participant_count
            FROM events e JOIN users u ON e.organizer_id = u.id
            WHERE e.event_date BETWEEN ? AND ?
            ORDER BY e.event_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_from, $date_to]);
    $events = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM events WHERE event_date BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $total = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT SUM((SELECT COUNT(*) FROM registrations WHERE event_id = events.id AND status = 'approved')) as total_participants FROM events WHERE event_date BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $total_participants = $stmt->fetch()['total_participants'] ?: 0;

} elseif ($report_type === 'participants') {
    $sql = "SELECT u.*, 
            (SELECT COUNT(*) FROM registrations WHERE user_id = u.id AND status = 'approved') as events_attended,
            (SELECT COUNT(*) FROM registrations WHERE user_id = u.id AND attendance_status = 'present') as attendance_count
            FROM users u
            WHERE u.role = 'participant' AND u.created_at BETWEEN ? AND ?
            ORDER BY events_attended DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_from, $date_to]);
    $participants = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'participant' AND created_at BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $total = $stmt->fetch()['total'];

} elseif ($report_type === 'attendance') {
    $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
    
    $sql = "SELECT r.*, u.first_name, u.last_name, u.student_id, u.course, u.year_level, u.contact_number, u.email, e.title as event_title
            FROM registrations r
            JOIN users u ON r.user_id = u.id
            JOIN events e ON r.event_id = e.id
            WHERE r.status = 'approved' AND e.event_date BETWEEN ? AND ?";
    $params = [$date_from, $date_to];
    
    if ($event_id) {
        $sql .= " AND r.event_id = ?";
        $params[] = $event_id;
    }
    
    $sql .= " ORDER BY u.last_name, u.first_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $attendance = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.status = 'approved' AND e.event_date BETWEEN ? AND ?" . ($event_id ? " AND r.event_id = ?" : ""));
    $stmt->execute($event_id ? [$date_from, $date_to, $event_id] : [$date_from, $date_to]);
    $total = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as present FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.status = 'approved' AND r.attendance_status = 'present' AND e.event_date BETWEEN ? AND ?" . ($event_id ? " AND r.event_id = ?" : ""));
    $stmt->execute($event_id ? [$date_from, $date_to, $event_id] : [$date_from, $date_to]);
    $present = $stmt->fetch()['present'];
    
    $stmt = $pdo->query("SELECT id, title FROM events WHERE status = 'approved' ORDER BY event_date DESC");
    $events_list = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container" style="padding: 40px 20px;">
        <div class="page-header">
            <div>
                <a href="javascript:history.back()" class="btn btn-secondary btn-sm" style="margin-bottom: 8px; display: inline-block;">← Back</a>
                <h2>Reports & Analytics</h2>
            </div>
            <div style="display: flex; gap: 12px;">
                <button class="btn btn-secondary" onclick="printPage()">🖨️ Print</button>
                <button class="btn btn-secondary" onclick="exportTableToCSV('reportTable', 'report_export.csv')">📥 Export CSV</button>
            </div>
        </div>

        <div class="tabs">
            <a href="?type=events&from=<?= $date_from ?>&to=<?= $date_to ?>" class="tab <?= $report_type === 'events' ? 'active' : '' ?>">Events Report</a>
            <a href="?type=participants&from=<?= $date_from ?>&to=<?= $date_to ?>" class="tab <?= $report_type === 'participants' ? 'active' : '' ?>">Participants Report</a>
            <a href="?type=attendance&from=<?= $date_from ?>&to=<?= $date_to ?>" class="tab <?= $report_type === 'attendance' ? 'active' : '' ?>">Attendance Report</a>
        </div>

        <div class="card" style="margin-bottom: 24px;">
            <div class="card-body">
                <form method="GET" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                    <input type="hidden" name="type" value="<?= $report_type ?>">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Date From</label>
                        <input type="date" name="from" class="form-control" value="<?= $date_from ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Date To</label>
                        <input type="date" name="to" class="form-control" value="<?= $date_to ?>">
                    </div>
                    <?php if ($report_type === 'attendance'): ?>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Event</label>
                        <select name="event_id" class="form-control">
                            <option value="0">All Events</option>
                            <?php foreach ($events_list as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= (isset($_GET['event_id']) && (int)$_GET['event_id'] === $e['id']) ? 'selected' : '' ?>><?= htmlspecialchars($e['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Report Results</h3>
                <span class="badge badge-info"><?= $total ?> records</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table" id="reportTable">
                        <?php if ($report_type === 'events'): ?>
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Organizer</th>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Participants</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $e): ?>
                                <tr>
                                    <td data-label="Event"><?= htmlspecialchars($e['title']) ?></td>
                                    <td data-label="Organizer"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></td>
                                    <td data-label="Date"><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
                                    <td data-label="Category"><?= ucwords(str_replace('_', ' ', $e['category'])) ?></td>
                                    <td data-label="Participants"><?= $e['participant_count'] ?></td>
                                    <td data-label="Status"><span class="badge <?= $e['status'] === 'approved' ? 'badge-success' : 'badge-warning' ?>"><?= ucfirst(str_replace('_', ' ', $e['status'])) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php elseif ($report_type === 'participants'): ?>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                    <th>Events Registered</th>
                                    <th>Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $p): ?>
                                <tr>
                                    <td data-label="Student ID"><?= htmlspecialchars($p['student_id']) ?></td>
                                    <td data-label="Name"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                                    <td data-label="Course"><?= htmlspecialchars($p['course']) ?></td>
                                    <td data-label="Year Level"><?= htmlspecialchars($p['year_level']) ?></td>
                                    <td data-label="Events Registered"><?= $p['events_attended'] ?></td>
                                    <td data-label="Attendance"><?= $p['attendance_count'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php elseif ($report_type === 'attendance'): ?>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Event</th>
                                    <th>Course/Year</th>
                                    <th>Contact</th>
                                    <th>Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance as $a): ?>
                                <tr>
                                    <td data-label="Student ID"><?= htmlspecialchars($a['student_id']) ?></td>
                                    <td data-label="Name"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                    <td data-label="Event"><?= htmlspecialchars($a['event_title']) ?></td>
                                    <td data-label="Course/Year"><?= htmlspecialchars($a['course']) ?> - <?= htmlspecialchars($a['year_level']) ?></td>
                                    <td data-label="Contact"><?= htmlspecialchars($a['contact_number']) ?></td>
                                    <td data-label="Attendance"><span class="badge <?= $a['attendance_status'] === 'present' ? 'badge-success' : 'badge-gray' ?>"><?= ucfirst($a['attendance_status']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($report_type === 'attendance'): ?>
        <div class="stats-grid" style="margin-top: 24px;">
            <div class="stat-card">
                <div class="stat-icon blue">📋</div>
                <div class="stat-info">
                    <h3><?= $total ?></h3>
                    <p>Total Approved Registrations</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-info">
                    <h3><?= $present ?></h3>
                    <p>Present</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">❌</div>
                <div class="stat-info">
                    <h3><?= $total - $present ?></h3>
                    <p>Absent</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">📊</div>
                <div class="stat-info">
                    <h3><?= $total > 0 ? round(($present / $total) * 100, 1) : 0 ?>%</h3>
                    <p>Attendance Rate</p>
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
