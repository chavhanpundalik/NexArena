<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];
$errors = [];
$success = false;
$form_data = [
    'title' => '',
    'message' => '',
    'type' => 'system',
    'recipient_type' => 'all',
    'user_id' => ''
];

// --- Get users for recipient dropdown ---
$users = $conn->query("SELECT user_id, full_name, email, role FROM users ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'system';
    $recipient_type = $_POST['recipient_type'] ?? 'all';
    $user_id = (int)($_POST['user_id'] ?? 0);

    $form_data = compact('title', 'message', 'type', 'recipient_type', 'user_id');

    if (strlen($title) < 3) { $errors[] = "Title must be at least 3 characters."; }
    if (strlen($message) < 10) { $errors[] = "Message must be at least 10 characters."; }
    if (!in_array($type, ['registration','event','team','fixture','match','invitation','system'])) {
        $errors[] = "Invalid notification type.";
    }
    if ($recipient_type === 'specific' && $user_id <= 0) {
        $errors[] = "Please select a recipient user.";
    }

    if (empty($errors)) {
        // Determine recipients
        if ($recipient_type === 'all') {
            // Send to all users
            $userQuery = $conn->query("SELECT user_id FROM users WHERE status = 'active'");
            while ($row = $userQuery->fetch_assoc()) {
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
                $link = '';
                $stmt->bind_param("issss", $row['user_id'], $title, $message, $type, $link);
                $stmt->execute();
                $stmt->close();
            }
            $success = true;
        } elseif ($recipient_type === 'role') {
            // Send to a specific role
            $role = $_POST['role'] ?? 'user';
            if (!in_array($role, ['user','admin','super_admin'])) {
                $errors[] = "Invalid role selected.";
            } else {
                $userQuery = $conn->prepare("SELECT user_id FROM users WHERE role = ? AND status = 'active'");
                $userQuery->bind_param("s", $role);
                $userQuery->execute();
                $result = $userQuery->get_result();
                while ($row = $result->fetch_assoc()) {
                    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
                    $link = '';
                    $stmt->bind_param("issss", $row['user_id'], $title, $message, $type, $link);
                    $stmt->execute();
                    $stmt->close();
                }
                $success = true;
                $userQuery->close();
            }
        } elseif ($recipient_type === 'specific' && $user_id > 0) {
            // Send to specific user
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
            $link = '';
            $stmt->bind_param("issss", $user_id, $title, $message, $type, $link);
            $stmt->execute();
            $stmt->close();
            $success = true;
        }

        if ($success) {
            $form_data = ['title' => '', 'message' => '', 'type' => 'system', 'recipient_type' => 'all', 'user_id' => ''];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/notifications.css">
</head>
<body>
<?php include "sidebar.php"; ?>
<main class="users-main">
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-bell-plus"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Send Notification</h1>
                <p>Compose and send a system notification.</p>
            </div>
        </div>
        <a href="notifications.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </section>

    <div class="form-card">
        <h2><i class="fa-regular fa-pen-to-square" style="color:var(--orange);"></i> Compose</h2>
        <p class="subtitle">Send notifications to users.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> Notification sent successfully! <a href="notifications.php" style="color:var(--orange);font-weight:700;text-decoration:none;margin-left:10px;">View all →</a></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Please fix: <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($form_data['title']); ?>" placeholder="Notification title" required>
            </div>

            <div class="form-group">
                <label for="message">Message <span class="required">*</span></label>
                <textarea id="message" name="message" rows="5" placeholder="Type your message here..." required><?= htmlspecialchars($form_data['message']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="type">Notification Type</label>
                <select name="type" id="type">
                    <option value="system" <?= $form_data['type'] === 'system' ? 'selected' : ''; ?>>System</option>
                    <option value="event" <?= $form_data['type'] === 'event' ? 'selected' : ''; ?>>Event</option>
                    <option value="team" <?= $form_data['type'] === 'team' ? 'selected' : ''; ?>>Team</option>
                    <option value="fixture" <?= $form_data['type'] === 'fixture' ? 'selected' : ''; ?>>Fixture</option>
                    <option value="match" <?= $form_data['type'] === 'match' ? 'selected' : ''; ?>>Match</option>
                    <option value="registration" <?= $form_data['type'] === 'registration' ? 'selected' : ''; ?>>Registration</option>
                    <option value="invitation" <?= $form_data['type'] === 'invitation' ? 'selected' : ''; ?>>Invitation</option>
                </select>
            </div>

            <div class="form-group">
                <label for="recipient_type">Recipients <span class="required">*</span></label>
                <select name="recipient_type" id="recipient_type" onchange="toggleRecipientFields()">
                    <option value="all" <?= $form_data['recipient_type'] === 'all' ? 'selected' : ''; ?>>All Users</option>
                    <option value="role" <?= $form_data['recipient_type'] === 'role' ? 'selected' : ''; ?>>Specific Role</option>
                    <option value="specific" <?= $form_data['recipient_type'] === 'specific' ? 'selected' : ''; ?>>Specific User</option>
                </select>
            </div>

            <!-- Role selection (hidden by default) -->
            <div class="form-group" id="role-group" style="display: none;">
                <label for="role">Select Role</label>
                <select name="role" id="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>

            <!-- Specific user selection (hidden by default) -->
            <div class="form-group" id="user-group" style="display: none;">
                <label for="user_id">Select User <span class="required">*</span></label>
                <select name="user_id" id="user_id">
                    <option value="0">-- Select User --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['user_id']; ?>" <?= $form_data['user_id'] == $u['user_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($u['full_name']); ?> (<?= htmlspecialchars($u['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-paper-plane"></i> Send Notification</button>
                <a href="notifications.php" class="btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
function toggleRecipientFields() {
    const val = document.getElementById('recipient_type').value;
    document.getElementById('role-group').style.display = val === 'role' ? 'block' : 'none';
    document.getElementById('user-group').style.display = val === 'specific' ? 'block' : 'none';
}
// Initial toggle on page load
document.addEventListener('DOMContentLoaded', toggleRecipientFields);
</script>
</body>
</html>