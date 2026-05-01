<?php
require_once '../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $course = sanitize($_POST['course']);
    $year_level = sanitize($_POST['year_level']);
    $contact_number = sanitize($_POST['contact_number']);

    if (empty($first_name) || empty($last_name)) {
        $error = 'First name and last name are required';
    } else {
        $profile_image = $user['profile_image'];

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $filename = 'profile_' . $user['id'] . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], UPLOAD_DIR . $filename)) {
                    if ($user['profile_image'] !== 'default-avatar.png') {
                        @unlink(UPLOAD_DIR . $user['profile_image']);
                    }
                    $profile_image = $filename;
                }
            } else {
                $error = 'Invalid image format. Allowed: JPG, JPEG, PNG, GIF';
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, course = ?, year_level = ?, contact_number = ?, profile_image = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $course, $year_level, $contact_number, $profile_image, $user['id']]);
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            logActivity('update_profile', 'Profile updated');
            $success = 'Profile updated successfully!';
            $user = getCurrentUser();
        }
    }
}

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!password_verify($current_password, $user['password'])) {
        $error = 'Current password is incorrect';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);
        logActivity('change_password', 'Password changed');
        $success = 'Password changed successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Campus Event Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container" style="padding: 40px 20px;">
        <a href="javascript:history.back()" class="btn btn-secondary btn-sm" style="margin-bottom: 20px; display: inline-block;">← Back</a>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 32px;">
            <div class="card">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <span class="badge badge-info" style="margin-top: 8px;"><?= ucfirst($user['role']) ?></span>
                    <p style="margin-top: 16px; font-size: 0.9rem; color: var(--gray-500);">
                        Student ID: <?= htmlspecialchars($user['student_id']) ?><br>
                        <?= htmlspecialchars($user['course']) ?> - <?= htmlspecialchars($user['year_level']) ?>
                    </p>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>Edit Profile</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" data-validate>
                            <div class="form-group">
                                <label for="profile_image">Profile Image</label>
                                <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/*">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: var(--gray-100);">
                                <small style="color: var(--gray-500);">Email cannot be changed</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="course">Course/Program</label>
                                    <input type="text" id="course" name="course" class="form-control" value="<?= htmlspecialchars($user['course']) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="year_level">Year Level</label>
                                    <select id="year_level" name="year_level" class="form-control">
                                        <option value="">Select year</option>
                                        <option value="1st Year" <?= $user['year_level'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                        <option value="2nd Year" <?= $user['year_level'] === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                        <option value="3rd Year" <?= $user['year_level'] === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                        <option value="4th Year" <?= $user['year_level'] === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                                        <option value="5th Year" <?= $user['year_level'] === '5th Year' ? 'selected' : '' ?>>5th Year</option>
                                        <option value="Graduate" <?= $user['year_level'] === 'Graduate' ? 'selected' : '' ?>>Graduate</option>
                                        <option value="Faculty" <?= $user['year_level'] === 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <input type="tel" id="contact_number" name="contact_number" class="form-control" value="<?= htmlspecialchars($user['contact_number']) ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>

                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3>Change Password</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" data-validate>
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Min 6 characters" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" name="change_password" value="1" class="btn btn-warning">Change Password</button>
                        </form>
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

    <script src="../assets/js/main.js"></script>
</body>
</html>
