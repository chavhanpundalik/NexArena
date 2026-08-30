<?php
session_start();
require_once "../db_connect.php";

/*
|--------------------------------------------------------------------------
| ADMIN ACCESS CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$admin_role = $_SESSION['role'] ?? 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));

// ============================================================
// FETCH REAL STATISTICS FROM DATABASE
// ============================================================

// --- Total Users ---
$totalUsers = 0;
$usersGrowth = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    $totalUsers = (int)$row['total'];
}

// Get users from last month for growth calculation
$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
if ($result) {
    $row = $result->fetch_assoc();
    $lastMonthUsers = (int)$row['total'];
    // Calculate growth percentage (if any users existed before)
    $prevMonthUsers = $totalUsers - $lastMonthUsers;
    if ($prevMonthUsers > 0) {
        $usersGrowth = round(($lastMonthUsers / $prevMonthUsers) * 100);
    } else {
        $usersGrowth = 100; // New platform
    }
}

// --- Total Sports ---
$totalSports = 0;
$sportsGrowth = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM sports");
if ($result) {
    $row = $result->fetch_assoc();
    $totalSports = (int)$row['total'];
}

// Get sports added this year
$result = $conn->query("SELECT COUNT(*) AS total FROM sports WHERE YEAR(created_at) = YEAR(NOW())");
if ($result) {
    $row = $result->fetch_assoc();
    $sportsGrowth = (int)$row['total'];
}

// --- Total Events ---
$totalEvents = 0;
$eventsGrowth = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($result) {
    $row = $result->fetch_assoc();
    $totalEvents = (int)$row['total'];
}

// Get events from last month
$result = $conn->query("SELECT COUNT(*) AS total FROM events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
if ($result) {
    $row = $result->fetch_assoc();
    $lastMonthEvents = (int)$row['total'];
    $prevMonthEvents = $totalEvents - $lastMonthEvents;
    if ($prevMonthEvents > 0) {
        $eventsGrowth = round(($lastMonthEvents / $prevMonthEvents) * 100);
    } else {
        $eventsGrowth = $totalEvents > 0 ? 100 : 0;
    }
}

// --- Total Registrations ---
$totalRegistrations = 0;
$registrationsGrowth = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM registrations");
if ($result) {
    $row = $result->fetch_assoc();
    $totalRegistrations = (int)$row['total'];
}

// Get registrations from last month
$result = $conn->query("SELECT COUNT(*) AS total FROM registrations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
if ($result) {
    $row = $result->fetch_assoc();
    $lastMonthRegistrations = (int)$row['total'];
    $prevMonthRegistrations = $totalRegistrations - $lastMonthRegistrations;
    if ($prevMonthRegistrations > 0) {
        $registrationsGrowth = round(($lastMonthRegistrations / $prevMonthRegistrations) * 100);
    } else {
        $registrationsGrowth = $totalRegistrations > 0 ? 100 : 0;
    }
}

// --- Upcoming Events ---
$upcomingEvents = [];
$result = $conn->query("SELECT event_id, event_name, start_date, end_date, venue 
                        FROM events 
                        WHERE start_date >= NOW() 
                        ORDER BY start_date ASC 
                        LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $upcomingEvents[] = $row;
    }
}

// --- Recent Registrations ---
$recentRegistrations = [];
$result = $conn->query("SELECT r.registration_id, r.created_at, u.username, e.event_name 
                        FROM registrations r
                        LEFT JOIN users u ON r.user_id = u.user_id
                        LEFT JOIN events e ON r.event_id = e.event_id
                        ORDER BY r.created_at DESC 
                        LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentRegistrations[] = $row;
    }
}

// --- Quick Stats for display ---
$growthUp = 'arrow-up';
$growthColor = '#22c55e';
$growthText = 'up';

// Determine if growth should show up or down
$usersGrowthDisplay = $usersGrowth;
$usersGrowthIcon = $usersGrowth >= 0 ? 'arrow-up' : 'arrow-down';
$usersGrowthColor = $usersGrowth >= 0 ? '#22c55e' : '#ef4444';
$usersGrowthText = $usersGrowth >= 0 ? 'up' : 'down';

$eventsGrowthDisplay = $eventsGrowth;
$eventsGrowthIcon = $eventsGrowth >= 0 ? 'arrow-up' : 'arrow-down';
$eventsGrowthColor = $eventsGrowth >= 0 ? '#22c55e' : '#ef4444';
$eventsGrowthText = $eventsGrowth >= 0 ? 'up' : 'down';

$registrationsGrowthDisplay = $registrationsGrowth;
$registrationsGrowthIcon = $registrationsGrowth >= 0 ? 'arrow-up' : 'arrow-down';
$registrationsGrowthColor = $registrationsGrowth >= 0 ? '#22c55e' : '#ef4444';
$registrationsGrowthText = $registrationsGrowth >= 0 ? 'up' : 'down';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NexArena</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- External CSS -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include "sidebar.php"; ?>

<!-- ============================================================
     MAIN AREA (With 270px left margin for sidebar)
============================================================ -->
<div class="admin-main">

    <!-- HEADER -->
    <header class="admin-header">
        <div class="header-left">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?></p>
            </div>
        </div>

        <div class="header-right">
            <a href="notifications.php" class="header-notification" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </a>
            <div class="header-admin">
                <div class="header-admin-avatar">
                    <?php echo $admin_initial; ?>
                </div>
                <div class="header-admin-info">
                    <strong><?php echo htmlspecialchars($admin_name); ?></strong>
                    <span><?php echo htmlspecialchars($admin_role); ?></span>
                </div>
                <i class="fas fa-chevron-down" style="color: var(--text-muted); font-size: 12px;"></i>
            </div>
        </div>
    </header>

    <!-- DASHBOARD CONTENT -->
    <main class="admin-content">

        <!-- WELCOME -->
        <section class="dashboard-welcome">
            <div>
                <span class="welcome-label">NEXARENA ADMIN</span>
                <h2>Welcome back, <span><?php echo htmlspecialchars($admin_name); ?></span> 👋</h2>
                <p>Here's what's happening in your sports event management system.</p>
            </div>
            <a href="events.php" class="dashboard-action">
                <i class="fas fa-plus-circle"></i> Create Event
            </a>
        </section>

        <!-- STATISTICS -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <span>Total Users</span>
                    <strong><?php echo number_format($totalUsers); ?></strong>
                    <small>
                        <i class="fas fa-<?php echo $usersGrowthIcon; ?>" style="color: <?php echo $usersGrowthColor; ?>;"></i> 
                        <?php echo abs($usersGrowthDisplay); ?>% <?php echo $usersGrowthText; ?> from last month
                    </small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                <div class="stat-info">
                    <span>Total Sports</span>
                    <strong><?php echo $totalSports; ?></strong>
                    <small>
                        <i class="fas fa-plus-circle" style="color: #22c55e;"></i> 
                        <?php echo $sportsGrowth; ?> new this year
                    </small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <span>Total Events</span>
                    <strong><?php echo number_format($totalEvents); ?></strong>
                    <small>
                        <i class="fas fa-<?php echo $eventsGrowthIcon; ?>" style="color: <?php echo $eventsGrowthColor; ?>;"></i> 
                        <?php echo abs($eventsGrowthDisplay); ?>% <?php echo $eventsGrowthText; ?> from last month
                    </small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-handshake"></i></div>
                <div class="stat-info">
                    <span>Registrations</span>
                    <strong><?php echo number_format($totalRegistrations); ?></strong>
                    <small>
                        <i class="fas fa-<?php echo $registrationsGrowthIcon; ?>" style="color: <?php echo $registrationsGrowthColor; ?>;"></i> 
                        <?php echo abs($registrationsGrowthDisplay); ?>% <?php echo $registrationsGrowthText; ?> from last month
                    </small>
                </div>
            </div>
        </section>

        <!-- LOWER DASHBOARD -->
        <section class="dashboard-grid">
            <div class="dashboard-panel">
                <div class="panel-header">
                    <div>
                        <h3>Upcoming Events</h3>
                        <p>Recently scheduled events</p>
                    </div>
                    <a href="events.php">View All →</a>
                </div>
                <?php if (!empty($upcomingEvents)): ?>
                    <div class="event-list">
                        <?php foreach ($upcomingEvents as $event): ?>
                            <div class="event-item">
                                <div class="event-date">
                                    <strong><?php echo date('M d', strtotime($event['start_date'])); ?></strong>
                                    <small><?php echo date('Y', strtotime($event['start_date'])); ?></small>
                                </div>
                                <div class="event-info">
                                    <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($event['venue'] ?? 'Venue TBD'); ?></small>
                                </div>
                                <span class="event-status">Upcoming</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-dashboard">
                        <div class="empty-icon"><i class="fas fa-calendar-plus"></i></div>
                        <h4>No upcoming events</h4>
                        <p>Create an event to see it here.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-panel">
                <div class="panel-header">
                    <div>
                        <h3>Recent Registrations</h3>
                        <p>Latest event registrations</p>
                    </div>
                    <a href="registrations.php">View All →</a>
                </div>
                <?php if (!empty($recentRegistrations)): ?>
                    <div class="registration-list">
                        <?php foreach ($recentRegistrations as $reg): ?>
                            <div class="registration-item">
                                <div class="reg-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="reg-info">
                                    <strong><?php echo htmlspecialchars($reg['username'] ?? 'Unknown User'); ?></strong>
                                    <small><?php echo htmlspecialchars($reg['event_name'] ?? 'Unknown Event'); ?></small>
                                </div>
                                <span class="reg-time"><?php echo date('M d, H:i', strtotime($reg['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-dashboard">
                        <div class="empty-icon"><i class="fas fa-file-signature"></i></div>
                        <h4>No registrations</h4>
                        <p>New registrations will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- QUICK ACTIONS -->
        <section class="quick-actions">
            <div class="quick-header">
                <h3>Quick Actions</h3>
                <p>Frequently used admin functions</p>
            </div>
            <div class="quick-grid">
                <a href="events.php" class="quick-card">
                    <span><i class="fas fa-calendar-plus"></i></span>
                    <div>
                        <strong>Manage Events</strong>
                        <small>Create and manage events</small>
                    </div>
                </a>
                <a href="sports.php" class="quick-card">
                    <span><i class="fas fa-trophy"></i></span>
                    <div>
                        <strong>Manage Sports</strong>
                        <small>Add and manage sports</small>
                    </div>
                </a>
                <a href="users.php" class="quick-card">
                    <span><i class="fas fa-users"></i></span>
                    <div>
                        <strong>Manage Users</strong>
                        <small>View registered users</small>
                    </div>
                </a>
                <a href="notifications.php" class="quick-card">
                    <span><i class="fas fa-bell"></i></span>
                    <div>
                        <strong>Notifications</strong>
                        <small>Send system notifications</small>
                    </div>
                </a>
            </div>
        </section>

    </main>
</div>

<style>
    /* Additional Dashboard Styles */
    .event-list, .registration-list {
        padding: 0 20px 20px;
    }
    .event-item, .registration-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .event-item:last-child, .registration-item:last-child {
        border-bottom: none;
    }
    .event-date {
        text-align: center;
        padding: 8px 12px;
        background: #f8fafc;
        border-radius: 8px;
        min-width: 60px;
    }
    .event-date strong {
        display: block;
        font-size: 16px;
        color: #1f2937;
    }
    .event-date small {
        font-size: 10px;
        color: #6b7280;
    }
    .event-info {
        flex: 1;
    }
    .event-info strong {
        display: block;
        font-size: 14px;
        color: #1f2937;
    }
    .event-info small {
        font-size: 12px;
        color: #6b7280;
    }
    .event-status {
        padding: 4px 12px;
        background: #dcfce7;
        color: #16a34a;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .reg-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #ede9fe;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8b5cf6;
    }
    .reg-info {
        flex: 1;
    }
    .reg-info strong {
        display: block;
        font-size: 14px;
        color: #1f2937;
    }
    .reg-info small {
        font-size: 12px;
        color: #6b7280;
    }
    .reg-time {
        font-size: 11px;
        color: #6b7280;
    }
    .quick-actions {
        margin-top: 30px;
    }
    .quick-header {
        margin-bottom: 20px;
    }
    .quick-header h3 {
        font-size: 18px;
        margin-bottom: 4px;
    }
    .quick-header p {
        color: #6b7280;
        font-size: 14px;
    }
    .quick-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    .quick-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 18px 20px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
        text-decoration: none;
        color: #1f2937;
        transition: 0.25s;
    }
    .quick-card:hover {
        background: #fff;
        border-color: #8b5cf6;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(139,92,246,0.1);
    }
    .quick-card span {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #ede9fe;
        color: #8b5cf6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .quick-card div strong {
        display: block;
        font-size: 14px;
    }
    .quick-card div small {
        font-size: 12px;
        color: #6b7280;
    }
    .stat-card {
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 22px 24px;
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        transition: 0.25s;
    }
    .stat-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        transform: translateY(-2px);
    }
    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        background: #ede9fe;
        color: #8b5cf6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .stat-info {
        flex: 1;
    }
    .stat-info span {
        font-size: 13px;
        color: #6b7280;
        font-weight: 500;
    }
    .stat-info strong {
        display: block;
        font-size: 26px;
        font-weight: 800;
        color: #1f2937;
        margin: 4px 0;
    }
    .stat-info small {
        font-size: 12px;
        color: #6b7280;
    }
    .stat-info small i {
        margin-right: 4px;
    }
</style>

</body>
</html>