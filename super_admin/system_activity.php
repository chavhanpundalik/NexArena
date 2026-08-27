<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Filters ---
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$user = isset($_GET['user']) ? trim($_GET['user']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// --- Build query ---
$sql = "SELECT * FROM activity_log WHERE 1=1";
$params = [];
$types = '';

if ($action !== '') {
    $sql .= " AND action LIKE ?";
    $params[] = "%$action%";
    $types .= "s";
}
if ($user !== '') {
    $sql .= " AND username LIKE ?";
    $params[] = "%$user%";
    $types .= "s";
}
if ($date_from !== '') {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if ($date_to !== '') {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC LIMIT 200";

$stmt = $conn->prepare($sql);
if (!$stmt) { die("Query error: " . $conn->error); }
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Stats ---
$totalLogs = $conn->query("SELECT COUNT(*) AS total FROM activity_log")->fetch_assoc()['total'] ?? 0;
$actionsList = $conn->query("SELECT DISTINCT action FROM activity_log ORDER BY action")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Activity | NexArena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/users.css">
    <link rel="stylesheet" href="assets/activity.css">
</head>
<body>
<?php include "sidebar.php"; ?>

<main class="users-main">

    <section class="page-header">
        <div class="header-left">
            <div class="header-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div>
                <span class="page-label">SUPER ADMIN</span>
                <h1>System Activity</h1>
                <p>Audit log of all admin actions.</p>
            </div>
        </div>
        <a href="system_activity.php?clear=1" class="add-user-btn" style="background:#dc2626;color:#fff;box-shadow:none;" onclick="return confirm('Delete all activity logs? This cannot be undone.');">
            <i class="fa-solid fa-trash"></i> Clear All
        </a>
    </section>

    <!-- Stats -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-regular fa-clipboard"></i></div>
            <div class="stat-content">
                <span>Total Activities</span>
                <strong><?= number_format($totalLogs); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="stat-icon" style="background:#dbeafe; color:#2563eb;"><i class="fa-regular fa-clock"></i></div>
            <div class="stat-content">
                <span>Today</span>
                <strong><?= number_format($conn->query("SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'] ?? 0); ?></strong>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
            <div class="stat-icon" style="background:#ede9fe; color:#7c3aed;"><i class="fa-solid fa-user"></i></div>
            <div class="stat-content">
                <span>Unique Admins</span>
                <strong><?= number_format($conn->query("SELECT COUNT(DISTINCT user_id) FROM activity_log")->fetch_assoc()['total'] ?? 0); ?></strong>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="filter-card">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="action" placeholder="Action (e.g., login, delete)" value="<?= htmlspecialchars($action); ?>">
            </div>
            <div class="search-box">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="user" placeholder="Username" value="<?= htmlspecialchars($user); ?>">
            </div>
            <div class="search-box" style="min-width:130px;">
                <i class="fa-regular fa-calendar"></i>
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from); ?>">
            </div>
            <div class="search-box" style="min-width:130px;">
                <i class="fa-regular fa-calendar"></i>
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to); ?>">
            </div>
            <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="system_activity.php" class="reset-btn"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </form>
    </section>

    <!-- Activity Table -->
    <section class="users-card">
        <div class="table-header">
            <div>
                <span class="section-label">ACTIVITY LOG</span>
                <h2>Recent Actions</h2>
            </div>
            <span class="user-count"><?= count($logs); ?> entries</span>
        </div>
        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                            <td><?= htmlspecialchars($log['username']); ?></td>
                            <td><span class="action-badge"><?= htmlspecialchars($log['action']); ?></span></td>
                            <td><?= htmlspecialchars(substr($log['details'] ?? '', 0, 60)) . (strlen($log['details'] ?? '') > 60 ? '...' : ''); ?></td>
                            <td><?= htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-clock"></i></div>
                        <h3>No Activity Found</h3>
                        <p>Adjust your filters or wait for admin actions.</p>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>
</body>
</html>