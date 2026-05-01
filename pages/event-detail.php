<?php
require_once '../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$message_type = '';

$stmt = $pdo->prepare("
    SELECT e.*, u.first_name, u.last_name, u.email as organizer_email, u.contact_number as organizer_contact
    FROM events e 
    JOIN users u ON e.organizer_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: events.php');
    exit;
}

$is_registered = false;
$registration_status = null;
$registration_id = null;

if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id, status, attendance_status, qr_code FROM registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $reg = $stmt->fetch();
    if ($reg) {
        $is_registered = true;
        $registration_status = $reg['status'];
        $registration_id = $reg['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    if ($stmt->fetch()['count'] > 0) {
        $message = 'You have already registered for this event';
        $message_type = 'warning';
    } else {
        if ($event['participant_limit']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ? AND status = 'approved'");
            $stmt->execute([$id]);
            if ($stmt->fetch()['count'] >= $event['participant_limit']) {
                $message = 'This event is full';
                $message_type = 'danger';
            } else {
                $stmt = $pdo->prepare("INSERT INTO registrations (event_id, user_id, status) VALUES (?, ?, 'pending')");
                $stmt->execute([$id, $_SESSION['user_id']]);
                logActivity('register_event', 'Registered for event: ' . $event['title']);
                $message = 'Registration submitted successfully! Awaiting approval.';
                $message_type = 'success';
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO registrations (event_id, user_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$id, $_SESSION['user_id']]);
            logActivity('register_event', 'Registered for event: ' . $event['title']);
            $message = 'Registration submitted successfully! Awaiting approval.';
            $message_type = 'success';
        }
        
        if ($message_type === 'success') {
            header('Location: event-detail.php?id=' . $id . '&registered=1');
            exit;
        }
    }
}

$stmt = $pdo->prepare("
    SELECT r.*, u.first_name, u.last_name, u.student_id, u.course, u.year_level
    FROM registrations r
    JOIN users u ON r.user_id = u.id
    WHERE r.event_id = ? AND r.status = 'approved'
");
$stmt->execute([$id]);
$participants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> - Campus Event Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
        <?php include '../includes/header.php'; ?>
    <?php else: ?>
        <nav class="navbar">
            <div class="container">
                <a href="<?= BASE_URL ?>/index.php" class="navbar-brand"><img src="<?= BASE_URL ?>/assets/images/URSLogo.png" alt="Logo" style="height: 40px; width: auto;" onerror="this.outerHTML='📚'"><span style="margin-left: 10px;">Campus Events</span></a>
                <ul class="navbar-nav">
                    <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
                    <li><a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">Register</a></li>
                </ul>
            </div>
        </nav>
    <?php endif; ?>

    <div class="container" style="padding: 40px 20px;">
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Registration submitted successfully! Awaiting organizer approval.</div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm">← Back</a>
            <a href="events.php" class="btn btn-secondary btn-sm">Browse Events</a>
        </div>

        <div class="card">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0;">
                <div>
                    <?php if ($event['poster_image']): ?>
                        <img src="<?= UPLOAD_URL . htmlspecialchars($event['poster_image']) ?>" alt="<?= htmlspecialchars($event['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; min-height: 300px;">
                    <?php else: ?>
                        <div style="background: linear-gradient(135deg, var(--primary-light) 0%, #c7d2fe 100%); min-height: 300px; display: flex; align-items: center; justify-content: center; font-size: 5rem;">📅</div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <span class="badge badge-info" style="margin-bottom: 12px;"><?= ucwords(str_replace('_', ' ', $event['category'])) ?></span>
                    <h1 style="font-size: 1.8rem; margin-bottom: 16px; color: var(--gray-900);"><?= htmlspecialchars($event['title']) ?></h1>
                    
                    <div class="event-meta" style="margin-bottom: 24px;">
                        <span>📅 <?= date('l, F d, Y', strtotime($event['event_date'])) ?></span>
                        <span>⏰ <?= date('g:i A', strtotime($event['start_time'])) ?> - <?= date('g:i A', strtotime($event['end_time'])) ?></span>
                        <span>📍 <?= htmlspecialchars($event['venue']) ?></span>
                        <span>👤 Organizer: <?= htmlspecialchars($event['first_name'] . ' ' . $event['last_name']) ?></span>
                    </div>

                    <h4 style="margin-bottom: 8px;">About This Event</h4>
                    <p style="color: var(--gray-600); margin-bottom: 24px; line-height: 1.8;"><?= nl2br(htmlspecialchars($event['description'])) ?></p>

                    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                        <?php if (isLoggedIn() && !$is_registered && $event['status'] === 'approved' && strtotime($event['event_date']) >= strtotime(date('Y-m-d'))): ?>
                            <form method="POST" style="display: inline;">
                                <button type="submit" class="btn btn-primary btn-lg">Register for Event</button>
                            </form>
                        <?php elseif ($is_registered): ?>
                            <span class="badge badge-success" style="font-size: 0.9rem; padding: 10px 16px;">Registered (<?= ucfirst($registration_status) ?>)</span>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-lg">Login to Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 32px;">
            <div class="card-header">
                <h3>Participants (<?= count($participants) ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($participants)): ?>
                    <p style="color: var(--gray-500);">No participants yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Year Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $p): ?>
                                <tr>
                                    <td data-label="Student ID"><?= htmlspecialchars($p['student_id']) ?></td>
                                    <td data-label="Name"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                                    <td data-label="Course"><?= htmlspecialchars($p['course']) ?></td>
                                    <td data-label="Year Level"><?= htmlspecialchars($p['year_level']) ?></td>
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

    <?php if (!isLoggedIn()): ?>
    <button class="dark-mode-toggle" aria-label="Toggle dark mode">
        <span class="sun-icon">☀️</span>
        <span class="moon-icon">🌙</span>
    </button>
    <?php endif; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>
