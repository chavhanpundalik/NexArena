<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$loggedInId = (int)$_SESSION['user_id'];
$loggedInRole = $_SESSION['role'] ?? '';

// --- Get user ID ---
$userId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($userId <= 0) {
    header("Location: users.php");
    exit();
}

// --- Fetch user data ---
$stmt = $conn->prepare("SELECT user_id, username, email, phone, role, status FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: users.php");
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

// --- CSRF token ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ============================================================
// LOG ACTIVITY FUNCTION - ADDED THIS
// ============================================================
function log_activity($conn, $user_id, $user_name, $action, $details = '') {
    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $user_id, $user_name, $action, $details);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Silently fail - don't break the main functionality
        error_log("Activity log error: " . $e->getMessage());
    }
}

// ============================================================
// HANDLE FORM SUBMISSION
// ============================================================
$errors = [];
$success = false;
$form_data = $user; // start with current data

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $role     = $_POST['role'] ?? 'user';
        $status   = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $form_data = compact('username', 'email', 'phone', 'role', 'status');

        // --- Validation ---
        if (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers and underscores.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }

        if (!empty($phone) && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
            $errors[] = "Phone number contains invalid characters.";
        }

        if (!in_array($role, ['user', 'admin', 'super_admin'])) {
            $errors[] = "Invalid role selected.";
        }
        if (!in_array($status, ['active', 'inactive', 'blocked'])) {
            $errors[] = "Invalid status selected.";
        }

        // --- Role change restrictions ---
        if ($user['role'] === 'super_admin' && $role !== 'super_admin') {
            $countStmt = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'super_admin'");
            $countRow = $countStmt->fetch_assoc();
            if ((int)$countRow['total'] <= 1) {
                $errors[] = "Cannot demote the only Super Admin. At least one Super Admin must exist.";
            } elseif ($userId === $loggedInId) {
                $errors[] = "You cannot demote your own Super Admin account.";
            }
        }

        if ($userId === $loggedInId && $role !== $user['role'] && $user['role'] === 'super_admin') {
            $errors[] = "You cannot change your own Super Admin role.";
        }

        // --- Check if username/email already taken by another user ---
        if (empty($errors)) {
            $stmtCheck = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
            $stmtCheck->bind_param("ssi", $username, $email, $userId);
            $stmtCheck->execute();
            $checkResult = $stmtCheck->get_result();
            if ($checkResult->num_rows > 0) {
                $errors[] = "Username or email already taken by another user.";
            }
            $stmtCheck->close();
        }

        // --- Password update (optional) ---
        $updatePassword = false;
        if (!empty($password) || !empty($confirm)) {
            if (strlen($password) < 8) {
                $errors[] = "Password must be at least 8 characters long.";
            } elseif ($password !== $confirm) {
                $errors[] = "Passwords do not match.";
            } else {
                $updatePassword = true;
            }
        }

        // --- Update user ---
        if (empty($errors)) {
            if ($updatePassword) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, password = ?, role = ?, status = ? WHERE user_id = ?");
                $stmt->bind_param("ssssssi", $username, $email, $phone, $hashed, $role, $status, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, role = ?, status = ? WHERE user_id = ?");
                $stmt->bind_param("sssssi", $username, $email, $phone, $role, $status, $userId);
            }

            if ($stmt->execute()) {
                // --- LOG ACTIVITY ---
                $user_name = $_SESSION['full_name'] ?? 'Admin';
                log_activity($conn, $_SESSION['user_id'], $user_name, 'User Updated', "Updated user: $username (ID: $userId) - New role: $role, New status: $status");

                $success = true;
                $user = array_merge($user, compact('username', 'email', 'phone', 'role', 'status'));
                $form_data = $user;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $csrf_token = $_SESSION['csrf_token'];
                if ($userId === $loggedInId) {
                    $_SESSION['full_name'] = $username;
                    $_SESSION['role'] = $role;
                }
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// ============================================================
// DON'T CLOSE CONNECTION HERE - SIDEBAR NEEDS IT
// ============================================================
// $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        .form-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
            max-width: 720px;
            margin: 0 auto;
        }
        .form-card h2 {
            font-size: 22px;
            margin-bottom: 6px;
        }
        .form-card .subtitle {
            color: #71717a;
            margin-bottom: 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 700;
            font-size: 13px;
            color: #1f2937;
            margin-bottom: 6px;
        }
        .form-group label .required {
            color: #dc2626;
            margin-left: 2px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: #fafafa;
            transition: 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--orange);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-actions {
            display: flex;
            gap: 14px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: var(--orange);
            color: #fff;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: 0.25s;
            box-shadow: 0 7px 18px rgba(249,115,22,0.25);
        }
        .btn-primary:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f4f4f5;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.25s;
        }
        .btn-secondary:hover {
            background: #e4e4e7;
        }
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert ul {
            margin: 4px 0 0 18px;
        }
        .password-hint {
            font-size: 12px;
            color: #71717a;
            margin-top: 4px;
        }
        .password-optional {
            background: #f4f4f5;
            padding: 12px 16px;
            border-radius: 10px;
            border-left: 4px solid var(--orange);
            margin-bottom: 20px;
            font-size: 13px;
            color: #4b5563;
        }
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="users-main">

    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-user-pen"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Edit User</h1>
                <p>Update details for <strong><?= htmlspecialchars($user['username']); ?></strong></p>
            </div>
        </div>
        <a href="users.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Users
        </a>
    </section>

    <div class="form-card">
        <h2><i class="fa-regular fa-pen-to-square" style="color:var(--orange);"></i> Edit User</h2>
        <p class="subtitle">Modify the user's information below.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> User updated successfully!
                <a href="users.php" style="color:var(--orange);font-weight:700;text-decoration:none;margin-left:10px;">View all users →</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i> Please fix the following errors:
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="id" value="<?= (int)$user['user_id']; ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($form_data['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($form_data['phone']); ?>" placeholder="+1 234 567 890">
            </div>

            <!-- Optional password change -->
            <div class="password-optional">
                <i class="fa-regular fa-circle-info" style="color:var(--orange);"></i>
                Leave password fields blank to keep the current password.
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" minlength="8">
                    <div class="password-hint"><i class="fa-regular fa-circle-info"></i> Minimum 8 characters.</div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="8">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="role">Role</label>
                    <select name="role" id="role">
                        <option value="user" <?= $form_data['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?= $form_data['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="super_admin" <?= $form_data['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="active" <?= $form_data['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $form_data['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="blocked" <?= $form_data['status'] === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Update User</button>
                <a href="users.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>

</main>

</body>
</html>