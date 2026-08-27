<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

$notifId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($notifId <= 0) { header("Location: notifications.php"); exit(); }

$sql = "SELECT n.*, u.full_name AS sender_name 
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.user_id
        WHERE n.notification_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $notifId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { header("Location: notifications.php"); exit(); }
$notif = $result->fetch_assoc();
$stmt->close();

// Mark as read
if (!$notif['is_read']) {
    $update = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ?");
    $update->bind_param("i", $notifId);
    $update->execute();
    $update->close();
    $notif['is_read'] = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Details | NexArena</title>
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
            <div class="header-icon"><i class="fa-regular fa-file-lines"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Notification Details</h1>
                <p>View full notification content.</p>
            </div>
        </div>
        <a href="notifications.php" class="add-user-btn" style="background:#f4f4f5;color:#1f2937;box-shadow:none;">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
    </section>

    <div class="detail-card">
        <div class="detail-section">
            <h3><i class="fa-regular fa-circle-info" style="color:var(--orange);"></i> Notification</h3>
            <table class="detail-table">
                <tr><th>ID</th><td>#<?= (int)$notif['notification_id']; ?></td></tr>
                <tr><th>Title</th><td><strong><?= htmlspecialchars($notif['title']); ?></strong></td></tr>
                <tr><th>Message</th><td><?= nl2br(htmlspecialchars($notif['message'])); ?></td></tr>
                <tr><th>Type</th><td><span class="notif-type <?= $notif['type']; ?>"><?= ucfirst($notif['type']); ?></span></td></tr>
                <tr><th>Recipient</th><td><?= htmlspecialchars($notif['sender_name'] ?? 'System'); ?></td></tr>
                <tr><th>Status</th><td><?= $notif['is_read'] ? '<span style="color:#059669;"><i class="fa-regular fa-check-circle"></i> Read</span>' : '<span style="color:#d97706;"><i class="fa-regular fa-clock"></i> Unread</span>'; ?></td></tr>
                <tr><th>Sent</th><td><?= date('M d, Y H:i', strtotime($notif['created_at'])); ?></td></tr>
                <?php if ($notif['read_at']): ?>
                    <tr><th>Read At</th><td><?= date('M d, Y H:i', strtotime($notif['read_at'])); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="detail-actions">
            <a href="notifications.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a href="delete_notification.php?id=<?= (int)$notif['notification_id']; ?>" class="btn-danger" onclick="return confirm('Delete this notification?');"><i class="fa-solid fa-trash"></i> Delete</a>
        </div>
    </div>
</main>
</body>
</html>