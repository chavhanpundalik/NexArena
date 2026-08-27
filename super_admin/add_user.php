<?php
session_start();
require_once "../db_connect.php";

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- CSRF token generation ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// --- Handle form submission ---
$errors = [];
$success = false;
$form_data = ['username' => '', 'email' => '', 'phone' => '', 'role' => 'user', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        $username   = trim($_POST['username'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $password   = $_POST['password'] ?? '';
        $confirm    = $_POST['confirm_password'] ?? '';
        $role       = $_POST['role'] ?? 'user';
        $status     = $_POST['status'] ?? 'active';

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

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm) {
            $errors[] = "Passwords do not match.";
        }

        if (!in_array($role, ['user', 'admin', 'super_admin'])) {
            $errors[] = "Invalid role selected.";
        }
        if (!in_array($status, ['active', 'inactive', 'blocked'])) {
            $errors[] = "Invalid status selected.";
        }

        // --- Check if username or email already exists ---
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "Username or email already taken.";
            }
            $stmt->close();
        }

        // --- Insert user ---
        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $email, $phone, $hashed, $role, $status);
            if ($stmt->execute()) {
                // --- LOG ACTIVITY ---
                log_activity($conn, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Admin', 'User Added', "Added user: $username ($email) with role: $role");

                $success = true;
                $form_data = ['username' => '', 'email' => '', 'phone' => '', 'role' => 'user', 'status' => 'active'];
                // Regenerate CSRF token after successful submission
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $csrf_token = $_SESSION['csrf_token'];
            } else {
                $errors[] = "Database error: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User | NexArena</title>
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
            <div class="header-icon"><i class="fa-solid fa-user-plus"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Add New User</h1>
                <p>Create a new user account with role and status.</p>
            </div>
        </div>
        <a href="users.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Users
        </a>
    </section>

    <div class="form-card">
        <h2><i class="fa-regular fa-pen-to-square" style="color:var(--orange);"></i> User Details</h2>
        <p class="subtitle">Fill in the information below to create a new user.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> User created successfully!
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

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <div class="password-hint"><i class="fa-regular fa-circle-info"></i> Minimum 8 characters.</div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
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
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Create User</button>
                <a href="users.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>

</main>

</body>
</html>