<?php
session_start();

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
                    <strong>1,284</strong>
                    <small><i class="fas fa-arrow-up" style="color: #22c55e;"></i> 12% from last month</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                <div class="stat-info">
                    <span>Total Sports</span>
                    <strong>8</strong>
                    <small><i class="fas fa-arrow-up" style="color: #22c55e;"></i> 2 new this year</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <span>Total Events</span>
                    <strong>45</strong>
                    <small><i class="fas fa-arrow-up" style="color: #22c55e;"></i> 18% from last month</small>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-handshake"></i></div>
                <div class="stat-info">
                    <span>Registrations</span>
                    <strong>342</strong>
                    <small><i class="fas fa-arrow-down" style="color: #ef4444;"></i> 5% from last month</small>
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
                <div class="empty-dashboard">
                    <div class="empty-icon"><i class="fas fa-calendar-plus"></i></div>
                    <h4>No upcoming events</h4>
                    <p>Create an event to see it here.</p>
                </div>
            </div>

            <div class="dashboard-panel">
                <div class="panel-header">
                    <div>
                        <h3>Recent Registrations</h3>
                        <p>Latest event registrations</p>
                    </div>
                    <a href="registrations.php">View All →</a>
                </div>
                <div class="empty-dashboard">
                    <div class="empty-icon"><i class="fas fa-file-signature"></i></div>
                    <h4>No registrations</h4>
                    <p>New registrations will appear here.</p>
                </div>
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

</body>
</html>