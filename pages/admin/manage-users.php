<?php
require_once '../../includes/functions.php';
requireRole('admin');

$success = '';
$error = '';

$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql = "SELECT *, 
        (SELECT COUNT(*) FROM events WHERE organizer_id = users.id) as events_count,
        (SELECT COUNT(*) FROM registrations WHERE user_id = users.id) as reg_count
        FROM users WHERE 1=1";
$params = [];

if ($filter === 'admin') {
    $sql .= " AND role = 'admin'";
} elseif ($filter === 'organizer') {
    $sql .= " AND role = 'organizer'";
} elseif ($filter === 'participant') {
    $sql .= " AND role = 'participant'";
} elseif ($filter === 'pending') {
    $sql .= " AND status = 'pending'";
} elseif ($filter === 'active') {
    $sql .= " AND status = 'active'";
} elseif ($filter === 'inactive') {
    $sql .= " AND status = 'inactive'";
}

if ($search) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

if (isset($_GET['approve_user'])) {
    $user_id = (int)$_GET['approve_user'];
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$user_id]);
    logActivity('approve_user', "Approved user $user_id");
    header('Location: manage-users.php?success=approved');
    exit;
}

if (isset($_GET['reject_user'])) {
    $user_id = (int)$_GET['reject_user'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    logActivity('reject_user', "Rejected and deleted pending user $user_id");
    header('Location: manage-users.php?success=rejected');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = sanitize($_POST['new_role']);
    
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $user_id]);
    logActivity('update_user_role', "Changed user $user_id role to $new_role");
    $success = 'User role updated successfully!';
}

if (isset($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_status = $stmt->fetch()['status'];
    $new_status = $current_status === 'active' ? 'inactive' : 'active';
    
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    logActivity('toggle_user_status', "Toggled user $user_id status to $new_status");
    header('Location: manage-users.php?success=status_updated');
    exit;
}

if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    if ($user_id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        logActivity('delete_user', "Deleted user $user_id");
        header('Location: manage-users.php?success=deleted');
        exit;
    } else {
        $error = 'Cannot delete your own account!';
    }
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'status_updated') $success = 'User status updated!';
    elseif ($_GET['success'] === 'deleted') $success = 'User deleted successfully!';
    elseif ($_GET['success'] === 'approved') $success = 'User account approved!';
    elseif ($_GET['success'] === 'rejected') $success = 'User registration rejected.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
                <h2>Manage Users</h2>
            </div>
        </div>

        <?php
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
        $pending_count = $stmt->fetch()['total'];
        if ($pending_count > 0):
        ?>
        <div class="alert alert-warning" style="cursor: pointer;" onclick="document.getElementById('filterSelect').value='pending'; window.location.href='manage-users.php?filter=pending'">
            ⏳ <strong><?= $pending_count ?></strong> user(s) pending approval. <a href="?filter=pending" style="text-decoration: underline;">Review now →</a>
        </div>
        <?php endif; ?>

        <div class="filters-bar">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select class="form-control" style="width: auto;" id="filterSelect">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Users</option>
                <option value="pending" <?= $filter === 'pending' ? 'selected' : '' ?>>⏳ Pending Approval</option>
                <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="admin" <?= $filter === 'admin' ? 'selected' : '' ?>>Admins</option>
                <option value="organizer" <?= $filter === 'organizer' ? 'selected' : '' ?>>Organizers</option>
                <option value="participant" <?= $filter === 'participant' ? 'selected' : '' ?>>Participants</option>
            </select>
            <button class="btn btn-secondary" onclick="exportTableToCSV('usersTable', 'users_export.csv')">📥 Export CSV</button>
        </div>

        <div class="card">
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Course</th>
                                <th>Events</th>
                                <th>Registrations</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td data-label="Student ID"><?= htmlspecialchars($user['student_id']) ?></td>
                                <td data-label="Name">
                                    <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                </td>
                                <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                                <td data-label="Role">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="new_role" class="form-control" style="padding: 4px 8px; font-size: 0.85rem; width: auto;" onchange="this.form.submit()">
                                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            <option value="organizer" <?= $user['role'] === 'organizer' ? 'selected' : '' ?>>Organizer</option>
                                            <option value="participant" <?= $user['role'] === 'participant' ? 'selected' : '' ?>>Participant</option>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                </td>
                                <td data-label="Course"><?= htmlspecialchars($user['course'] ?: '-') ?></td>
                                <td data-label="Events"><?= $user['events_count'] ?></td>
                                <td data-label="Registrations"><?= $user['reg_count'] ?></td>
                                <td data-label="Status"><span class="badge <?= $user['status'] === 'active' ? 'badge-success' : ($user['status'] === 'pending' ? 'badge-warning' : 'badge-danger') ?>"><?= ucfirst($user['status']) ?></span></td>
                                <td data-label="Actions">
                                    <div class="table-actions">
                                        <?php if ($user['status'] === 'pending'): ?>
                                            <a href="manage-users.php?approve_user=<?= $user['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this user account?')">✓ Approve</a>
                                            <a href="manage-users.php?reject_user=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject and delete this registration?')">✗ Reject</a>
                                        <?php else: ?>
                                            <a href="manage-users.php?toggle_status=<?= $user['id'] ?>" class="btn btn-sm <?= $user['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>">
                                                <?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <a href="manage-users.php?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                                            <?php endif; ?>
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
        document.getElementById('searchInput').addEventListener('input', function() {
            window.location.href = 'manage-users.php?filter=<?= $filter ?>&search=' + encodeURIComponent(this.value);
        });
        document.getElementById('filterSelect').addEventListener('change', function() {
            window.location.href = 'manage-users.php?filter=' + this.value + '&search=<?= $search ?>';
        });
    </script>
</body>
</html>
