<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$adminId = (int)$_SESSION['user_id'];

// Ensure user_profiles table exists
$conn->query("CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    profile_image VARCHAR(255) DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    gender VARCHAR(20) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)");

// Fetch admin data + profile image
$stmt = $conn->prepare("SELECT u.user_id, u.full_name, u.username, u.email, u.phone, p.profile_image 
                         FROM users u 
                         LEFT JOIN user_profiles p ON u.user_id = p.user_id 
                         WHERE u.user_id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: ../login.php"); exit(); }
$admin = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success = false;
$form_data = $admin;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $form_data = compact('full_name', 'email', 'phone');

    // Validation
    if (strlen($full_name) < 2) { $errors[] = "Full name must be at least 2 characters."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid email address."; }

    $updatePassword = false;
    if (!empty($password) || !empty($confirm)) {
        if (strlen($password) < 8) { $errors[] = "Password must be at least 8 characters."; }
        elseif ($password !== $confirm) { $errors[] = "Passwords do not match."; }
        else { $updatePassword = true; }
    }

    // --- Profile image upload ---
    $profile_image = $admin['profile_image']; // keep existing by default
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed)) {
            $errors[] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif ($file['size'] > $maxSize) {
            $errors[] = "Image size must be less than 2MB.";
        } else {
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $adminId . '_' . time() . '.' . $ext;
            $target = 'uploads/profiles/' . $filename;

            // Ensure directory exists
            if (!is_dir('uploads/profiles')) {
                mkdir('uploads/profiles', 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $target)) {
                // Delete old image if exists
                if (!empty($admin['profile_image']) && file_exists('uploads/profiles/' . $admin['profile_image'])) {
                    unlink('uploads/profiles/' . $admin['profile_image']);
                }
                $profile_image = $filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    if (empty($errors)) {
        // Update users table
        if ($updatePassword) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, password=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $hashed, $adminId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $adminId);
        }
        if ($stmt->execute()) {
            // Update or insert profile image in user_profiles
            $stmt2 = $conn->prepare("INSERT INTO user_profiles (user_id, profile_image) VALUES (?, ?) ON DUPLICATE KEY UPDATE profile_image = ?");
            $stmt2->bind_param("iss", $adminId, $profile_image, $profile_image);
            $stmt2->execute();
            $stmt2->close();

            // Update session
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;

            if (function_exists('log_activity')) {
                log_activity($conn, $adminId, $full_name, 'Profile Updated', "Admin updated profile" . ($profile_image !== $admin['profile_image'] ? " with new image" : ""));
            }

            $success = true;
            $admin = array_merge($admin, compact('full_name', 'email', 'phone', 'profile_image'));
            $form_data = $admin;
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <style>
        .profile-card {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            padding: 32px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.07);
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--orange);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 800;
            margin: 0 auto 16px;
            box-shadow: 0 4px 16px rgba(249,115,22,0.3);
            overflow: hidden;
            position: relative;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-avatar .avatar-text {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            margin: 0 auto 16px;
            text-align: center;
        }
        .upload-btn-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .upload-btn-wrapper .btn-upload {
            display: inline-block;
            background: var(--orange);
            color: #fff;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            transition: 0.25s;
            cursor: pointer;
        }
        .upload-btn-wrapper .btn-upload:hover {
            background: var(--orange-dark);
        }
        .profile-avatar-wrapper {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-avatar-wrapper small {
            display: block;
            color: #71717a;
            margin-top: 6px;
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
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: #fafafa;
            transition: 0.2s;
        }
        .form-group input:focus {
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
        .current-image {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #71717a;
        }
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .profile-card {
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
            <div class="header-icon"><i class="fa-solid fa-user"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>My Profile</h1>
                <p>Update your personal information and profile picture.</p>
            </div>
        </div>
        <a href="dashboard.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </section>

    <div class="profile-card">
        <div class="profile-avatar-wrapper">
            <div class="profile-avatar">
                <?php if (!empty($admin['profile_image']) && file_exists('uploads/profiles/' . $admin['profile_image'])): ?>
                    <img src="uploads/profiles/<?= htmlspecialchars($admin['profile_image']); ?>" alt="Profile Image">
                <?php else: ?>
                    <div class="avatar-text"><?= strtoupper(substr($admin['full_name'], 0, 1)); ?></div>
                <?php endif; ?>
            </div>
            <small><?= htmlspecialchars($admin['full_name']); ?></small>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Profile updated successfully!</div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_image">Profile Picture</label>
                <div class="upload-btn-wrapper">
                    <span class="btn-upload"><i class="fa-solid fa-camera"></i> Choose Image</span>
                    <input type="file" name="profile_image" accept="image/*">
                </div>
                <small class="current-image">Allowed: JPG, PNG, GIF, WEBP (max 2MB)</small>
                <?php if (!empty($admin['profile_image'])): ?>
                    <div style="margin-top:8px;">
                        <img src="uploads/profiles/<?= htmlspecialchars($admin['profile_image']); ?>" alt="Current" style="max-width:80px; border-radius:8px; border:1px solid #e5e7eb;">
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($form_data['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($form_data['phone']); ?>">
            </div>

            <div class="password-optional">
                <i class="fa-regular fa-circle-info" style="color:var(--orange);"></i>
                Leave password fields blank to keep current password.
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" minlength="8">
                    <small style="color:#71717a;">Minimum 8 characters.</small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="8">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Update Profile</button>
                <a href="dashboard.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>