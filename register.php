<?php
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = sanitize($_POST['student_id'] ?? '');
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $course = sanitize($_POST['course']);
    $year_level = sanitize($_POST['year_level']);
    $contact_number = sanitize($_POST['contact_number']);
    $role = isset($_POST['role']) ? sanitize($_POST['role']) : 'participant';

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all required fields and select your role';
    } elseif (!in_array($role, ['participant', 'organizer', 'admin'])) {
        $error = 'Invalid role selected';
    } elseif ($role === 'participant' && empty($student_id)) {
        $error = 'Student ID is required for participants';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        if ($role === 'participant') {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
            $stmt->execute([$student_id]);
            if ($stmt->fetch()) {
                $error = 'Student ID already registered';
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                $final_student_id = ($role === 'participant') ? $student_id : 'STAFF-' . strtoupper(substr($first_name, 0, 2) . substr($last_name, 0, 2)) . '-' . time();
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (student_id, first_name, last_name, email, password, course, year_level, contact_number, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$final_student_id, $first_name, $last_name, $email, $hashed_password, $course, $year_level, $contact_number, $role]);
                
                logActivity('register', 'New user registered as ' . $role . ' (pending approval)');
                header('Location: login.php?pending=1');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Campus Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 550px;">
            <a href="index.php" class="btn btn-secondary btn-sm btn-block" style="margin-bottom: 20px;">← Back to Home</a>

            <div class="auth-header">
                <img src="assets/images/URSLogo.png" alt="Logo" style="height: 80px; margin-bottom: 16px;" onerror="this.style.display='none'">
                <h1>Create Account</h1>
                <p>Join the campus event community</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" data-validate id="registerForm">
                <div class="form-row">
                    <div class="form-group" id="studentIdGroup">
                        <label for="student_id">Student ID <span id="studentIdAsterisk">*</span></label>
                        <input type="text" id="student_id" name="student_id" class="form-control" placeholder="e.g., 2024-0001" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="your.email@campus.edu" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" placeholder="First name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Last name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" id="courseGroup">
                        <label for="course">Course/Program <span id="courseOptional" style="color: var(--text-tertiary); font-weight: normal;">(optional)</span></label>
                        <input type="text" id="course" name="course" class="form-control" placeholder="e.g., Computer Science">
                    </div>
                    <div class="form-group" id="yearLevelGroup">
                        <label for="year_level">Year Level <span id="yearOptional" style="color: var(--text-tertiary); font-weight: normal;">(optional)</span></label>
                        <select id="year_level" name="year_level" class="form-control">
                            <option value="">Select year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                            <option value="Graduate">Graduate</option>
                            <option value="Faculty">Faculty</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number" class="form-control" placeholder="e.g., 09XX-XXX-XXXX">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>I am registering as a...</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <label class="role-option" style="cursor: pointer;">
                            <input type="radio" name="role" value="participant" required style="display: none;" checked>
                            <div class="role-card">
                                <span style="font-size: 1.8rem;">👤</span>
                                <span style="font-weight: 600; font-size: 0.85rem;">Participant</span>
                            </div>
                        </label>
                        <label class="role-option" style="cursor: pointer;">
                            <input type="radio" name="role" value="organizer" style="display: none;">
                            <div class="role-card">
                                <span style="font-size: 1.8rem;">📋</span>
                                <span style="font-weight: 600; font-size: 0.85rem;">Organizer</span>
                            </div>
                        </label>
                        <label class="role-option" style="cursor: pointer;">
                            <input type="radio" name="role" value="admin" style="display: none;">
                            <div class="role-card">
                                <span style="font-size: 1.8rem;">🛡️</span>
                                <span style="font-weight: 600; font-size: 0.85rem;">Admin</span>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>

            <div style="margin-top: 20px; padding: 14px; background: var(--warning-light); border-radius: var(--radius); font-size: 0.82rem; color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.2);">
                <strong>ℹ️ Note:</strong> All new accounts require admin approval before you can log in. Please allow some time for your account to be reviewed.
            </div>
        </div>
    </div>

    <button class="dark-mode-toggle" aria-label="Toggle dark mode">
        <span class="sun-icon">☀️</span>
        <span class="moon-icon">🌙</span>
    </button>

    <script src="assets/js/main.js"></script>
    <script>
        const studentIdInput = document.getElementById('student_id');
        const studentIdAsterisk = document.getElementById('studentIdAsterisk');
        const courseInput = document.getElementById('course');
        const courseOptional = document.getElementById('courseOptional');
        const yearLevelInput = document.getElementById('year_level');
        const yearOptional = document.getElementById('yearOptional');
        const roleInputs = document.querySelectorAll('input[name="role"]');

        function updateFormFields() {
            const selectedRole = document.querySelector('input[name="role"]:checked').value;
            
            // Student ID
            const studentLabel = document.querySelector('label[for="student_id"]');
            if (selectedRole === 'participant') {
                studentIdInput.setAttribute('required', '');
                studentIdInput.value = '';
                studentIdInput.placeholder = 'e.g., 2024-0001';
                studentIdAsterisk.style.display = 'inline';
                studentIdInput.style.opacity = '1';
                studentLabel.childNodes[0].textContent = 'Student ID ';
            } else {
                studentIdInput.removeAttribute('required');
                studentIdInput.value = '';
                studentIdInput.placeholder = 'Not required for ' + selectedRole;
                studentIdAsterisk.style.display = 'none';
                studentIdInput.style.opacity = '0.5';
                studentLabel.childNodes[0].textContent = 'Staff/Employee ID (optional)';
            }

            // Course & Year Level
            if (selectedRole === 'participant') {
                courseInput.style.opacity = '1';
                courseOptional.style.display = 'none';
                courseInput.placeholder = 'e.g., Computer Science';
                yearLevelInput.style.opacity = '1';
                yearOptional.style.display = 'none';
            } else {
                courseInput.style.opacity = '0.5';
                courseOptional.style.display = 'inline';
                courseInput.placeholder = 'Optional';
                yearLevelInput.style.opacity = '0.5';
                yearOptional.style.display = 'inline';
            }
        }

        roleInputs.forEach(input => {
            input.addEventListener('change', updateFormFields);
        });

        updateFormFields();
    </script>
</body>
</html>
