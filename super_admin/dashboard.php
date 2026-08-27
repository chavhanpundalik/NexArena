<?php
require_once "../db_connect.php";
session_start();

// --- Access control ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../login.php");
    exit();
}

// --- Get real counts from database ---
$totalUsers = 0;
$totalEvents = 0;
$totalTeams = 0;
$totalRegistrations = 0;
$totalRevenue = 0;

// Total Users
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    $totalUsers = (int)($row['total'] ?? 0);
    $result->free();
}

// Total Events
$result = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($result) {
    $row = $result->fetch_assoc();
    $totalEvents = (int)($row['total'] ?? 0);
    $result->free();
}

// Total Teams
$result = $conn->query("SELECT COUNT(*) AS total FROM teams");
if ($result) {
    $row = $result->fetch_assoc();
    $totalTeams = (int)($row['total'] ?? 0);
    $result->free();
}

// Total Registrations
$result = $conn->query("SELECT COUNT(*) AS total FROM registrations");
if ($result) {
    $row = $result->fetch_assoc();
    $totalRegistrations = (int)($row['total'] ?? 0);
    $result->free();
}

// Total Revenue (if payment table exists)
$revenueTableCheck = $conn->query("SHOW TABLES LIKE 'payments'");
if ($revenueTableCheck && $revenueTableCheck->num_rows > 0) {
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'completed'");
    if ($result) {
        $row = $result->fetch_assoc();
        $totalRevenue = (float)($row['total'] ?? 0);
        $result->free();
    }
}

// --- Get recent activity (FIXED - removed team_id reference) ---
$recentActivities = [];
$activitySql = "
    (SELECT 'user' AS type, user_id AS id, username AS name, created_at AS date, 'registered' AS action 
     FROM users ORDER BY created_at DESC LIMIT 5)
    UNION
    (SELECT 'event' AS type, event_id AS id, event_name AS name, created_at AS date, 'created' AS action 
     FROM events ORDER BY created_at DESC LIMIT 5)
    UNION
    (SELECT 'team' AS type, team_id AS id, team_name AS name, created_at AS date, 'created' AS action 
     FROM teams ORDER BY created_at DESC LIMIT 5)
    UNION
    (SELECT 'registration' AS type, registration_id AS id, CONCAT('Registration #', registration_id) AS name, registered_at AS date, 'submitted' AS action 
     FROM registrations ORDER BY registered_at DESC LIMIT 5)
    ORDER BY date DESC LIMIT 10
";

$activityResult = $conn->query($activitySql);
if ($activityResult) {
    while ($row = $activityResult->fetch_assoc()) {
        $recentActivities[] = $row;
    }
    $activityResult->free();
}

// --- Get top teams by registrations (FIXED - using user_id instead of team_id) ---
$topTeams = [];
$topTeamsSql = "
    SELECT 
        t.team_id,
        t.team_name,
        t.sport,
        COUNT(DISTINCT r.user_id) AS member_count,
        t.created_at
    FROM teams t
    LEFT JOIN team_members tm ON t.team_id = tm.team_id
    LEFT JOIN users u ON tm.user_id = u.user_id
    LEFT JOIN registrations r ON u.user_id = r.user_id
    GROUP BY t.team_id
    ORDER BY member_count DESC
    LIMIT 5
";

$topTeamsResult = $conn->query($topTeamsSql);
if ($topTeamsResult) {
    while ($row = $topTeamsResult->fetch_assoc()) {
        $topTeams[] = $row;
    }
    $topTeamsResult->free();
}

// --- Get upcoming events ---
$upcomingEvents = [];
$upcomingSql = "
    SELECT 
        event_id,
        event_name,
        sport,
        event_date,
        location,
        status,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.event_id) AS participant_count
    FROM events e
    WHERE event_date >= CURDATE()
    ORDER BY event_date ASC
    LIMIT 5
";

$upcomingResult = $conn->query($upcomingSql);
if ($upcomingResult) {
    while ($row = $upcomingResult->fetch_assoc()) {
        $upcomingEvents[] = $row;
    }
    $upcomingResult->free();
}

// --- Get user growth (last 7 days) ---
$userGrowth = [];
$growthSql = "
    SELECT 
        DATE(created_at) AS date,
        COUNT(*) AS count
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";

$growthResult = $conn->query($growthSql);
if ($growthResult) {
    while ($row = $growthResult->fetch_assoc()) {
        $userGrowth[$row['date']] = $row['count'];
    }
    $growthResult->free();
}

// --- Get active users (last 30 days) ---
$activeUsers = 0;
$activeSql = "SELECT COUNT(DISTINCT user_id) AS active FROM registrations WHERE registered_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$activeResult = $conn->query($activeSql);
if ($activeResult) {
    $row = $activeResult->fetch_assoc();
    $activeUsers = (int)($row['active'] ?? 0);
    $activeResult->free();
}

// --- Get system status ---
$systemStatus = [
    'database' => 'Connected',
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'php_version' => phpversion(),
    'last_backup' => 'Not Available'
];

// Check if backup log exists
$backupCheck = $conn->query("SHOW TABLES LIKE 'backup_logs'");
if ($backupCheck && $backupCheck->num_rows > 0) {
    $backupResult = $conn->query("SELECT created_at FROM backup_logs ORDER BY created_at DESC LIMIT 1");
    if ($backupResult && $backupResult->num_rows > 0) {
        $backupRow = $backupResult->fetch_assoc();
        $systemStatus['last_backup'] = date('M d, Y H:i', strtotime($backupRow['created_at']));
    }
    $backupResult->free();
}

// --- Get recent registrations with user and event info ---
$recentRegistrations = [];
$regSql = "
    SELECT 
        r.registration_id,
        r.registered_at,
        r.status,
        u.username,
        e.event_name
    FROM registrations r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN events e ON r.event_id = e.event_id
    ORDER BY r.registered_at DESC
    LIMIT 5
";

$regResult = $conn->query($regSql);
if ($regResult) {
    while ($row = $regResult->fetch_assoc()) {
        $recentRegistrations[] = $row;
    }
    $regResult->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard | NexArena</title>
    <!-- Font Awesome 6 (Free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/sidebar.css">
    <link rel="stylesheet" href="assets/dashboard.css">
    <style>
        /* ========================================
           COMPLETE ORANGE THEME
           White Background | Black Text | Orange Accents
           ======================================== */

        /* ---- Root Variables ---- */
        :root {
            --orange: #f97316;
            --orange-dark: #ea580c;
            --orange-light: #fb923c;
            --orange-bg: #fff7ed;
            --orange-border: #fed7aa;
            --orange-shadow: rgba(249, 115, 22, 0.25);
            --white: #ffffff;
            --black: #000000;
            --gray-dark: #1e293b;
            --gray: #64748b;
            --gray-light: #94a3b8;
            --border: #e2e8f0;
            --border-dark: #cbd5e1;
        }

        /* ---- Global Reset ---- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, 
        .main-content,
        .dashboard-welcome,
        .card,
        .stat-card {
            background: var(--white) !important;
            color: var(--black) !important;
        }

        /* ---- Scrollbar ---- */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--orange);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--orange-dark);
        }

        /* ========================================
           MAIN CONTENT
           ======================================== */
        .main-content {
            padding: 24px 32px;
            min-height: 100vh;
            background: var(--white) !important;
        }

        /* ========================================
           WELCOME SECTION
           ======================================== */
        .dashboard-welcome {
            background: var(--white) !important;
            padding: 20px 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 28px;
        }
        .dashboard-welcome h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--black) !important;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .dashboard-welcome h1 i {
            color: var(--orange) !important;
        }
        .dashboard-welcome .welcome-highlight {
            color: var(--orange) !important;
        }
        .dashboard-welcome p {
            color: var(--gray) !important;
            font-size: 15px;
            margin-top: 4px;
        }
        .dashboard-welcome p i {
            color: var(--orange) !important;
        }

        /* ========================================
           STATS GRID
           ======================================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--white) !important;
            border: 1px solid var(--border) !important;
            border-radius: 12px;
            padding: 20px 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            border-color: var(--orange-border) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06) !important;
            transform: translateY(-2px);
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: var(--orange-bg) !important;
            color: var(--orange) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        .stat-card .stat-label {
            color: var(--gray) !important;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .stat-card .stat-label i {
            color: var(--orange) !important;
            margin-right: 4px;
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: var(--black) !important;
        }
        .stat-card .stat-change {
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
        }
        .stat-card .stat-change.up {
            color: #22c55e !important;
        }
        .stat-card .stat-change.down {
            color: #ef4444 !important;
        }
        .stat-card .stat-change i {
            margin-right: 3px;
        }

        /* Orange accent bar on stat card */
        .stat-card.orange-accent::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--orange) !important;
        }

        /* ========================================
           CONTENT GRID
           ======================================== */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }

        /* ========================================
           CARDS
           ======================================== */
        .card {
            background: var(--white) !important;
            border: 1px solid var(--border) !important;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .card:hover {
            border-color: var(--orange-border) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06) !important;
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border) !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--black) !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-header h3 i {
            color: var(--orange) !important;
        }
        .card-action {
            color: var(--orange) !important;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none !important;
            transition: all 0.3s ease;
        }
        .card-action:hover {
            color: var(--orange-dark) !important;
        }
        .card-body {
            padding: 16px 20px;
        }

        /* ========================================
           ACTIVITY LIST
           ======================================== */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .activity-item:hover {
            background: var(--orange-bg) !important;
        }
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            color: #fff;
        }
        .activity-icon.orange { background: var(--orange) !important; }
        .activity-icon.green { background: #22c55e !important; }
        .activity-icon.blue { background: #3b82f6 !important; }
        .activity-icon.purple { background: #8b5cf6 !important; }
        .activity-icon.red { background: #ef4444 !important; }
        .activity-icon.teal { background: #14b8a6 !important; }
        .activity-content {
            flex: 1;
        }
        .activity-content p {
            color: var(--black) !important;
            font-size: 14px;
            margin: 0;
        }
        .activity-content p strong {
            color: var(--black) !important;
        }
        .activity-time {
            color: var(--gray) !important;
            font-size: 12px;
            display: block;
            margin-top: 2px;
        }
        .activity-time i {
            margin-right: 4px;
        }

        /* ========================================
           RANK LIST
           ======================================== */
        .rank-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .rank-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .rank-item:hover {
            background: var(--orange-bg) !important;
        }
        .rank-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--orange-bg) !important;
            color: var(--orange) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .rank-item:nth-child(1) .rank-number {
            background: #fef3c7 !important;
            color: #d97706 !important;
        }
        .rank-item:nth-child(2) .rank-number {
            background: #e5e7eb !important;
            color: #6b7280 !important;
        }
        .rank-item:nth-child(3) .rank-number {
            background: #fde68a !important;
            color: #92400e !important;
        }
        .rank-info {
            flex: 1;
        }
        .rank-info h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--black) !important;
            margin: 0;
        }
        .rank-info h4 i {
            color: var(--orange) !important;
            margin-right: 4px;
        }
        .rank-info span {
            font-size: 12px;
            color: var(--gray) !important;
        }
        .rank-info span i {
            color: var(--orange) !important;
        }
        .rank-value {
            font-size: 18px;
        }

        /* ========================================
           ACTION BUTTONS SECTION - ORANGE
           ======================================== */
        .action-buttons-section {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding: 20px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        /* Orange Primary Button */
        .btn-orange {
            background: var(--orange) !important;
            color: #ffffff !important;
            border: none !important;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none !important;
        }
        .btn-orange:hover {
            background: var(--orange-dark) !important;
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--orange-shadow) !important;
        }

        /* Orange Outline Button */
        .btn-orange-outline {
            background: transparent !important;
            color: var(--orange) !important;
            border: 2px solid var(--orange) !important;
            padding: 8px 22px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none !important;
        }
        .btn-orange-outline:hover {
            background: var(--orange) !important;
            color: #ffffff !important;
            transform: translateY(-2px);
        }

        /* ========================================
           QUICK STATS FOOTER
           ======================================== */
        .quick-stats-footer {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            padding-top: 20px;
        }
        .quick-stat-item {
            background: var(--white) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px;
            padding: 16px 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .quick-stat-item:hover {
            border-color: var(--orange-border) !important;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05) !important;
        }
        .quick-stat-item i {
            font-size: 24px;
            color: var(--orange) !important;
            margin-bottom: 6px;
            display: block;
        }
        .quick-stat-item .stat-label-text {
            font-size: 12px;
            color: var(--gray) !important;
            margin: 0;
        }
        .quick-stat-item .stat-value-text {
            font-size: 16px;
            font-weight: 700;
            color: var(--black) !important;
            margin: 2px 0 0 0;
        }

        /* ========================================
           EMPTY STATE
           ======================================== */
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: var(--gray) !important;
        }
        .empty-state i {
            font-size: 40px;
            color: var(--orange) !important;
            margin-bottom: 12px;
            display: block;
        }
        .empty-state h4 {
            color: var(--black) !important;
            font-size: 16px;
            margin-bottom: 4px;
        }
        .empty-state p {
            color: var(--gray) !important;
            font-size: 14px;
        }

        /* ========================================
           REGISTRATION BADGE
           ======================================== */
        .reg-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .reg-badge.pending { background: #fef3c7; color: #d97706; }
        .reg-badge.confirmed { background: #dcfce7; color: #16a34a; }
        .reg-badge.cancelled { background: #fef2f2; color: #dc2626; }

        /* ========================================
           RESPONSIVE
           ======================================== */
        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }
            .dashboard-welcome h1 {
                font-size: 22px;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            .stat-card {
                padding: 16px;
            }
            .stat-card .stat-number {
                font-size: 22px;
            }
            .action-buttons-section {
                flex-direction: column;
            }
            .action-buttons-section .btn-orange,
            .action-buttons-section .btn-orange-outline {
                width: 100%;
                justify-content: center;
            }
            .quick-stats-footer {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .quick-stats-footer {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Include Super Admin Sidebar -->
<?php include "sidebar.php"; ?>

<!-- ============================================================
     MAIN CONTENT
============================================================ -->
<main class="main-content">

    <!-- Welcome Section -->
    <div class="dashboard-welcome">
        <h1>
            <i class="fas fa-hand-wave"></i> Welcome Back, <span class="welcome-highlight"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Super Admin'); ?></span>!
        </h1>
        <p><i class="fas fa-rocket"></i> Manage your platform with premium control & insights</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card orange-accent">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-label"><i class="fas fa-chart-line"></i> Total Users</div>
            <div class="stat-number"><?= number_format($totalUsers); ?></div>
            <?php if (!empty($userGrowth)): ?>
                <?php $latest = end($userGrowth); ?>
                <div class="stat-change up"><i class="fas fa-arrow-up"></i> <?= $latest; ?> new this week</div>
            <?php else: ?>
                <div class="stat-change"><i class="fas fa-minus"></i> No new users</div>
            <?php endif; ?>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-label"><i class="fas fa-chart-line"></i> Total Events</div>
            <div class="stat-number"><?= number_format($totalEvents); ?></div>
            <?php if (!empty($upcomingEvents)): ?>
                <div class="stat-change up"><i class="fas fa-arrow-up"></i> <?= count($upcomingEvents); ?> upcoming</div>
            <?php else: ?>
                <div class="stat-change"><i class="fas fa-minus"></i> No upcoming events</div>
            <?php endif; ?>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users-cog"></i></div>
            <div class="stat-label"><i class="fas fa-chart-line"></i> Total Teams</div>
            <div class="stat-number"><?= number_format($totalTeams); ?></div>
            <div class="stat-change"><i class="fas fa-users"></i> Active teams</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label"><i class="fas fa-chart-line"></i> Registrations</div>
            <div class="stat-number"><?= number_format($totalRegistrations); ?></div>
            <div class="stat-change up"><i class="fas fa-user-check"></i> <?= $activeUsers; ?> active users (30 days)</div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Recent Activity</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($recentActivities)): ?>
                    <div class="activity-list">
                        <?php foreach ($recentActivities as $activity): ?>
                            <?php 
                            $iconMap = [
                                'user' => ['icon' => 'fa-user-plus', 'color' => 'orange'],
                                'event' => ['icon' => 'fa-calendar-plus', 'color' => 'blue'],
                                'team' => ['icon' => 'fa-users-cog', 'color' => 'green'],
                                'registration' => ['icon' => 'fa-check-circle', 'color' => 'teal']
                            ];
                            $iconInfo = $iconMap[$activity['type']] ?? ['icon' => 'fa-circle', 'color' => 'gray'];
                            ?>
                            <div class="activity-item">
                                <div class="activity-icon <?= $iconInfo['color']; ?>">
                                    <i class="fas <?= $iconInfo['icon']; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <p>
                                        <strong><?= htmlspecialchars($activity['name'] ?? 'Unknown'); ?></strong> 
                                        was <?= htmlspecialchars($activity['action'] ?? 'created'); ?>
                                        <?php if ($activity['type'] == 'user'): ?>
                                            as a new user
                                        <?php elseif ($activity['type'] == 'event'): ?>
                                            as an event
                                        <?php elseif ($activity['type'] == 'team'): ?>
                                            as a team
                                        <?php else: ?>
                                            a registration
                                        <?php endif; ?>
                                    </p>
                                    <span class="activity-time">
                                        <i class="far fa-clock"></i> 
                                        <?= date('M d, Y H:i', strtotime($activity['date'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4>No Recent Activity</h4>
                        <p>Your platform is quiet right now. Check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Teams -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-trophy"></i> Top Performing Teams</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($topTeams)): ?>
                    <div class="rank-list">
                        <?php foreach ($topTeams as $index => $team): ?>
                            <?php $rank = $index + 1; ?>
                            <div class="rank-item">
                                <div class="rank-number"><?= $rank; ?></div>
                                <div class="rank-info">
                                    <h4><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($team['team_name']); ?></h4>
                                    <span><i class="fas fa-tag"></i> <?= htmlspecialchars($team['sport'] ?? 'General'); ?> • <i class="fas fa-users"></i> <?= (int)$team['member_count']; ?> members</span>
                                </div>
                                <div class="rank-value">
                                    <?php if ($rank == 1): ?>
                                        <i class="fas fa-crown" style="color: #f59e0b;"></i>
                                    <?php elseif ($rank == 2): ?>
                                        <i class="fas fa-medal" style="color: #9ca3af;"></i>
                                    <?php elseif ($rank == 3): ?>
                                        <i class="fas fa-medal" style="color: #cd7f32;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-star" style="color: var(--orange);"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <h4>No Teams Yet</h4>
                        <p>Teams will appear here once created.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ============================================================
         RECENT REGISTRATIONS SECTION
    ============================================================ -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-list"></i> Recent Registrations</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($recentRegistrations)): ?>
                <div style="display:grid;gap:10px;">
                    <?php foreach ($recentRegistrations as $reg): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--orange-bg);border-radius:8px;border:1px solid var(--orange-border);flex-wrap:wrap;gap:8px;">
                            <div>
                                <strong style="color:var(--black);"><?= htmlspecialchars($reg['username'] ?? 'Unknown User'); ?></strong>
                                <span style="color:var(--gray);font-size:13px;">registered for</span>
                                <strong style="color:var(--black);"><?= htmlspecialchars($reg['event_name'] ?? 'Unknown Event'); ?></strong>
                            </div>
                            <div>
                                <span class="reg-badge <?= $reg['status'] ?? 'pending'; ?>">
                                    <?= ucfirst($reg['status'] ?? 'Pending'); ?>
                                </span>
                                <span style="color:var(--gray);font-size:12px;margin-left:8px;">
                                    <i class="far fa-clock"></i> <?= date('M d, Y', strtotime($reg['registered_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h4>No Registrations Yet</h4>
                    <p>Registrations will appear here once users sign up for events.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================
         UPCOMING EVENTS SECTION
    ============================================================ -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-calendar-check"></i> Upcoming Events</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($upcomingEvents)): ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:14px;">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <div style="background:var(--orange-bg);border-radius:8px;padding:14px 18px;border:1px solid var(--orange-border);">
                            <h4 style="color:var(--black);font-size:15px;font-weight:700;margin:0;">
                                <?= htmlspecialchars($event['event_name']); ?>
                            </h4>
                            <div style="color:var(--gray);font-size:13px;margin-top:4px;">
                                <i class="fas fa-tag" style="color:var(--orange);"></i> <?= htmlspecialchars($event['sport'] ?? 'General'); ?>
                            </div>
                            <div style="color:var(--gray);font-size:13px;margin-top:2px;">
                                <i class="fas fa-calendar" style="color:var(--orange);"></i> <?= date('M d, Y H:i', strtotime($event['event_date'])); ?>
                            </div>
                            <?php if (!empty($event['location'])): ?>
                                <div style="color:var(--gray);font-size:13px;margin-top:2px;">
                                    <i class="fas fa-map-marker-alt" style="color:var(--orange);"></i> <?= htmlspecialchars($event['location']); ?>
                                </div>
                            <?php endif; ?>
                            <div style="color:var(--gray);font-size:13px;margin-top:2px;">
                                <i class="fas fa-users" style="color:var(--orange);"></i> <?= (int)$event['participant_count']; ?> participants
                            </div>
                            <div style="margin-top:8px;">
                                <span style="background:var(--orange);color:#fff;padding:2px 12px;border-radius:12px;font-size:11px;font-weight:600;">
                                    <?= ucfirst($event['status'] ?? 'Upcoming'); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No Upcoming Events</h4>
                    <p>Create an event to get started.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================
         ACTION BUTTONS SECTION - ORANGE BUTTONS
    ============================================================ -->
    <div class="action-buttons-section">
        <a href="add_user.php" class="btn-orange">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
        <a href="create_event.php" class="btn-orange-outline">
            <i class="fas fa-calendar-plus"></i> Create Event
        </a>
        <a href="teams.php" class="btn-orange-outline">
            <i class="fas fa-users"></i> Manage Teams
        </a>
        <a href="advanced_settings.php" class="btn-orange-outline">
            <i class="fas fa-sliders-h"></i> Settings
        </a>
    </div>

    <!-- ============================================================
         QUICK STATS FOOTER - ORANGE THEME
    ============================================================ -->
    <div class="quick-stats-footer">
        <div class="quick-stat-item">
            <i class="fas fa-check-circle"></i>
            <p class="stat-label-text">System Status</p>
            <p class="stat-value-text"><?= $systemStatus['database']; ?></p>
        </div>
        <div class="quick-stat-item">
            <i class="fas fa-server"></i>
            <p class="stat-label-text">Server</p>
            <p class="stat-value-text"><?= htmlspecialchars($systemStatus['server']); ?></p>
        </div>
        <div class="quick-stat-item">
            <i class="fas fa-code"></i>
            <p class="stat-label-text">PHP Version</p>
            <p class="stat-value-text"><?= $systemStatus['php_version']; ?></p>
        </div>
        <div class="quick-stat-item">
            <i class="fas fa-database"></i>
            <p class="stat-label-text">Last Backup</p>
            <p class="stat-value-text"><?= $systemStatus['last_backup']; ?></p>
        </div>
    </div>

</main>

</body>
</html>