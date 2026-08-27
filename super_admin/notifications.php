<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Filters ---
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- Stats ---
$totalNotifications = 0;
$sentNotifications = 0;
$pendingNotifications = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM notifications");
if ($result) { $row = $result->fetch_assoc(); $totalNotifications = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 1");
if ($result) { $row = $result->fetch_assoc(); $sentNotifications = (int)$row['total']; }

$result = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0");
if ($result) { $row = $result->fetch_assoc(); $pendingNotifications = (int)$row['total']; }

// --- Build query ---
$sql = "SELECT n.*, u.full_name AS sender_name 
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.user_id
        WHERE 1=1";
$params = [];
$types = '';

if ($type !== '') {
    $sql .= " AND n.type = ?";
    $params[] = $type;
    $types .= "s";
}
if ($search !== '') {
    $sql .= " AND (n.title LIKE ? OR n.message LIKE ?)";
    $sv = "%$search%";
    $params[] = $sv; $params[] = $sv;
    $types .= "ss";
}

$sql .= " ORDER BY n.created_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!$stmt) { die("Query error: " . $conn->error); }
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/notifications.css">
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <!-- Page Header -->
    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-bell"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>Notifications</h1>
                <p>Send and manage system notifications.</p>
            </div>
        </div>
        <a href="send_notification.php" class="add-user-btn">
            <i class="fa-solid fa-plus"></i> Send Notification
        </a>
    </section>

    <!-- Stats -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-regular fa-bell"></i></div>
            <div class="stat-content">
                <span>Total</span>
                <strong><?= number_format($totalNotifications); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #16a34a;">
            <div class="stat-icon" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-check-circle"></i></div>
            <div class="stat-content">
                <span>Read</span>
                <strong><?= number_format($sentNotifications); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #d97706;">
            <div class="stat-icon" style="background:#fef3c7; color:#d97706;"><i class="fa-regular fa-clock"></i></div>
            <div class="stat-content">
                <span>Unread</span>
                <strong><?= number_format($pendingNotifications); ?></strong>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="filter-card">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Search title or message..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="select-box">
                <i class="fa-solid fa-tag"></i>
                <select name="type">
                    <option value="">All Types</option>
                    <option value="registration" <?= $type === 'registration' ? 'selected' : ''; ?>>Registration</option>
                    <option value="event" <?= $type === 'event' ? 'selected' : ''; ?>>Event</option>
                    <option value="team" <?= $type === 'team' ? 'selected' : ''; ?>>Team</option>
                    <option value="fixture" <?= $type === 'fixture' ? 'selected' : ''; ?>>Fixture</option>
                    <option value="match" <?= $type === 'match' ? 'selected' : ''; ?>>Match</option>
                    <option value="invitation" <?= $type === 'invitation' ? 'selected' : ''; ?>>Invitation</option>
                    <option value="system" <?= $type === 'system' ? 'selected' : ''; ?>>System</option>
                </select>
            </div>
            <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="notifications.php" class="reset-btn"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </form>
    </section>

    <!-- Notifications Table -->
    <section class="users-card">
        <div class="table-header">
            <div>
                <span class="section-label">NOTIFICATION DIRECTORY</span>
                <h2>All Notifications</h2>
            </div>
            <span class="user-count"><?= count($notifications); ?> notification<?= count($notifications) != 1 ? 's' : ''; ?></span>
        </div>
        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Recipient</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notif): ?>
                        <tr>
                            <td>
                                <div class="notif-title">
                                    <strong><?= htmlspecialchars($notif['title']); ?></strong>
                                    <small><?= htmlspecialchars(substr($notif['message'], 0, 60)) . (strlen($notif['message']) > 60 ? '...' : ''); ?></small>
                                </div>
                            </td>
                            <td><span class="notif-type <?= $notif['type']; ?>"><?= ucfirst($notif['type']); ?></span></td>
                            <td><?= htmlspecialchars($notif['sender_name'] ?? 'System'); ?></td>
                            <td>
                                <?php if ($notif['is_read']): ?>
                                    <span class="fixture-status" style="background:#d1fae5; color:#059669;"><i class="fa-regular fa-check-circle"></i> Read</span>
                                <?php else: ?>
                                    <span class="fixture-status" style="background:#fef3c7; color:#d97706;"><i class="fa-regular fa-clock"></i> Unread</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y H:i', strtotime($notif['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_notification.php?id=<?= (int)$notif['notification_id']; ?>" class="action-btn view-btn" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <a href="delete_notification.php?id=<?= (int)$notif['notification_id']; ?>" class="action-btn delete-btn" title="Delete" onclick="return confirm('Delete this notification?');"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
                        <h3>No Notifications</h3>
                        <p>No notifications have been sent yet.</p>
                        <a href="send_notification.php" class="empty-reset"><i class="fa-solid fa-plus"></i> Send Notification</a>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>