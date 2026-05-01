<?php
require_once '../../includes/functions.php';
requireRole(['organizer', 'admin']);

$user = getCurrentUser();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
$stmt->execute([$id, $user['id']]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: my-events.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $venue = sanitize($_POST['venue']);
    $event_date = sanitize($_POST['event_date']);
    $start_time = sanitize($_POST['start_time']);
    $end_time = sanitize($_POST['end_time']);
    $category = sanitize($_POST['category']);
    $participant_limit = isset($_POST['participant_limit']) && $_POST['participant_limit'] ? (int)$_POST['participant_limit'] : null;

    if (empty($title) || empty($venue) || empty($event_date) || empty($start_time) || empty($end_time)) {
        $error = 'Please fill in all required fields';
    } elseif ($start_time >= $end_time) {
        $error = 'End time must be after start time';
    } else {
        $poster_image = $event['poster_image'];
        if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['poster_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'event_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['poster_image']['tmp_name'], UPLOAD_DIR . $filename)) {
                    if ($event['poster_image']) {
                        @unlink(UPLOAD_DIR . $event['poster_image']);
                    }
                    $poster_image = $filename;
                }
            } else {
                $error = 'Invalid image format. Allowed: JPG, JPEG, PNG, GIF';
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, venue = ?, event_date = ?, start_time = ?, end_time = ?, category = ?, participant_limit = ?, poster_image = ? WHERE id = ?");
            $stmt->execute([$title, $description, $venue, $event_date, $start_time, $end_time, $category, $participant_limit, $poster_image, $id]);
            
            logActivity('update_event', 'Updated event: ' . $title);
            $success = 'Event updated successfully!';
            
            $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$id]);
            $event = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Campus Event Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container" style="padding: 40px 20px;">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <a href="javascript:history.back()" class="btn btn-secondary btn-sm" style="margin-bottom: 8px; display: inline-block;">← Back</a>
                <h2>Edit Event</h2>
            </div>
            <a href="my-events.php" class="btn btn-secondary">My Events</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" data-validate>
                    <div class="form-group">
                        <label for="title">Event Title *</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required><?= htmlspecialchars($event['description']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="">Select category</option>
                                <?php 
                                $categories = ['seminar', 'workshop', 'sports_fest', 'cultural_event', 'student_org', 'academic', 'other'];
                                foreach ($categories as $cat): 
                                ?>
                                    <option value="<?= $cat ?>" <?= $event['category'] === $cat ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $cat)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="venue">Venue *</label>
                            <input type="text" id="venue" name="venue" class="form-control" value="<?= htmlspecialchars($event['venue']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="event_date">Event Date *</label>
                        <input type="date" id="event_date" name="event_date" class="form-control" value="<?= $event['event_date'] ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Start Time *</label>
                            <input type="time" id="start_time" name="start_time" class="form-control" value="<?= $event['start_time'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time *</label>
                            <input type="time" id="end_time" name="end_time" class="form-control" value="<?= $event['end_time'] ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="participant_limit">Participant Limit</label>
                        <input type="number" id="participant_limit" name="participant_limit" class="form-control" value="<?= $event['participant_limit'] ?: '' ?>" placeholder="Leave empty for unlimited" min="1">
                    </div>

                    <div class="form-group">
                        <label for="poster_image">Event Poster</label>
                        <?php if ($event['poster_image']): ?>
                            <div style="margin-bottom: 12px;">
                                <img src="<?= UPLOAD_URL . htmlspecialchars($event['poster_image']) ?>" alt="Current poster" style="max-height: 150px; border-radius: var(--radius);">
                                <p style="color: var(--gray-500); font-size: 0.85rem; margin-top: 4px;">Current poster. Upload a new one to replace.</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="poster_image" name="poster_image" class="form-control" accept="image/*">
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-primary btn-lg">Update Event</button>
                        <a href="my-events.php" class="btn btn-secondary btn-lg">Cancel</a>
                    </div>
                </form>
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
